# Security Guide

## 📋 Overview

Security utilities provide essential security functions for the Yii3 API application, including input sanitization, JWT authentication, permission-based authorization, and protection against common security vulnerabilities.

---

## 🏗️ Security Architecture

### Directory Structure

```
src/Shared/Core/Security/
└── InputSanitizer.php              # Static input sanitization (XSS / SQLi / DoS guards)

src/Shared/Core/Middleware/
├── AccessMiddleware.php            # Per-route permission enforcement
├── CorsMiddleware.php              # CORS handling, rejects disallowed origins
├── JwtMiddleware.php               # JWT bearer-token authentication
├── RateLimitMiddleware.php         # In-memory rate limiting
├── RequestParamsMiddleware.php     # Parses filter/sort/pagination params
├── SecureHeadersMiddleware.php     # CSP, X-Frame-Options, etc.
└── TrustedHostMiddleware.php       # Host header validation

src/Infrastructure/Core/Security/
├── AccessChecker.php               # Yiisoft AccessCheckerInterface implementation
├── Actor.php                       # Authenticated user value (implements ActorInterface)
├── ActorProvider.php               # Builds an Actor from JWT claims
├── CurrentUser.php                 # Holds the current Actor (implements CurrentUserInterface)
├── CurrentUserAwareInterface.php   # Marks classes that need CurrentUser injected
├── HstsMiddleware.php              # Strict-Transport-Security header
├── JwtService.php                  # firebase/jwt decode + iss/aud validation
├── PermissionChecker.php           # Evaluates rules from config/common/access.php
├── RbacAuthorizer.php              # AuthorizerInterface implementation
└── Rule/PermissionMapRule.php      # Yiisoft Access rule backed by the access map

src/Domain/Shared/Core/Contract/
├── ActorInterface.php              # getId(), getUsername(), getDept(), hasRole(), isAdmin(), isSuperAdmin()
├── CurrentUserInterface.php        # getActor(): ActorInterface
└── DateTimeProviderInterface.php

src/Domain/Shared/Core/Security/
└── AuthorizerInterface.php         # can(string $permission): bool
```

### Design Principles

#### **1. Defense in Depth**
- Input sanitization (`InputSanitizer`) at the request boundary
- JWT authentication (`JwtMiddleware`) before actions run
- Permission checks (`AccessMiddleware` + `config/common/access.php`) per route
- Trusted host, CORS, secure headers, and rate-limit middleware

#### **2. Fail-Safe Defaults**
- Unknown routes' permissions default to **deny** (`PermissionChecker::can()` returns `false` for unregistered permissions)
- `JwtMiddleware` throws `UnauthorizedException` when the `Authorization` header is missing or the token is invalid
- `TrustedHostMiddleware` throws `UnauthorizedException` for disallowed hosts

#### **3. Sanitize Input, Escape Output**
- `InputSanitizer` removes suspicious content but **does not HTML-encode** — escape on output instead
- Empty strings are normalized to `null`; depth/size limits prevent DoS via crafted payloads

#### **4. Configuration over Code**
- Permission map lives in `config/common/access.php`
- JWT secrets/paths, CORS, rate limit, HSTS, and trusted hosts are env-driven via `config/common/params.php`

---

## 📁 Security Components

### 1. InputSanitizer

**Location**: `src/Shared/Core/Security/InputSanitizer.php`

**Purpose**: Static, recursive input sanitization to prevent XSS, SQL-injection patterns, and DoS (deep nesting, oversized arrays/strings).

```php
final class InputSanitizer
{
    private const MAX_STRING_LENGTH = 65535; // 64KB
    private const MAX_ARRAY_DEPTH   = 10;
    private const MAX_ARRAY_SIZE    = 1000;

    public static function process(array $input): array;
}
```

**What it does**:
- Trims strings, strips null bytes/control/invisible characters, converts `''` to `null`
- Validates UTF-8 encoding; throws `BadRequestException` on invalid encoding
- Detects XSS and SQL-injection patterns, logs a warning, and removes the suspicious content
- Enforces `MAX_STRING_LENGTH`, `MAX_ARRAY_DEPTH`, `MAX_ARRAY_SIZE` and validates array keys — violations throw `BadRequestException` with localized `Message` objects (`domain: 'validation'`, keys `input_sanitizer.*`)

**Usage Example**:
```php
use App\Shared\Core\Security\InputSanitizer;

// Direct usage
$sanitized = InputSanitizer::process($request->getParsedBody() ?? []);

// Via RawParams (preferred for request data)
use App\Shared\Core\Request\RawParams;

$params = new RawParams($request->getParsedBody() ?? []);
$clean  = $params->sanitize()->all();
```

```php
// Example: dangerous input is cleaned, not escaped
$sanitized = InputSanitizer::process([
    'name' => 'John<script>alert("xss")</script>',
    'bio'  => '',
]);
// ['name' => 'John', 'bio' => null]
```

### 2. JWT Authentication

**Components**:
- `App\Infrastructure\Core\Security\JwtService` — wraps `firebase/jwt`, validates `iss`/`aud` when configured
- `App\Infrastructure\Core\Security\ActorProvider` — builds an `Actor` from token claims (supports a nested `user` claim object or `preferred_username`)
- `App\Shared\Core\Middleware\JwtMiddleware` — PSR-15 middleware that enforces the bearer token
- `App\Infrastructure\Core\Security\CurrentUser` — holds the resolved `Actor` for the request

```php
// JwtMiddleware is wired in config/common/di/jwt.php:
JwtMiddleware::class => static fn (
    JwtService $jwtService,
    ActorProvider $actorProvider,
    CurrentUser $currentUser
) => new JwtMiddleware(
    jwtService: $jwtService,
    actorProvider: $actorProvider,
    currentUser: $currentUser,
    publicPaths: $params['app/jwt']['publicPaths'] ?? [],
);
```

**Flow**:
1. Paths listed in `app/jwt.publicPaths` (env `app.jwt.publicPaths`, JSON array) skip validation.
2. Missing `Authorization` header → `UnauthorizedException` (`auth.header_missing`), unless god mode allows bypass.
3. `Bearer <token>` is decoded by `JwtService`; invalid tokens → `UnauthorizedException` (`auth.invalid_token`).
4. `ActorProvider::fromToken()` maps claims to an `Actor`, stored in `CurrentUser` and as the `actor` request attribute.

**JWT params** (`config/common/params.php`, from `.env`):
```php
'app/jwt' => [
    'secret'      => $_ENV['app.jwt.secret'],
    'algorithm'   => $_ENV['app.jwt.algorithm'] ?? 'HS256',
    'issuer'      => $_ENV['app.jwt.issuer'] ?? null,
    'audience'    => $_ENV['app.jwt.audience'] ?? null,
    'publicPaths' => $publicPaths, // JSON array, e.g. ["/", "/auth/login"]
],
```

### 3. Actor & CurrentUser

```php
// src/Domain/Shared/Core/Contract/ActorInterface.php
interface ActorInterface
{
    public function getId(): int;
    public function getUsername(): string;
    public function getDept(): string;
    public function hasRole(string $app, string $role): bool;
    public function isAdmin(string $app): bool;
    public function isSuperAdmin(string $app): bool;
}
```

- `Actor` (`src/Infrastructure/Core/Security/Actor.php`) — concrete implementation; roles are a per-app map (`$roles[$app]['roles']`, `['admin']`, `['superadmin']`).
- `CurrentUser` — `getActor()` returns the set actor or a `system` actor fallback; `setActor()` re-wraps `Actor` instances preserving `allowGodMode`.
- `CurrentUserAwareInterface` — implement `setCurrentUser(CurrentUser $currentUser)` on classes (e.g. repositories using `HasCoreFeatures`) that need the actor injected.

### 4. Authorization (Permissions)

**Permission map** — `config/common/access.php` (project-owned):

```php
$appCode = $params['app/config']['code'] ?? 'default';

$isKasir      = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isAdmin      = static fn (Actor $actor): bool => $actor->isAdmin($appCode);
$isSuperAdmin = static fn (Actor $actor): bool => $actor->isSuperAdmin($appCode);

return [
    '*' => $allowGodMode,                    // wildcard rule (god mode)
    'example.index'   => static fn (Actor $actor): bool => true, // public
    'example.view'    => $isKasir,           // single rule
    'example.data'    => [$isSuperAdmin, $isKasir], // OR logic
    'example.restore' => $isSuperAdmin,
];
```

Rules may be a boolean, a `callable(Actor): bool`, or an array of callables (OR logic). Unregistered permissions are denied by `PermissionChecker`.

**Enforcement**:
- `AccessMiddleware` reads the `permission` from the matched route (`$route->getArgument('permission')` or route `defaults.permission`) and calls `AccessChecker::userHasPermission()`; denied → `ForbiddenException` (`access.insufficient_permissions`).
- `AccessChecker` evaluates the `'*'` wildcard first (god mode), then the named permission rule.
- `AuthorizerInterface` (`can(string $permission): bool`) is bound to `RbacAuthorizer` in `config/common/di/security-di.php` for use in domain/application services.

**God mode**:
- `app/config.allow_god_mode` (env `app.config.allow_god_mode`) is injected into `CurrentUser` via `security-di.php`.
- When enabled, `Actor::isSuperAdmin()` returns `true` for every actor, and `JwtMiddleware` lets requests through even without a token.
- ⚠️ Development/testing only — set `app.config.allow_god_mode=false` in production.

### 5. Security Middleware

| Middleware | Location | Purpose |
|---|---|---|
| `JwtMiddleware` | `src/Shared/Core/Middleware/` | Bearer-token auth, public path bypass |
| `AccessMiddleware` | `src/Shared/Core/Middleware/` | Route `permission` enforcement |
| `TrustedHostMiddleware` | `src/Shared/Core/Middleware/` | Host allowlist incl. `*.example.com` wildcards (`app/trusted_hosts.allowedHosts`) |
| `CorsMiddleware` | `src/Shared/Core/Middleware/` | CORS headers; `ForbiddenException` (`request.origin_not_allowed`) for bad origins (`app/cors`) |
| `RateLimitMiddleware` | `src/Shared/Core/Middleware/` | `TooManyRequestsException` (`rate_limit.exceeded`) past `app/rateLimit` limits |
| `SecureHeadersMiddleware` | `src/Shared/Core/Middleware/` | CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` (`app/secureHeaders`) |
| `HstsMiddleware` | `src/Infrastructure/Core/Security/` | `Strict-Transport-Security` (`app/hsts`) |
| `RequestParamsMiddleware` | `src/Shared/Core/Middleware/` | Normalizes filter/sort/pagination params (`app/pagination`) |

DI definitions for these live in `config/common/di/middleware-di.php` and `config/common/di/jwt.php`. Add middleware classes to `config/common/middleware.php` to actually execute them on every request.

---

## 🔧 Security Configuration

### 1. **Environment Variables** (`.env`)

```env
app.config.allow_god_mode=false          # NEVER true in production
app.jwt.secret=change-me
app.jwt.algorithm=HS256
app.jwt.issuer=your-issuer
app.jwt.audience=your-audience
app.jwt.publicPaths=["/","/auth/login","/auth/refresh"]
app.trusted_hosts.allowedHosts=["example.com","*.example.com"]
app.cors.allowedOrigins=["https://app.example.com"]
app.rateLimit.maxRequests=100
app.rateLimit.windowSize=60
```

### 2. **DI Configuration**

```php
// config/common/di/security-di.php (excerpt)
return array_merge([
    CurrentUser::class => [
        '__construct()' => [
            'allowGodMode' => $params['app/config']['allow_god_mode'] ?? false,
        ],
    ],
    Actor::class         => static fn (CurrentUser $currentUser) => $currentUser->getActor(),
    AccessChecker::class => static function (CurrentUser $currentUser) {
        $accessMap = require \dirname(__DIR__) . '/access.php';
        return new AccessChecker($currentUser, $accessMap);
    },
    PermissionChecker::class => [
        '__construct()' => [require __DIR__ . '/../access.php'],
    ],
    AuthorizerInterface::class => RbacAuthorizer::class,
], /* project overrides from config/common/security.php */);
```

---

## 🚀 Best Practices

### 1. **Sanitize Input**
```php
// ✅ Sanitize request data
$clean = InputSanitizer::process($request->getParsedBody() ?? []);
// or
$clean = (new RawParams($request->getParsedBody() ?? []))->sanitize()->all();

// ❌ Trust user input
$name = $_POST['name'];
```

### 2. **Protect Routes with Permissions**
```php
// ✅ Declare a permission on the route and add a rule in config/common/access.php
// ❌ Check roles manually inside every action
```

### 3. **Check Authorization in Services**
```php
// ✅ Inject AuthorizerInterface and call can('example.delete')
// ❌ Trust that the middleware already checked everything
```

### 4. **Keep God Mode Off in Production**
```php
// ✅ app.config.allow_god_mode=false in production .env
// ❌ Ship with god mode enabled — it bypasses JWT and all permission checks
```

---

## 📊 Security Considerations

### 1. **Common Vulnerabilities**
- **SQL Injection**: `InputSanitizer` detects and strips suspicious patterns; always use parameterized queries via `Query`/`QueryConditionApplier`
- **XSS**: detected and stripped on input; still escape on output
- **Host Header Injection**: `TrustedHostMiddleware` allowlist
- **Rate Limiting / DoS**: `RateLimitMiddleware` + sanitizer depth/size limits
- **File Upload**: no built-in upload sanitizer — validate type/size in your action before storage

### 2. **Data Protection**
- **Sanitization**: `InputSanitizer::process()` / `RawParams::sanitize()`
- **Validation**: `AbstractValidator` + rules in `src/Shared/Core/Validation/`
- **Localization of errors**: `Message` value objects resolved via `resources/messages/{locale}/`

### 3. **Monitoring**
- `RequestIdMiddleware`, `StructuredLoggingMiddleware`, `MetricsMiddleware`, `ErrorMonitoringMiddleware` under `src/Infrastructure/Core/Monitoring/` (configured via `app/monitoring` params)

---

## 🎯 Summary

Security in this boilerplate is layered: sanitize input at the boundary, authenticate with JWT, authorize per route via the `access.php` permission map, and harden responses with host/CORS/headers/rate-limit middleware. Key benefits include:

- **🛡️ Protection**: Defense against common attacks
- **🔍 Validation**: Comprehensive input validation and sanitization limits
- **🔑 Authentication**: JWT with issuer/audience validation
- **🚦 Authorization**: Config-driven permission map with god-mode override for dev
- **🚀 Performance**: Efficient, static security processing

By following the patterns and best practices outlined in this guide, you can build secure, robust applications with the Yii3 API framework! 🚀
