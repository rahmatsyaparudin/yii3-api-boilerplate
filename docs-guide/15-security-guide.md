# Panduan Security

## 📋 Gambaran Umum

Utilitas security menyediakan fungsi keamanan esensial untuk aplikasi Yii3 API, termasuk input sanitization, autentikasi JWT, otorisasi berbasis permission, dan perlindungan terhadap kerentanan keamanan yang umum.

---

## 🏗️ Arsitektur Security

### Struktur Direktori

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

### Prinsip Desain

#### **1. Defense in Depth**
- Sanitasi input (`InputSanitizer`) di batas request
- Autentikasi JWT (`JwtMiddleware`) sebelum action dijalankan
- Pemeriksaan permission (`AccessMiddleware` + `config/common/access.php`) per route
- Middleware trusted host, CORS, secure headers, dan rate-limit

#### **2. Fail-Safe Defaults**
- Permission untuk route yang tidak dikenal default-nya **deny** (`PermissionChecker::can()` mengembalikan `false` untuk permission yang tidak terdaftar)
- `JwtMiddleware` melempar `UnauthorizedException` ketika header `Authorization` tidak ada atau token tidak valid
- `TrustedHostMiddleware` melempar `UnauthorizedException` untuk host yang tidak diizinkan

#### **3. Sanitize Input, Escape Output**
- `InputSanitizer` menghapus konten mencurigakan tetapi **tidak melakukan HTML-encode** — lakukan escape saat output
- String kosong dinormalisasi menjadi `null`; batas kedalaman/ukuran mencegah DoS lewat payload buatan

#### **4. Configuration over Code**
- Permission map berada di `config/common/access.php`
- Secret/path JWT, CORS, rate limit, HSTS, dan trusted host diatur via env melalui `config/common/params.php`

---

## 📁 Komponen Security

### 1. InputSanitizer

**Lokasi**: `src/Shared/Core/Security/InputSanitizer.php`

**Tujuan**: Sanitasi input statis dan rekursif untuk mencegah XSS, pola SQL-injection, dan DoS (nesting terlalu dalam, array/string berukuran berlebihan).

```php
final class InputSanitizer
{
    private const MAX_STRING_LENGTH = 65535; // 64KB
    private const MAX_ARRAY_DEPTH   = 10;
    private const MAX_ARRAY_SIZE    = 1000;

    public static function process(array $input): array;
}
```

**Apa yang dilakukannya**:
- Melakukan trim pada string, menghapus null byte/karakter kontrol/tak terlihat, mengubah `''` menjadi `null`
- Memvalidasi encoding UTF-8; melempar `BadRequestException` pada encoding yang tidak valid
- Mendeteksi pola XSS dan SQL-injection, mencatat warning, dan menghapus konten mencurigakan
- Menegakkan `MAX_STRING_LENGTH`, `MAX_ARRAY_DEPTH`, `MAX_ARRAY_SIZE` dan memvalidasi key array — pelanggaran melempar `BadRequestException` dengan objek `Message` terlokalisasi (`domain: 'validation'`, key `input_sanitizer.*`)

**Contoh Penggunaan**:
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

### 2. Autentikasi JWT

**Komponen**:
- `App\Infrastructure\Core\Security\JwtService` — membungkus `firebase/jwt`, memvalidasi `iss`/`aud` bila dikonfigurasi
- `App\Infrastructure\Core\Security\ActorProvider` — membangun `Actor` dari claim token (mendukung claim objek `user` bersarang atau `preferred_username`)
- `App\Presentation\Core\Http\Middleware\JwtMiddleware` — middleware PSR-15 yang menegakkan bearer token
- `App\Infrastructure\Core\Security\CurrentUser` — menyimpan `Actor` yang ter-resolve untuk request tersebut

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

**Alur**:
1. Path yang terdaftar di `app/jwt.publicPaths` (env `app.jwt.publicPaths`, JSON array) melewatkan validasi.
2. Header `Authorization` tidak ada → `UnauthorizedException` (`auth.header_missing`), kecuali god mode mengizinkan bypass.
3. `Bearer <token>` di-decode oleh `JwtService`; token tidak valid → `UnauthorizedException` (`auth.invalid_token`).
4. `ActorProvider::fromToken()` memetakan claim ke `Actor`, disimpan di `CurrentUser` dan sebagai atribut request `actor`.

**Parameter JWT** (`config/common/params.php`, dari `.env`):
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

- `Actor` (`src/Infrastructure/Core/Security/Actor.php`) — implementasi konkret; role berupa map per-app (`$roles[$app]['roles']`, `['admin']`, `['superadmin']`).
- `CurrentUser` — `getActor()` mengembalikan actor yang sudah di-set atau fallback `system` actor; `setActor()` membungkus ulang instance `Actor` dengan mempertahankan `allowGodMode`.
- `CurrentUserAwareInterface` — implementasikan `setCurrentUser(CurrentUser $currentUser)` pada class (mis. repository yang menggunakan `HasCoreFeatures`) yang memerlukan injeksi actor.

### 4. Otorisasi (Permission)

**Permission map** — `config/common/access.php` (milik project):

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

Rule dapat berupa boolean, `callable(Actor): bool`, atau array of callable (logika OR). Permission yang tidak terdaftar ditolak oleh `PermissionChecker`.

**Penegakan**:
- `AccessMiddleware` membaca `permission` dari route yang cocok (`$route->getArgument('permission')` atau `defaults.permission` route) dan memanggil `AccessChecker::userHasPermission()`; ditolak → `ForbiddenException` (`access.insufficient_permissions`).
- `AccessChecker` mengevaluasi wildcard `'*'` terlebih dahulu (god mode), lalu rule permission yang bernama.
- `AuthorizerInterface` (`can(string $permission): bool`) di-bind ke `RbacAuthorizer` di `config/common/di/security-di.php` untuk digunakan di domain/application service.

**God mode**:
- `app/config.allow_god_mode` (env `app.config.allow_god_mode`) diinjeksikan ke `CurrentUser` via `security-di.php`.
- Saat aktif, `Actor::isSuperAdmin()` mengembalikan `true` untuk setiap actor, dan `JwtMiddleware` meloloskan request bahkan tanpa token.
- ⚠️ Hanya untuk development/testing — set `app.config.allow_god_mode=false` di production.

### 5. Security Middleware

| Middleware | Lokasi | Tujuan |
|---|---|---|
| `JwtMiddleware` | `src/Shared/Core/Middleware/` | Autentikasi bearer token, bypass public path |
| `AccessMiddleware` | `src/Shared/Core/Middleware/` | Penegakan `permission` route |
| `TrustedHostMiddleware` | `src/Shared/Core/Middleware/` | Allowlist host termasuk wildcard `*.example.com` (`app/trusted_hosts.allowedHosts`) |
| `CorsMiddleware` | `src/Shared/Core/Middleware/` | Header CORS; `ForbiddenException` (`request.origin_not_allowed`) untuk origin tidak valid (`app/cors`) |
| `RateLimitMiddleware` | `src/Shared/Core/Middleware/` | `TooManyRequestsException` (`rate_limit.exceeded`) melewati batas `app/rateLimit` |
| `SecureHeadersMiddleware` | `src/Shared/Core/Middleware/` | CSP, `X-Frame-Options`, `X-Content-Type-Options`, `Referrer-Policy`, `Permissions-Policy` (`app/secureHeaders`) |
| `HstsMiddleware` | `src/Infrastructure/Core/Security/` | `Strict-Transport-Security` (`app/hsts`) |
| `RequestParamsMiddleware` | `src/Shared/Core/Middleware/` | Menormalkan parameter filter/sort/pagination (`app/pagination`) |

Definisi DI untuk middleware ini berada di `config/common/di/middleware-di.php` dan `config/common/di/jwt.php`. Tambahkan class middleware ke `config/common/middleware.php` agar benar-benar dieksekusi pada setiap request.

---

## 🔧 Konfigurasi Security

### 1. **Environment Variable** (`.env`)

```env
app.config.allow_god_mode=false          # JANGAN PERNAH true di production
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

### 2. **Konfigurasi DI**

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

### 1. **Sanitasi Input**
```php
// ✅ Sanitize request data
$clean = InputSanitizer::process($request->getParsedBody() ?? []);
// or
$clean = (new RawParams($request->getParsedBody() ?? []))->sanitize()->all();

// ❌ Trust user input
$name = $_POST['name'];
```

### 2. **Lindungi Route dengan Permission**
```php
// ✅ Declare a permission on the route and add a rule in config/common/access.php
// ❌ Check roles manually inside every action
```

### 3. **Periksa Otorisasi di Service**
```php
// ✅ Inject AuthorizerInterface and call can('example.delete')
// ❌ Trust that the middleware already checked everything
```

### 4. **Pastikan God Mode Mati di Production**
```php
// ✅ app.config.allow_god_mode=false in production .env
// ❌ Ship with god mode enabled — it bypasses JWT and all permission checks
```

---

## 📊 Pertimbangan Security

### 1. **Kerentanan Umum**
- **SQL Injection**: `InputSanitizer` mendeteksi dan menghapus pola mencurigakan; selalu gunakan parameterized query via `Query`/`QueryConditionApplier`
- **XSS**: dideteksi dan dihapus saat input; tetap lakukan escape saat output
- **Host Header Injection**: allowlist `TrustedHostMiddleware`
- **Rate Limiting / DoS**: `RateLimitMiddleware` + batas kedalaman/ukuran sanitizer
- **File Upload**: tidak ada upload sanitizer bawaan — validasi tipe/ukuran di action Anda sebelum penyimpanan

### 2. **Perlindungan Data**
- **Sanitasi**: `InputSanitizer::process()` / `RawParams::sanitize()`
- **Validasi**: `AbstractValidator` + rule di `src/Shared/Core/Validation/`
- **Lokalisasi error**: value object `Message` yang di-resolve via `resources/messages/{locale}/`

### 3. **Monitoring**
- `RequestIdMiddleware`, `StructuredLoggingMiddleware`, `MetricsMiddleware`, `ErrorMonitoringMiddleware` di bawah `src/Infrastructure/Core/Monitoring/` (dikonfigurasi via parameter `app/monitoring`)

---

## 🎯 Ringkasan

Security di boilerplate ini berlapis: sanitasi input di batas request, autentikasi dengan JWT, otorisasi per route via permission map `access.php`, dan perkuat respons dengan middleware host/CORS/headers/rate-limit. Manfaat utamanya meliputi:

- **🛡️ Perlindungan**: Pertahanan terhadap serangan umum
- **🔍 Validasi**: Validasi input komprehensif dan batas sanitasi
- **🔑 Autentikasi**: JWT dengan validasi issuer/audience
- **🚦 Otorisasi**: Permission map berbasis konfigurasi dengan override god mode untuk dev
- **🚀 Performa**: Pemrosesan security yang efisien dan statis

Dengan mengikuti pola dan best practice yang diuraikan dalam panduan ini, Anda dapat membangun aplikasi yang aman dan robust dengan Yii3 API framework! 🚀
