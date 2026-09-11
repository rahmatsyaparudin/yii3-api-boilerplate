# Middleware Guide

## 📋 Overview

Middleware components provide a way to filter HTTP requests entering your application. In this Yii3 API application, middleware handles cross-cutting concerns like authentication, CORS, rate limiting, security headers, monitoring, and request parameter processing.

All application middleware throw typed `HttpException` subclasses (`UnauthorizedException`, `ForbiddenException`, `TooManyRequestsException`, `BadRequestException`) instead of building responses inline — exceptions are rendered by the `ExceptionResponderFactory` responder registered in the middleware dispatcher.

---

## 🏗️ Middleware Architecture

### Directory Structure

```
src/Shared/Core/Middleware/
├── AccessMiddleware.php          # RBAC permission check (route 'permission' default)
├── CorsMiddleware.php            # Cross-Origin Resource Sharing
├── JwtMiddleware.php             # JWT authentication
├── RateLimitMiddleware.php       # Sliding-window rate limiting (in-memory)
├── RequestParamsMiddleware.php   # Builds RequestParams -> 'payload' attribute
├── SecureHeadersMiddleware.php   # Security headers (CSP, Permissions-Policy, ...)
└── TrustedHostMiddleware.php     # Trusted host validation (wildcard support)

src/Infrastructure/Core/Security/
└── HstsMiddleware.php            # Strict-Transport-Security header

src/Infrastructure/Core/Monitoring/
├── RequestIdMiddleware.php       # X-Request-Id assignment/propagation
├── StructuredLoggingMiddleware.php  # Per-request structured logging
├── MetricsMiddleware.php         # Request metrics collection
└── ErrorMonitoringMiddleware.php # Error/exception capture

src/Api/Shared/
└── NotFoundMiddleware.php        # 404 for unmatched routes (after Router)
```

### Design Principles

#### **1. **Single Responsibility**
- Each middleware handles one specific concern
- Clear separation of concerns
- Easy to test and maintain

#### **2. **Composability**
- Middleware can be chained together
- Flexible request processing pipeline
- Configurable execution order

#### **3. **PSR Compliance**
- PSR-7 HTTP message interface
- PSR-15 middleware interface
- Standardized implementation

#### **4. **Exception-Based Errors**
- Middleware throw `HttpException` subclasses with `Message` translation keys
- A single exception responder renders consistent JSON error responses

---

## 📁 Middleware Components

### 1. TrustedHostMiddleware

**Purpose**: Validates the request `Host` against an allow-list, preventing host-header injection. Supports exact hosts and `*.` wildcard subdomains.

**Location**: `src/Shared/Core/Middleware/TrustedHostMiddleware.php`

```php
final class TrustedHostMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $allowedHosts,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();

        if ($host === '' || !$this->isAllowedHost($host)) {
            throw new UnauthorizedException(
                translate: Message::create(key: 'security.host_not_allowed', params: ['host' => $host])
            );
        }

        return $handler->handle($request);
    }

    private function isAllowedHost(string $host): bool { /* iterate allowedHosts */ }

    private function matchHost(string $host, string $allowedHost): bool
    {
        // exact match, or '*.example.com' suffix match (does not match the bare domain)
    }
}
```

**Configuration**: `allowedHosts` comes from `app/trusted_hosts` params (`app.trusted_hosts.allowedHosts` env, JSON array). Registered inline in `config/web/di/application.php`.

> Note: `Yiisoft\Security\TrustedHosts\TrustedHostsMiddleware` is also defined in `config/common/di/middleware-di.php` as a vendor alternative; the application stack currently uses the custom `App\...\TrustedHostMiddleware`.

---

### 2. CorsMiddleware

**Purpose**: CORS handling — validates `Origin`, answers `OPTIONS` preflight with `204`, and adds CORS headers to responses.

**Location**: `src/Shared/Core/Middleware/CorsMiddleware.php`

```php
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $config,                      // see keys below
        private ResponseFactoryInterface $responseFactory,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $handler->handle($request);
        }

        if (!$this->isOriginAllowed($origin)) {
            throw new ForbiddenException(
                translate: Message::create(key: 'request.origin_not_allowed', params: ['origin' => $origin])
            );
        }

        $allowOrigin = $this->getAllowOriginValue($origin);

        if (\strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = $this->responseFactory->createResponse(Status::NO_CONTENT);
            return $this->addCorsHeaders($request, $response, $allowOrigin);
        }

        return $this->addCorsHeaders($request, $handler->handle($request), $allowOrigin);
    }
}
```

**Config keys** (`app/cors` params, populated from `app.cors.*` env vars):
- `allowedOrigins` — list or `['*']` (forced to `['*']` in dev env)
- `allowedMethods` — default `['GET','POST','PUT','PATCH','DELETE','OPTIONS']`
- `allowedHeaders` — default `['Content-Type','Authorization']`
- `exposedHeaders` — emitted as `Access-Control-Expose-Headers`
- `maxAge` — default `3600`
- `allowCredentials` — when true, `Access-Control-Allow-Origin` echoes the origin instead of `*`

---

### 3. JwtMiddleware

**Purpose**: JWT authentication. Skips configured public paths; decodes the `Bearer` token via `JwtService`, builds an `Actor` via `ActorProvider`, and stores it in `CurrentUser` + the `actor` request attribute.

**Location**: `src/Shared/Core/Middleware/JwtMiddleware.php`

```php
final class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwtService,
        private ActorProvider $actorProvider,
        private CurrentUser $currentUser,
        private array $publicPaths = ['/', '/auth/login', '/auth/refresh'],
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        if ($this->isPublicPath($path)) {
            return $handler->handle($request);
        }

        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader === '') {
            // GodMode super-admin bypass, else:
            throw new UnauthorizedException(translate: Message::create(key: 'auth.header_missing'));
        }

        $token = \str_replace('Bearer ', '', $authHeader);

        try {
            $claims = $this->jwtService->decode($token);
            $actor  = $this->actorProvider->fromToken($claims);

            $this->currentUser->setActor($actor);
            $request = $request->withAttribute('actor', $actor);
        } catch (\Exception $e) {
            // GodMode bypass, else:
            throw new UnauthorizedException(
                translate: Message::create(key: 'auth.invalid_token', params: ['error' => $e->getMessage()])
            );
        }

        return $handler->handle($request);
    }
}
```

**DI** (`config/common/di/jwt.php`): `publicPaths` comes from `app/jwt` params (`app.jwt.publicPaths` env). `JwtService` gets `secret`/`algorithm`/`issuer`/`audience` from the same params.

---

### 4. RequestParamsMiddleware

**Purpose**: Merges query params + parsed body into a `RequestParams` object and stores it as the `payload` request attribute (plus `paginationConfig`). Attached to the `/v1` route group — see `config/common/routes.php`.

**Location**: `src/Shared/Core/Middleware/RequestParamsMiddleware.php`

```php
final class RequestParamsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $defaultPageSize = 50,
        private int $maxPageSize = 200
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Anonymous DataParserInterface merging query + body (body wins)
        $parser = new class($request) implements DataParserInterface { /* ... */ };

        $params = new RequestParams($parser, $this->defaultPageSize, $this->maxPageSize);

        $request = $request->withAttribute('paginationConfig', [
            'defaultPageSize' => $this->defaultPageSize,
            'maxPageSize'     => $this->maxPageSize,
        ]);
        $request = $request->withAttribute('payload', $params);

        return $handler->handle($request);
    }
}
```

**Configuration**: `app/pagination` params (`app.pagination.defaultPageSize` / `app.pagination.maxPageSize` env); DI factory in `config/common/di/middleware-di.php`. Downstream actions read `$request->getAttribute('payload')`. See the Request Processing Guide for the full flow.

---

### 5. RateLimitMiddleware

**Purpose**: In-memory sliding-window rate limiting, keyed by client IP and path group (`/v1/example*` → `example:{ip}`, `/v1/auth*` → `auth:{ip}`, everything else → `global:{ip}`).

**Location**: `src/Shared/Core/Middleware/RateLimitMiddleware.php`

```php
final class RateLimitMiddleware implements MiddlewareInterface
{
    private array $storage = [];

    public function __construct(int $maxRequests = 100, int $windowSize = 60) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $this->getClientIp($request); // X-Forwarded-For > X-Real-IP > Client-IP > REMOTE_ADDR
        $key      = $this->getCacheKey($clientIp, $request);

        // slide the window, count requests
        if ($currentCount >= $this->maxRequests) {
            throw new TooManyRequestsException(
                translate: Message::create(key: 'rate_limit.exceeded', params: [/* seconds, limit, reset, retry_after */])
            );
        }

        $response = $handler->handle($request);

        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) \max(0, $this->maxRequests - $currentCount - 1))
            ->withHeader('X-RateLimit-Reset', (string) ($now + $this->windowSize));
    }
}
```

**Configuration**: `app/rateLimit` params (`app.rateLimit.maxRequests` / `app.rateLimit.windowSize` env). The storage is a per-process PHP array — for multi-process production setups, replace/extend with `App\Infrastructure\Core\RateLimit\DatabaseRateLimiter`.

---

### 6. SecureHeadersMiddleware

**Purpose**: Adds security headers to every response. Config is a single array with `csp`, `permissions`, and `custom` sections.

**Location**: `src/Shared/Core/Middleware/SecureHeadersMiddleware.php`

```php
final class SecureHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(array $config = [])
    {
        $this->headers = \array_merge([
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-XSS-Protection'          => '1; mode=block',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Content-Security-Policy'   => $this->buildCsp($config['csp'] ?? []),
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Permissions-Policy'        => $this->buildPermissionsPolicy($config['permissions'] ?? []),
        ], $config['custom'] ?? []);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->headers as $name => $value) {
            if ($value !== null && \is_string($value)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }
}
```

**Configuration**: `app/secureHeaders` params in `config/common/params.php` — `csp` directives (defaults: `default-src 'self'`, `script-src 'self' 'unsafe-inline'`, `style-src 'self' 'unsafe-inline'`, `img-src 'self' data: https:`, `connect-src 'self'`), `permissions` feature list, and `custom` header overrides.

> A dedicated `HstsMiddleware` (`src/Infrastructure/Core/Security/HstsMiddleware.php`) also exists for standalone HSTS config (`app/hsts` params: `maxAge`, `includeSubDomains`, `preload`).

---

### 7. AccessMiddleware

**Purpose**: RBAC authorization. Reads the `permission` requirement from the route (`defaults['permission']` or the `CurrentRoute` argument) and checks it via `Yiisoft\Access\AccessCheckerInterface`.

**Location**: `src/Shared/Core/Middleware/AccessMiddleware.php`

```php
final class AccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private UrlMatcher $urlMatcher,
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $currentRoute = $request->getAttribute(CurrentRoute::class);

        if ($currentRoute === null) {
            // Router hasn't run yet — match manually via UrlMatcher
            $result     = $this->urlMatcher->match($request);
            $permission = $result->route()?->getData('defaults')['permission'] ?? null;
        } else {
            $permission = $currentRoute->getArgument('permission') ?? null;
        }

        if ($permission === null) {
            return $handler->handle($request); // route has no permission requirement
        }

        $actor = $this->currentUser->getActor();

        if ($actor === null) {
            throw new ForbiddenException(translate: Message::create(key: 'access.insufficient_permissions'));
        }

        $allowed = $this->accessChecker->userHasPermission(
            $actor->getId() ?? null,
            $permission,
            ['actor' => $actor]
        );

        if (!$allowed) {
            throw new ForbiddenException(translate: Message::create(key: 'access.insufficient_permissions'));
        }

        return $handler->handle($request);
    }
}
```

**Route permission declaration** (`config/common/routes.php`):

```php
Route::post('/example/create')
    ->action(ExampleV1\ExampleCreateAction::class)
    ->name('v1/example/create')
    ->defaults(['permission' => 'example.create']),
```

---

### 8. Monitoring & Utility Middleware

Located in `src/Infrastructure/Core/Monitoring/` and `src/Api/Shared/`; configured via `app/monitoring` params in `config/common/params.php` and DI factories in `config/common/di/middleware-di.php`:

- **`RequestIdMiddleware`** — assigns/propagates `X-Request-Id` (header name configurable).
- **`StructuredLoggingMiddleware`** — logs each request/response; options: `enabled`, `log_level`, `include_request_body`, `include_response_body`, `max_log_size`, `exclude_paths`, `exclude_status_codes`.
- **`MetricsMiddleware`** — tracks request count, response time, status codes, memory; options under `metrics`.
- **`ErrorMonitoringMiddleware`** — captures exceptions/errors; options under `error_monitoring` (`ignore_error_codes`, `include_stack_trace`, ...).
- **`NotFoundMiddleware`** (`src/Api/Shared/NotFoundMiddleware.php`) — terminal 404 handler placed after `Router`.

---

## 🔧 Middleware Configuration

### 1. **Application Stack**

The real pipeline lives in `config/web/di/application.php` (`Application` → `MiddlewareDispatcher::withMiddlewares()`), outermost first:

```php
'withMiddlewares()' => [
    [
        FormatDataResponseAsJson::class,
        static fn () => new ContentNegotiator([
            'application/json' => new JsonDataResponseFormatter(),
        ]),
        ErrorCatcher::class,
        static fn (ExceptionResponderFactory $factory) => $factory->create(),
        static fn () => new TrustedHostMiddleware(
            $params['app/trusted_hosts']['allowedHosts'] ?? [],
        ),
        CorsMiddleware::class,
        JwtMiddleware::class,
        RequestIdMiddleware::class,
        StructuredLoggingMiddleware::class,
        MetricsMiddleware::class,
        RateLimitMiddleware::class,
        SecureHeadersMiddleware::class,
        ErrorMonitoringMiddleware::class,
        RequestBodyParser::class,
        AccessMiddleware::class,
        Router::class,
        NotFoundMiddleware::class,
    ],
],
```

Route-level middleware is attached via groups in `config/common/routes.php`:

```php
Group::create('/v1')
    ->middleware(RequestParamsMiddleware::class)
    ->routes(/* ... */);
```

`config/common/middleware.php` returns an extra common stack (currently `[]`) for project-specific additions.

### 2. **DI Configuration**

Middleware factories live in `config/common/di/middleware-di.php` (params-driven) and `config/common/di/jwt.php`:

```php
// config/common/di/middleware-di.php (excerpt)
RequestParamsMiddleware::class => static function () use ($params) {
    $pagination = $params['app/pagination'] ?? [];
    return new RequestParamsMiddleware(
        defaultPageSize: (int) ($pagination['defaultPageSize'] ?? 50),
        maxPageSize: (int) ($pagination['maxPageSize'] ?? 200),
    );
},

CorsMiddleware::class => static fn (ResponseFactoryInterface $responseFactory) =>
    new CorsMiddleware($params['app/cors'], $responseFactory),

RateLimitMiddleware::class => static function () use ($params) {
    $rateLimit = $params['app/rateLimit'] ?? [];
    return new RateLimitMiddleware(
        maxRequests: (int) ($rateLimit['maxRequests'] ?? 100),
        windowSize: (int) ($rateLimit['windowSize'] ?? 60)
    );
},

SecureHeadersMiddleware::class => static fn () =>
    new SecureHeadersMiddleware($params['app/secureHeaders'] ?? []),

AccessMiddleware::class => static fn (
    AccessChecker $accessChecker,
    CurrentUser $currentUser,
    UrlMatcher $urlMatcher,
) => new AccessMiddleware($accessChecker, $currentUser, $urlMatcher),
```

```php
// config/common/di/jwt.php (excerpt)
JwtMiddleware::class => static fn (
    JwtService $jwtService,
    ActorProvider $actorProvider,
    CurrentUser $currentUser
) => new JwtMiddleware(
    jwtService: $jwtService,
    actorProvider: $actorProvider,
    currentUser: $currentUser,
    publicPaths: $params['app/jwt']['publicPaths'] ?? [],
),
```

---

## 🚀 Best Practices

### 1. **Middleware Order**
```php
// ✅ Correct order (outermost first) — as wired in config/web/di/application.php
// 1. ErrorCatcher / ExceptionResponder   // must wrap everything
// 2. TrustedHostMiddleware               // host validation
// 3. CorsMiddleware                      // preflight before auth
// 4. JwtMiddleware                       // authentication
// 5. Monitoring (RequestId, Logging, Metrics)
// 6. RateLimitMiddleware                 // throttle authenticated traffic
// 7. SecureHeadersMiddleware
// 8. RequestBodyParser
// 9. AccessMiddleware                    // needs actor from JwtMiddleware
// 10. Router -> NotFoundMiddleware

// ❌ Wrong order
// JwtMiddleware before CorsMiddleware — preflight OPTIONS would 401
```

### 2. **Error Handling**
```php
// ✅ Throw typed exceptions — the exception responder renders JSON
throw new UnauthorizedException(translate: Message::create(key: 'auth.header_missing'));

// ❌ Build ad-hoc error responses inside middleware
$response = new Response();
$response->getBody()->write(json_encode(['error' => 'x']));
return $response->withStatus(401);
```

### 3. **Performance**
```php
// ✅ Early returns / cheap checks first
if ($origin === '') {
    return $handler->handle($request); // no CORS work needed
}

// ❌ Heavy work before deciding the middleware applies
```

---

## 📊 Performance Considerations

### 1. **Middleware Overhead**
- Keep middleware lightweight
- `RateLimitMiddleware` storage is in-memory (per process) — swap in `DatabaseRateLimiter` for shared limits
- Monitoring middleware is config-gated (`enabled` flags)

### 2. **Execution Order**
- Place cheap rejectors (host, CORS preflight, rate limit) early
- `AccessMiddleware` runs after `RequestBodyParser` and before `Router` — it can fall back to `UrlMatcher` when `CurrentRoute` isn't set yet

### 3. **Memory Usage**
- Avoid storing large objects in middleware state
- `$this->storage` in `RateLimitMiddleware` grows per-process — bound it in long-running workers

---

## 🎯 Summary

Middleware provides a clean, composable way to handle cross-cutting concerns in the Yii3 API application. Key benefits include:

- **🔧 Modularity**: Each middleware handles one concern
- **🔄 Reusability**: Middleware can be reused across routes and groups
- **🧪 Testability**: Easy to unit test individual components
- **⚡ Performance**: Efficient request processing pipeline
- **🛡️ Security**: Centralized security handling with typed exceptions
- **📦 Composability**: Global stack in `config/web/di/application.php`, group stack in `config/common/routes.php`

By following the patterns and best practices outlined in this guide, you can build robust, maintainable middleware for your Yii3 API application! 🚀
