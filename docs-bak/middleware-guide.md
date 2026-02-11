# Middleware Guide

## 📋 Overview

Middleware components provide a way to filter HTTP requests entering your application. In this Yii3 API application, middleware handles cross-cutting concerns like authentication, CORS, rate limiting, security headers, and request processing.

---

## 🏗️ Middleware Architecture

### Directory Structure

```
src/Shared/Middleware/
├── AccessMiddleware.php         # Access control and permissions
├── CorsMiddleware.php           # Cross-Origin Resource Sharing
├── JwtMiddleware.php           # JWT authentication
├── RateLimitMiddleware.php     # Rate limiting
├── RequestParamsMiddleware.php # Request parameter processing
├── SecureHeadersMiddleware.php # Security headers
└── TrustedHostMiddleware.php   # Trusted host validation
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

#### **4. **Dependency Injection**
- Middleware components are DI-friendly
- Easy to mock and test
- Configurable behavior

---

## 📁 Middleware Components

### 1. AccessMiddleware

**Purpose**: Access control and permission checking

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Security\AuthorizerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Access Control Middleware
 */
final class AccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AuthorizerInterface $authorizer,
        private TranslatorInterface $translator
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $user = $request->getAttribute('user');
        $route = $request->getAttribute('route');
        
        if ($user === null) {
            return $this->createErrorResponse(
                $this->translator->translate('access.auth_required', [], 'error'),
                Status::UNAUTHORIZED
            );
        }

        if ($route === null) {
            return $handler->handle($request);
        }

        $permission = $this->getPermissionFromRoute($route);
        
        if (!$this->authorizer->can($user, $permission)) {
            return $this->createErrorResponse(
                $this->translator->translate('access.insufficient_permissions', [], 'error'),
                Status::FORBIDDEN
            );
        }

        return $handler->handle($request);
    }

    private function getPermissionFromRoute(string $route): string
    {
        // Convert route to permission
        // Example: /api/v1/users -> users.read
        // Example: /api/v1/users/{id} -> users.read
        // Example: POST /api/v1/users -> users.create
        
        $parts = explode('/', trim($route, '/'));
        $resource = $parts[2] ?? 'unknown';
        $action = $this->getActionFromMethod($request->getMethod());
        
        return "{$resource}.{$action}";
    }

    private function getActionFromMethod(string $method): string
    {
        return match ($method) {
            'GET' => 'read',
            'POST' => 'create',
            'PUT', 'PATCH' => 'update',
            'DELETE' => 'delete',
            default => 'unknown'
        };
    }

    private function createErrorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => [
                'message' => $message,
                'status' => $status
            ]
        ]));
        
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    AccessMiddleware::class,
    // ... other middleware
];

// In DI configuration
AccessMiddleware::class => [
    '__construct()' => [
        'authorizer' => Reference::to(AuthorizerInterface::class),
        'translator' => Reference::to(TranslatorInterface::class),
    ],
];
```

---

### 2. CorsMiddleware

**Purpose**: Cross-Origin Resource Sharing (CORS) handling

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * CORS Middleware
 */
final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $allowedOrigins = ['*'],
        private array $allowedMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
        private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-Requested-With'],
        private bool $allowCredentials = false,
        private int $maxAge = 86400
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $origin = $request->getHeaderLine('Origin');
        $method = $request->getMethod();
        
        // Handle preflight requests
        if ($method === 'OPTIONS') {
            return $this->createPreflightResponse($origin);
        }

        $response = $handler->handle($request);
        
        // Add CORS headers to actual response
        return $this->addCorsHeaders($response, $origin);
    }

    private function createPreflightResponse(string $origin): ResponseInterface
    {
        $response = new Response();
        
        if ($this->isOriginAllowed($origin)) {
            $response = $response
                ->withHeader('Access-Control-Allow-Origin', $origin)
                ->withHeader('Access-Control-Allow-Methods', implode(', ', $this->allowedMethods))
                ->withHeader('Access-Control-Allow-Headers', implode(', ', $this->allowedHeaders))
                ->withHeader('Access-Control-Max-Age', (string) $this->maxAge);
                
            if ($this->allowCredentials) {
                $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
            }
        }
        
        return $response->withStatus(Status::NO_CONTENT);
    }

    private function addCorsHeaders(ResponseInterface $response, string $origin): ResponseInterface
    {
        if (!$this->isOriginAllowed($origin)) {
            return $response;
        }

        $response = $response->withHeader('Access-Control-Allow-Origin', $origin);
        
        if ($this->allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        // Add Vary header for proper caching
        return $response->withHeader('Vary', 'Origin');
    }

    private function isOriginAllowed(string $origin): bool
    {
        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    CorsMiddleware::class => [
        'allowedOrigins' => ['https://example.com', 'https://app.example.com'],
        'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowedHeaders' => ['Content-Type', 'Authorization'],
        'allowCredentials' => true,
        'maxAge' => 7200,
    ],
    // ... other middleware
];
```

---

### 3. JwtMiddleware

**Purpose**: JWT authentication and token validation

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Security\JwtService;
use App\Shared\Security\ActorProviderInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * JWT Authentication Middleware
 */
final class JwtMiddleware implements MiddlewareInterface
{
    public function __construct(
        private JwtService $jwtService,
        private ActorProviderInterface $actorProvider,
        private TranslatorInterface $translator
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $token = $this->extractToken($request);
        
        if ($token === null) {
            return $this->createErrorResponse(
                $this->translator->translate('auth.header_missing', [], 'error'),
                Status::UNAUTHORIZED
            );
        }

        try {
            $payload = $this->jwtService->validate($token);
            $user = $this->actorProvider->findByToken($payload);
            
            if ($user === null) {
                return $this->createErrorResponse(
                    $this->translator->translate('auth.invalid_token', [], 'error'),
                    Status::UNAUTHORIZED
                );
            }

            // Add user to request attributes
            $request = $request->withAttribute('user', $user);
            $request = $request->withAttribute('token_payload', $payload);

            return $handler->handle($request);
            
        } catch (\Exception $e) {
            return $this->createErrorResponse(
                $this->translator->translate('auth.invalid_token', [], 'error'),
                Status::UNAUTHORIZED
            );
        }
    }

    private function extractToken(ServerRequestInterface $request): ?string
    {
        // Extract from Authorization header
        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader && str_starts_with($authHeader, 'Bearer ')) {
            return substr($authHeader, 7);
        }

        // Extract from query parameter (for WebSocket connections)
        $tokenParam = $request->getQueryParams()['token'] ?? null;
        if ($tokenParam && is_string($tokenParam)) {
            return $tokenParam;
        }

        // Extract from cookie
        $cookieParam = $request->getCookieParams()['jwt'] ?? null;
        if ($cookieParam && is_string($cookieParam)) {
            return $cookieParam;
        }

        return null;
    }

    private function createErrorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => [
                'message' => $message,
                'status' => $status
            ]
        ]));
        
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    JwtMiddleware::class,
    // ... other middleware
];

// In DI configuration
JwtMiddleware::class => [
    '__construct()' => [
        'jwtService' => Reference::to(JwtService::class),
        'actorProvider' => Reference::to(ActorProviderInterface::class),
        'translator' => Reference::to(TranslatorInterface::class),
    ],
];
```

---

### 4. RateLimitMiddleware

**Purpose**: Rate limiting to prevent abuse

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Cache\CacheInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Rate Limiting Middleware
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    public function __construct(
        private CacheInterface $cache,
        private TranslatorInterface $translator,
        private int $requests = 100,
        private int $window = 3600, // 1 hour
        private int $burst = 10
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $identifier = $this->getIdentifier($request);
        $key = "rate_limit:{$identifier}";
        
        $current = $this->cache->get($key, 0);
        
        if ($current >= $this->requests) {
            return $this->createRateLimitResponse();
        }

        // Increment counter
        $this->cache->set($key, $current + 1, $this->window);
        
        // Add rate limit headers
        $response = $handler->handle($request);
        return $this->addRateLimitHeaders($response, $current + 1);
    }

    private function getIdentifier(ServerRequestInterface $request): string
    {
        // Try to get user ID first
        $user = $request->getAttribute('user');
        if ($user && method_exists($user, 'getId')) {
            return 'user:' . $user->getId();
        }

        // Fall back to IP address
        $ip = $request->getServerParams()['REMOTE_ADDR'] ?? 'unknown';
        return 'ip:' . $ip;
    }

    private function createRateLimitResponse(): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => [
                'message' => $this->translator->translate('rate_limit.exceeded', [], 'error'),
                'status' => Status::TOO_MANY_REQUESTS
            ]
        ]));
        
        return $response
            ->withStatus(Status::TOO_MANY_REQUESTS)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) $this->window)
            ->withHeader('X-RateLimit-Limit', (string) $this->requests)
            ->withHeader('X-RateLimit-Remaining', '0')
            ->withHeader('X-RateLimit-Reset', (string) (time() + $this->window));
    }

    private function addRateLimitHeaders(ResponseInterface $response, int $current): ResponseInterface
    {
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->requests)
            ->withHeader('X-RateLimit-Remaining', (string) max(0, $this->requests - $current))
            ->withHeader('X-RateLimit-Reset', (string) (time() + $this->window));
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    RateLimitMiddleware::class => [
        'requests' => 100,
        'window' => 3600,
        'burst' => 10,
    ],
    // ... other middleware
];
```

---

### 5. RequestParamsMiddleware

**Purpose**: Request parameter processing and validation

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Request Parameters Middleware
 */
final class RequestParamsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private TranslatorInterface $translator
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $parsedBody = $request->getParsedBody();
        
        // Validate JSON body
        if ($this->isJsonRequest($request)) {
            if (!is_array($parsedBody)) {
                return $this->createErrorResponse(
                    $this->translator->translate('request.invalid_json', [], 'error'),
                    Status::BAD_REQUEST
                );
            }
        }

        // Process and normalize parameters
        $processedParams = $this->processParams($request);
        
        // Add processed parameters to request
        $request = $request->withAttribute('params', $processedParams);

        return $handler->handle($request);
    }

    private function isJsonRequest(ServerRequestInterface $request): bool
    {
        $contentType = $request->getHeaderLine('Content-Type');
        return str_contains($contentType, 'application/json');
    }

    private function processParams(ServerRequestInterface $request): array
    {
        $params = [];
        
        // Merge query parameters
        $params = array_merge($params, $request->getQueryParams());
        
        // Merge body parameters
        $parsedBody = $request->getParsedBody();
        if (is_array($parsedBody)) {
            $params = array_merge($params, $parsedBody);
        }

        // Normalize boolean values
        $params = $this->normalizeBooleans($params);
        
        // Normalize null values
        $params = $this->normalizeNulls($params);
        
        // Trim string values
        $params = $this->trimStrings($params);
        
        return $params;
    }

    private function normalizeBooleans(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $lower = strtolower($value);
                if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                    $params[$key] = true;
                } elseif (in_array($lower, ['false', '0', 'no', 'off'], true)) {
                    $params[$key] = false;
                }
            }
        }
        
        return $params;
    }

    private function normalizeNulls(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_string($value) && $value === '') {
                $params[$key] = null;
            }
        }
        
        return $params;
    }

    private function trimStrings(array $params): array
    {
        foreach ($params as $key => $value) {
            if (is_string($value)) {
                $params[$key] = trim($value);
            }
        }
        
        return $params;
    }

    private function createErrorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => [
                'message' => $message,
                'status' => $status
            ]
        ]));
        
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    RequestParamsMiddleware::class,
    // ... other middleware
];
```

---

### 6. SecureHeadersMiddleware

**Purpose**: Security headers for HTTP responses

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Security Headers Middleware
 */
final class SecureHeadersMiddleware implements MiddlewareInterface
{
    public function __construct(
        private bool $enableHsts = true,
        private bool $enableCsp = true,
        private bool $enableXFrameOptions = true,
        private bool $enableXContentTypeOptions = true,
        private bool $enableXssProtection = true,
        private bool $enableReferrerPolicy = true,
        private int $hstsMaxAge = 31536000, // 1 year
        private bool $hstsIncludeSubdomains = true,
        private bool $hstsPreload = false,
        private string $cspPolicy = "default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data: https:; font-src 'self'; connect-src 'self'; frame-ancestors 'none';"
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        $response = $handler->handle($request);
        
        return $this->addSecurityHeaders($response);
    }

    private function addSecurityHeaders(ResponseInterface $response): ResponseInterface
    {
        // Strict-Transport-Security (HSTS)
        if ($this->enableHsts) {
            $hstsValue = "max-age={$this->hstsMaxAge}";
            
            if ($this->hstsIncludeSubdomains) {
                $hstsValue .= '; includeSubDomains';
            }
            
            if ($this->hstsPreload) {
                $hstsValue .= '; preload';
            }
            
            $response = $response->withHeader('Strict-Transport-Security', $hstsValue);
        }

        // Content-Security-Policy (CSP)
        if ($this->enableCsp) {
            $response = $response->withHeader('Content-Security-Policy', $this->cspPolicy);
        }

        // X-Frame-Options
        if ($this->enableXFrameOptions) {
            $response = $response->withHeader('X-Frame-Options', 'DENY');
        }

        // X-Content-Type-Options
        if ($this->enableXContentTypeOptions) {
            $response = $response->withHeader('X-Content-Type-Options', 'nosniff');
        }

        // X-XSS-Protection
        if ($this->enableXssProtection) {
            $response = $response->withHeader('X-XSS-Protection', '1; mode=block');
        }

        // Referrer-Policy
        if ($this->enableReferrerPolicy) {
            $response = $response->withHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        }

        // Additional security headers
        $response = $response
            ->withHeader('X-Permitted-Cross-Domain-Policies', 'none')
            ->withHeader('X-Download-Options', 'noopen')
            ->withHeader('X-Content-Security-Policy', $this->cspPolicy)
            ->withHeader('X-WebKit-CSP', $this->cspPolicy);

        // Remove server information
        $response = $response
            ->withoutHeader('Server')
            ->withoutHeader('X-Powered-By');

        return $response;
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    SecureHeadersMiddleware::class => [
        'enableHsts' => true,
        'enableCsp' => true,
        'hstsMaxAge' => 31536000,
        'cspPolicy' => "default-src 'self'; script-src 'self';",
    ],
    // ... other middleware
];
```

---

### 7. TrustedHostMiddleware

**Purpose**: Trusted host validation for security

```php
<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Trusted Host Middleware
 */
final class TrustedHostMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $trustedHosts = [],
        private TranslatorInterface $translator
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        if (empty($this->trustedHosts)) {
            return $handler->handle($request);
        }

        $host = $this->getHost($request);
        
        if (!$this->isTrustedHost($host)) {
            return $this->createErrorResponse(
                $this->translator->translate('security.host_not_allowed', [], 'error'),
                Status::BAD_REQUEST
            );
        }

        return $handler->handle($request);
    }

    private function getHost(ServerRequestInterface $request): string
    {
        // Try to get host from different sources
        $host = $request->getHeaderLine('Host');
        
        if (empty($host)) {
            $host = $request->getServerParams()['HTTP_HOST'] ?? '';
        }
        
        if (empty($host)) {
            $host = $request->getServerParams()['SERVER_NAME'] ?? '';
        }
        
        return strtolower($host);
    }

    private function isTrustedHost(string $host): bool
    {
        foreach ($this->trustedHosts as $trustedHost) {
            if ($this->matchesHost($host, $trustedHost)) {
                return true;
            }
        }
        
        return false;
    }

    private function matchesHost(string $host, string $pattern): bool
    {
        // Exact match
        if ($host === $pattern) {
            return true;
        }
        
        // Wildcard match
        if (str_contains($pattern, '*')) {
            $regex = '/^' . str_replace('*', '.*', preg_quote($pattern, '/')) . '$/';
            return preg_match($regex, $host) === 1;
        }
        
        return false;
    }

    private function createErrorResponse(string $message, int $status): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => [
                'message' => $message,
                'status' => $status
            ]
        ]));
        
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

**Usage Example**:
```php
// In middleware configuration
'middleware' => [
    TrustedHostMiddleware::class => [
        'trustedHosts' => [
            'example.com',
            'api.example.com',
            '*.example.org',
        ],
    ],
    // ... other middleware
];
```

---

## 🔧 Middleware Configuration

### 1. **Application Configuration**
```php
// config/web/middleware.php
return [
    'middleware' => [
        // Global middleware (applies to all routes)
        TrustedHostMiddleware::class => [
            'trustedHosts' => ['api.example.com', '*.example.org'],
        ],
        SecureHeadersMiddleware::class,
        CorsMiddleware::class => [
            'allowedOrigins' => ['https://app.example.com'],
            'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
            'allowedHeaders' => ['Content-Type', 'Authorization'],
            'allowCredentials' => true,
        ],
        
        // Route-specific middleware
        'api' => [
            RequestParamsMiddleware::class,
            JwtMiddleware::class,
            RateLimitMiddleware::class => [
                'requests' => 100,
                'window' => 3600,
            ],
            AccessMiddleware::class,
        ],
        
        // Public routes (no auth required)
        'public' => [
            RateLimitMiddleware::class => [
                'requests' => 10,
                'window' => 60,
            ],
        ],
    ],
];
```

### 2. **DI Configuration**
```php
// config/web/di/middleware.php
return [
    AccessMiddleware::class => [
        '__construct()' => [
            'authorizer' => Reference::to(AuthorizerInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],
    JwtMiddleware::class => [
        '__construct()' => [
            'jwtService' => Reference::to(JwtService::class),
            'actorProvider' => Reference::to(ActorProviderInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],
    RateLimitMiddleware::class => [
        '__construct()' => [
            'cache' => Reference::to(CacheInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],
];
```

---

## 🚀 Best Practices

### 1. **Middleware Order**
```php
// ✅ Correct order (outside to inside)
1. TrustedHostMiddleware        // Security validation
2. SecureHeadersMiddleware      // Security headers
3. CorsMiddleware              // CORS handling
4. RateLimitMiddleware         // Rate limiting
5. RequestParamsMiddleware     // Request processing
6. JwtMiddleware               // Authentication
7. AccessMiddleware            // Authorization

// ❌ Wrong order
1. JwtMiddleware              // Can't validate without CORS
2. CorsMiddleware             // Headers already sent
```

### 2. **Error Handling**
```php
// ✅ Handle errors gracefully
public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {
    try {
        return $handler->handle($request);
    } catch (\Exception $e) {
        return $this->createErrorResponse($e);
    }
}

// ❌ Let exceptions bubble up
public function process(
    ServerRequestInterface $request,
    RequestHandlerInterface $handler
): ResponseInterface {
    return $handler->handle($request); // Exceptions not handled
}
```

### 3. **Performance**
```php
// ✅ Early returns for efficiency
if (!$this->isValidRequest($request)) {
    return $this->createErrorResponse();
}

return $handler->handle($request);

// ❌ Unnecessary processing
$processed = $this->processRequest($request);
if (!$processed->isValid()) {
    return $this->createErrorResponse();
}
return $handler->handle($request);
```

---

## 📊 Performance Considerations

### 1. **Middleware Overhead**
- Keep middleware lightweight
- Avoid heavy computations
- Use caching where appropriate

### 2. **Execution Order**
- Place expensive middleware last
- Use early returns for failures
- Minimize request processing time

### 3. **Memory Usage**
- Avoid storing large objects
- Use dependency injection efficiently
- Clean up resources properly

---

## 🎯 Summary

Middleware provides a clean, composable way to handle cross-cutting concerns in the Yii3 API application. Key benefits include:

- **🔧 Modularity**: Each middleware handles one concern
- **🔄 Reusability**: Middleware can be reused across routes
- **🧪 Testability**: Easy to unit test individual components
- **⚡ Performance**: Efficient request processing pipeline
- **🛡️ Security**: Centralized security handling
- **📦 Composability**: Flexible middleware chaining

By following the patterns and best practices outlined in this guide, you can build robust, maintainable middleware for your Yii3 API application! 🚀
