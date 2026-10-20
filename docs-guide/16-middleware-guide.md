# Panduan Middleware

## 📋 Gambaran Umum

Komponen middleware menyediakan cara untuk memfilter request HTTP yang masuk ke aplikasi Anda. Dalam aplikasi Yii3 API ini, middleware menangani cross-cutting concern seperti autentikasi, CORS, rate limiting, security header, monitoring, dan pemrosesan parameter request.

Semua middleware aplikasi melempar subclass `HttpException` bertipe (`UnauthorizedException`, `ForbiddenException`, `TooManyRequestsException`, `BadRequestException`) alih-alih membangun response secara inline — exception dirender oleh responder `ExceptionResponderFactory` yang terdaftar di middleware dispatcher.

---

## 🏗️ Arsitektur Middleware

### Struktur Direktori

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

### Prinsip Desain

#### **1. **Single Responsibility**
- Setiap middleware menangani satu concern spesifik
- Pemisahan tanggung jawab yang jelas
- Mudah diuji dan dipelihara

#### **2. **Composability**
- Middleware dapat dirangkai satu sama lain
- Pipeline pemrosesan request yang fleksibel
- Urutan eksekusi yang dapat dikonfigurasi

#### **3. **Kepatuhan PSR**
- Interface pesan HTTP PSR-7
- Interface middleware PSR-15
- Implementasi yang terstandarisasi

#### **4. **Error Berbasis Exception**
- Middleware melempar subclass `HttpException` dengan key terjemahan `Message`
- Satu exception responder merender response error JSON yang konsisten

---

## 📁 Komponen Middleware

### 1. TrustedHostMiddleware

**Tujuan**: Memvalidasi `Host` request terhadap allow-list, mencegah host-header injection. Mendukung host persis dan wildcard subdomain `*.`.

**Lokasi**: `src/Shared/Core/Middleware/TrustedHostMiddleware.php`

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

**Konfigurasi**: `allowedHosts` berasal dari params `app/trusted_hosts` (env `app.trusted_hosts.allowedHosts`, array JSON). Didaftarkan inline di `config/web/di/application.php`.

> Catatan: `Yiisoft\Security\TrustedHosts\TrustedHostsMiddleware` juga didefinisikan di `config/common/di/middleware-di.php` sebagai alternatif dari vendor; stack aplikasi saat ini menggunakan `App\...\TrustedHostMiddleware` kustom.

---

### 2. CorsMiddleware

**Tujuan**: Penanganan CORS — memvalidasi `Origin`, menjawab preflight `OPTIONS` dengan `204`, dan menambahkan header CORS ke response.

**Lokasi**: `src/Shared/Core/Middleware/CorsMiddleware.php`

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

**Key konfigurasi** (params `app/cors`, diisi dari env var `app.cors.*`):
- `allowedOrigins` — list atau `['*']` (dipaksa menjadi `['*']` di env dev)
- `allowedMethods` — default `['GET','POST','PUT','PATCH','DELETE','OPTIONS']`
- `allowedHeaders` — default `['Content-Type','Authorization']`
- `exposedHeaders` — dikirim sebagai `Access-Control-Expose-Headers`
- `maxAge` — default `3600`
- `allowCredentials` — jika true, `Access-Control-Allow-Origin` menggemakan origin alih-alih `*`

---

### 3. JwtMiddleware

**Tujuan**: Autentikasi JWT. Melewati public path yang dikonfigurasi; mendekode token `Bearer` via `JwtService`, membangun `Actor` via `ActorProvider`, dan menyimpannya di `CurrentUser` + atribut request `actor`.

**Lokasi**: `src/Shared/Core/Middleware/JwtMiddleware.php`

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

**DI** (`config/common/di/jwt.php`): `publicPaths` berasal dari params `app/jwt` (env `app.jwt.publicPaths`). `JwtService` mendapatkan `secret`/`algorithm`/`issuer`/`audience` dari params yang sama.

---

### 4. RequestParamsMiddleware

**Tujuan**: Menggabungkan query param + body yang sudah di-parse ke dalam objek `RequestParams` dan menyimpannya sebagai atribut request `payload` (plus `paginationConfig`). Dipasang ke route group `/v1` — lihat `config/common/routes.php`.

**Lokasi**: `src/Shared/Core/Middleware/RequestParamsMiddleware.php`

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

**Konfigurasi**: params `app/pagination` (env `app.pagination.defaultPageSize` / `app.pagination.maxPageSize`); factory DI di `config/common/di/middleware-di.php`. Action di hilir membaca `$request->getAttribute('payload')`. Lihat Panduan Request Processing untuk alur lengkapnya.

---

### 5. RateLimitMiddleware

**Tujuan**: Rate limiting sliding-window di memori, dikunci berdasarkan IP klien dan grup path (`/v1/example*` → `example:{ip}`, `/v1/auth*` → `auth:{ip}`, lainnya → `global:{ip}`).

**Lokasi**: `src/Shared/Core/Middleware/RateLimitMiddleware.php`

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

**Konfigurasi**: params `app/rateLimit` (env `app.rateLimit.maxRequests` / `app.rateLimit.windowSize`). Penyimpanannya berupa array PHP per proses — untuk setup production multi-proses, ganti/perluas dengan `App\Infrastructure\Core\RateLimit\DatabaseRateLimiter`.

---

### 6. SecureHeadersMiddleware

**Tujuan**: Menambahkan security header ke setiap response. Konfigurasinya berupa satu array dengan bagian `csp`, `permissions`, dan `custom`.

**Lokasi**: `src/Shared/Core/Middleware/SecureHeadersMiddleware.php`

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

**Konfigurasi**: params `app/secureHeaders` di `config/common/params.php` — directive `csp` (default: `default-src 'self'`, `script-src 'self' 'unsafe-inline'`, `style-src 'self' 'unsafe-inline'`, `img-src 'self' data: https:`, `connect-src 'self'`), daftar fitur `permissions`, dan override header `custom`.

> `HstsMiddleware` khusus (`src/Infrastructure/Core/Security/HstsMiddleware.php`) juga tersedia untuk konfigurasi HSTS mandiri (params `app/hsts`: `maxAge`, `includeSubDomains`, `preload`).

---

### 7. AccessMiddleware

**Tujuan**: Otorisasi RBAC. Membaca requirement `permission` dari route (`defaults['permission']` atau argumen `CurrentRoute`) dan memeriksanya via `Yiisoft\Access\AccessCheckerInterface`.

**Lokasi**: `src/Shared/Core/Middleware/AccessMiddleware.php`

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

**Deklarasi permission route** (`config/common/routes.php`):

```php
Route::post('/example/create')
    ->action(ExampleV1\ExampleCreateAction::class)
    ->name('v1/example/create')
    ->defaults(['permission' => 'example.create']),
```

---

### 8. Middleware Monitoring & Utilitas

Berada di `src/Infrastructure/Core/Monitoring/` dan `src/Api/Shared/`; dikonfigurasi via params `app/monitoring` di `config/common/params.php` dan factory DI di `config/common/di/middleware-di.php`:

- **`RequestIdMiddleware`** — menetapkan/meneruskan `X-Request-Id` (nama header dapat dikonfigurasi).
- **`StructuredLoggingMiddleware`** — mencatat setiap request/response; opsi: `enabled`, `log_level`, `include_request_body`, `include_response_body`, `max_log_size`, `exclude_paths`, `exclude_status_codes`.
- **`MetricsMiddleware`** — melacak jumlah request, waktu response, status code, memori; opsi di bawah `metrics`.
- **`ErrorMonitoringMiddleware`** — menangkap exception/error; opsi di bawah `error_monitoring` (`ignore_error_codes`, `include_stack_trace`, ...).
- **`NotFoundMiddleware`** (`src/Api/Shared/NotFoundMiddleware.php`) — handler 404 terminal yang ditempatkan setelah `Router`.

---

## 🔧 Konfigurasi Middleware

### 1. **Stack Aplikasi**

Pipeline sebenarnya berada di `config/web/di/application.php` (`Application` → `MiddlewareDispatcher::withMiddlewares()`), terluar lebih dulu:

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

Middleware level route dipasang via group di `config/common/routes.php`:

```php
Group::create('/v1')
    ->middleware(RequestParamsMiddleware::class)
    ->routes(/* ... */);
```

`config/common/middleware.php` mengembalikan stack common tambahan (saat ini `[]`) untuk penambahan spesifik proyek.

### 2. **Konfigurasi DI**

Factory middleware berada di `config/common/di/middleware-di.php` (digerakkan params) dan `config/common/di/jwt.php`:

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

## 🚀 Praktik Terbaik

### 1. **Urutan Middleware**
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

### 3. **Performa**
```php
// ✅ Early returns / cheap checks first
if ($origin === '') {
    return $handler->handle($request); // no CORS work needed
}

// ❌ Heavy work before deciding the middleware applies
```

---

## 📊 Pertimbangan Performa

### 1. **Overhead Middleware**
- Jaga middleware tetap ringan
- Penyimpanan `RateLimitMiddleware` berada di memori (per proses) — ganti dengan `DatabaseRateLimiter` untuk limit bersama
- Middleware monitoring dikendalikan konfigurasi (flag `enabled`)

### 2. **Urutan Eksekusi**
- Tempatkan penolak murah (host, preflight CORS, rate limit) di awal
- `AccessMiddleware` berjalan setelah `RequestBodyParser` dan sebelum `Router` — ia dapat kembali ke `UrlMatcher` ketika `CurrentRoute` belum diset

### 3. **Penggunaan Memori**
- Hindari menyimpan objek besar di state middleware
- `$this->storage` di `RateLimitMiddleware` bertambah per proses — batasi di worker yang berjalan lama

---

## 🎯 Ringkasan

Middleware menyediakan cara yang bersih dan composable untuk menangani cross-cutting concern dalam aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🔧 Modularitas**: Setiap middleware menangani satu concern
- **🔄 Reusability**: Middleware dapat dipakai ulang di berbagai route dan group
- **🧪 Testability**: Mudah melakukan unit test pada komponen individual
- **⚡ Performa**: Pipeline pemrosesan request yang efisien
- **🛡️ Keamanan**: Penanganan keamanan terpusat dengan exception bertipe
- **📦 Composability**: Stack global di `config/web/di/application.php`, stack group di `config/common/routes.php`

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun middleware yang tangguh dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
