# Panduan Konfigurasi Dependency Injection

## 📋 Gambaran Umum

Panduan ini membahas file konfigurasi Dependency Injection (DI) di `config/common/di/`. File-file ini mendefinisikan bagaimana service, repository, dan komponen infrastruktur di-wire bersama dalam aplikasi Yii3 API menggunakan arsitektur Domain-Driven Design (DDD).

---

## 🏗️ Gambaran Arsitektur DI

### Struktur Konfigurasi

```
config/common/di/
├── access-di.php           # Access control and RBAC configuration
├── application.php         # App\Shared\ApplicationParams service
├── audit.php               # Audit trail (AuditServiceInterface → DatabaseAuditService)
├── db-mongodb.php          # MongoDB client + MongoDBService
├── db-mysql.php            # MySQL/MariaDB connection (when db.default.driver=mysql|mariadb)
├── db-pgsql.php            # PostgreSQL connection (when db.default.driver=pgsql)
├── db-redis.php            # RedisService + project Redis bindings
├── error-handler.php       # HtmlRenderer traceLink configuration
├── hydrator.php            # yiisoft/hydrator container factories
├── infrastructure-di.php   # ClockInterface, DateTimeProviderInterface + project bindings
├── json.php                # JsonHandler binding
├── jwt.php                 # JwtService + JwtMiddleware configuration
├── logger.php              # PSR-3 LoggerInterface (file/stream/security targets)
├── middleware-di.php       # Middleware definitions (CORS, rate limit, headers, ...)
├── monitoring.php          # MonitoringServiceInterface → CustomMonitoringService
├── optimistic-lock.php     # LockVersionConfig from app/optimisticLock params
├── repository-di.php       # Delegates to config/common/repository.php
├── router.php              # RouteCollectionInterface from common/routes.php
├── security-di.php         # CurrentUser, Actor, AccessChecker, AuthorizerInterface, ...
├── service-di.php          # Core service bindings + project overrides
├── translator-di.php       # TranslatorInterface (app/validation/error/success categories)
└── validator.php           # ValidatorInterface + rule handler resolver
```

Semua file `config/common/di/*.php` digabungkan ke dalam grup `di` oleh `config/configuration.php` (yang juga menggabungkan `common/repository.php` terlebih dahulu). File `config/web/di/*.php` (`application.php`, `psr17.php`) memperluas `di` untuk aplikasi web.

Beberapa file `*-di.php` juga menggabungkan **file override milik proyek** dari `config/common/`:

| File DI | File override proyek |
|---------|----------------------|
| `infrastructure-di.php` | `config/common/infrastructure.php` |
| `security-di.php` | `config/common/security.php` |
| `service-di.php` | `config/common/service.php` |
| `translator-di.php` | `config/common/translator.php` |
| `db-redis.php` | `config/common/redis.php` |
| `repository-di.php` | `config/common/repository.php` (definisi lengkap berada di sana) |

---

## 🔧 File Konfigurasi

### access-di.php

**Tujuan**: Konfigurasi access control dan Role-Based Access Control (RBAC)

```php
<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Access\AccessChecker;
use Yiisoft\Access\Assignment\Assignment;
use Yiisoft\Access\Permission\Permission;
use Yiisoft\Access\Rule\RuleFactory;

// Infrastructure Layer
use App\Infrastructure\Core\Security\Rule\PermissionMapRule;

$permissionMap = require __DIR__ . '/../access.php';

return [
    Assignment::class => static fn () => new Assignment([
        'authenticated' => \array_map(
            static fn (string $permission) => new Permission($permission, 'permission.map'),
            \array_keys($permissionMap)
        ),
    ]),

    RuleFactory::class => static fn () => new RuleFactory([
        'permission.map' => static fn () => new PermissionMapRule($permissionMap),
    ]),

    AccessChecker::class => static fn ($c) => new AccessChecker(
        $c->get(Assignment::class),
        $c->get(RuleFactory::class),
    ),
];
```

**Komponen Utama**:
- `AccessChecker` (`Yiisoft\Access\AccessChecker`): Access checker dari package yang dibangun dari `Assignment` + `RuleFactory`
- `Assignment`: Penugasan role dan permission
- `RuleFactory`: Factory rule untuk access control
- `PermissionMapRule` (`App\Infrastructure\Core\Security\Rule\PermissionMapRule`): Rule pemetaan permission yang didukung `config/common/access.php`

**Contoh Penggunaan**:
```php
// The project also provides App\Infrastructure\Core\Security\AccessChecker,
// used by AccessMiddleware / AuthorizerInterface (RbacAuthorizer):
public function can(string $permission): bool
{
    return $this->authorizer->can('example.create');   // AuthorizerInterface
}
```

---

### jwt.php

**Tujuan**: Konfigurasi autentikasi JWT (JSON Web Token)

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Security\ActorProvider;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Infrastructure\Core\Security\JwtService;
use App\Presentation\Core\Http\Middleware\JwtMiddleware;

// @var array $params

return [
    JwtService::class => [
        '__construct()' => [
            'secret'   => $params['app/jwt']['secret'] ?? '',
            'algo'     => $params['app/jwt']['algorithm'] ?? 'HS256',
            'issuer'   => $params['app/jwt']['issuer'] ?? null,
            'audience' => $params['app/jwt']['audience'] ?? null,
        ],
    ],

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
];
```

**Komponen Utama**:
- `JwtService` (`App\Infrastructure\Core\Security\JwtService`): Pembuatan dan validasi token JWT
- `JwtMiddleware` (`App\Presentation\Core\Http\Middleware\JwtMiddleware`): Middleware autentikasi request (bagian dari stack middleware web di `config/web/di/application.php`)

**Parameter Konfigurasi**:
```bash
# Di file .env
app.jwt.secret=secret-key-harus-panjang-256-bit
app.jwt.algorithm=HS256
app.jwt.issuer=https://sso.example.com
app.jwt.audience=https://sso.example.com
app.jwt.publicPaths=["/","/auth/login","/auth/refresh"]
```

**Contoh Penggunaan**:
```php
// Decoding and validating a JWT token (throws UnauthorizedException on failure)
$payload = $this->jwtService->decode($token);
```

---

### middleware-di.php

**Tujuan**: Definisi DI untuk HTTP middleware yang digunakan oleh pipeline pemrosesan request

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Monitoring\ErrorMonitoringMiddleware;
use App\Infrastructure\Core\Monitoring\MetricsMiddleware;
use App\Infrastructure\Core\Monitoring\RequestIdMiddleware;
use App\Infrastructure\Core\Monitoring\StructuredLoggingMiddleware;
use App\Infrastructure\Core\Security\AccessChecker;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Infrastructure\Core\Security\HstsMiddleware;
// Shared Layer
use App\Presentation\Core\Http\Middleware\AccessMiddleware;
use App\Presentation\Core\Http\Middleware\CorsMiddleware;
use App\Presentation\Core\Http\Middleware\RateLimitMiddleware;
use App\Presentation\Core\Http\Middleware\RequestParamsMiddleware;
use App\Presentation\Core\Http\Middleware\SecureHeadersMiddleware;
// PSR Interfaces
use Psr\Http\Message\ResponseFactoryInterface;
// Vendor Layer
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Security\TrustedHosts\TrustedHostsMiddleware;

// @var array $params

return [
    // Always available through DI; add to the middleware stack in
    // config/common/middleware.php if needed for a project.
    TrustedHostsMiddleware::class => TrustedHostsMiddleware::class,

    // Middleware global untuk semua route
    RequestParamsMiddleware::class => static function () use ($params) {
        $pagination = $params['app/pagination'] ?? [];

        return new RequestParamsMiddleware(
            defaultPageSize: (int) ($pagination['defaultPageSize'] ?? 50),
            maxPageSize: (int) ($pagination['maxPageSize'] ?? 200),
        );
    },

    CorsMiddleware::class => static fn (ResponseFactoryInterface $responseFactory) => new CorsMiddleware($params['app/cors'], $responseFactory),

    RateLimitMiddleware::class => static function () use ($params) {
        $rateLimit = $params['app/rateLimit'] ?? [];

        return new RateLimitMiddleware(
            maxRequests: (int) ($rateLimit['maxRequests'] ?? 100),
            windowSize: (int) ($rateLimit['windowSize'] ?? 60)
        );
    },
    
    SecureHeadersMiddleware::class => static function () use ($params) {
        $secureHeaders = $params['app/secureHeaders'] ?? [];

        return new SecureHeadersMiddleware($secureHeaders);
    },
    
    HstsMiddleware::class => static function () use ($params) {
        $hsts = $params['app/hsts'] ?? [];

        return new HstsMiddleware(
            maxAge: (int) ($hsts['maxAge'] ?? 31536000),
            includeSubDomains: (bool) ($hsts['includeSubDomains'] ?? true),
            preload: (bool) ($hsts['preload'] ?? false)
        );
    },

    RequestIdMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new RequestIdMiddleware($monitoring['request_id_header'] ?? 'X-Request-Id');
    },

    StructuredLoggingMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new StructuredLoggingMiddleware($monitoring['logging'] ?? []);
    },

    MetricsMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new MetricsMiddleware($monitoring['metrics'] ?? []);
    },

    ErrorMonitoringMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new ErrorMonitoringMiddleware($monitoring['error_monitoring'] ?? []);
    },

    AccessMiddleware::class => static fn (
        AccessChecker $accessChecker,
        CurrentUser $currentUser,
        UrlMatcher $urlMatcher,
    ) => new AccessMiddleware($accessChecker, $currentUser, $urlMatcher),
];
```

**Komponen Utama**:
- `RequestParamsMiddleware`: Pemrosesan parameter request
- `CorsMiddleware`: Penanganan CORS
- `RateLimitMiddleware`: Pembatasan rate request
- `SecureHeadersMiddleware`: Security header
- `HstsMiddleware`: HTTP Strict Transport Security
- `RequestIdMiddleware`: Pelacakan Request ID
- `StructuredLoggingMiddleware`: Structured logging
- `MetricsMiddleware`: Metric aplikasi
- `ErrorMonitoringMiddleware`: Monitoring error
- `AccessMiddleware`: Access control

**Middleware Stack**:

Ini hanyalah definisi DI — urutan eksekusi sebenarnya dirakit di `config/web/di/application.php` (`Application::class` → `MiddlewareDispatcher::withMiddlewares()`):

1. `FormatDataResponseAsJson` + `ContentNegotiator`: Response JSON (dan opsional XML)
2. `ErrorCatcher` + `ExceptionResponderFactory`: Penangkapan error dan response error API
3. `TrustedHostMiddleware` (`App\Presentation\Core\Http\Middleware\TrustedHostMiddleware`): Allowlist host
4. `CorsMiddleware`: Penanganan CORS
5. `JwtMiddleware`: Autentikasi JWT
6. `RequestIdMiddleware`, `StructuredLoggingMiddleware`, `MetricsMiddleware`: Observability
7. `RateLimitMiddleware`, `SecureHeadersMiddleware`, `ErrorMonitoringMiddleware`
8. `RequestBodyParser`, `AccessMiddleware`, `Router`, `NotFoundMiddleware`

Middleware tambahan dapat dicantumkan di `config/common/middleware.php` (stack milik proyek).

**Contoh Penggunaan**:
```php
// Middleware are resolved through the container — e.g. AccessMiddleware gets:
AccessMiddleware::class => static fn (
    AccessChecker $accessChecker,
    CurrentUser $currentUser,
    UrlMatcher $urlMatcher,
) => new AccessMiddleware($accessChecker, $currentUser, $urlMatcher),
```

---

### monitoring.php

**Tujuan**: Konfigurasi monitoring aplikasi dan pelacakan performa

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Monitoring\CustomMonitoringService;
use App\Infrastructure\Core\Monitoring\MonitoringServiceInterface;
// Vendor Layer
use Yiisoft\Di\Container;

return [
    MonitoringServiceInterface::class => static function (Container $container) use ($params) {
        $monitoringConfig = $params['app/monitoring'] ?? [];

        return new CustomMonitoringService([
            'log_file' => $monitoringConfig['log_file'] ?? 'runtime/logs/api.log',
        ]);
    },
];
```

**Komponen Utama**:
- `CustomMonitoringService` (`App\Infrastructure\Core\Monitoring\CustomMonitoringService`): Implementasi monitoring
- `MonitoringServiceInterface` (`App\Infrastructure\Core\Monitoring\MonitoringServiceInterface`): Kontrak monitoring

**Fitur**:
- Pelacakan metric performa (`MetricsMiddleware`, params `app/monitoring`)
- Monitoring error (`ErrorMonitoringMiddleware`)
- Logging request (`StructuredLoggingMiddleware`, `RequestIdMiddleware`)

---

### repository-di.php

**Tujuan**: Konfigurasi repository pattern untuk lapisan akses data

`repository-di.php` hanya mendelegasikan ke `config/common/repository.php` milik proyek:

```php
<?php

declare(strict_types=1);

/** @var array $params */

return require \dirname(__DIR__) . '/repository.php';
```

Binding yang sebenarnya berada di `config/common/repository.php`:

```php
use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Infrastructure\Common\Persistence\AnotherExample\AnotherExampleRepository;
use App\Infrastructure\Common\Persistence\Example\ExampleRepository;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Shared\Core\ValueObject\LockVersionConfig;
use Yiisoft\Definitions\Reference;

return [
    ExampleRepositoryInterface::class => [
        'class'                  => ExampleRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()'       => [Reference::to(CurrentUser::class)],
        '__construct()'          => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
    AnotherExampleRepositoryInterface::class => [
        'class'                  => AnotherExampleRepository::class,
        'setLockVersionConfig()' => [Reference::to(LockVersionConfig::class)],
        'setCurrentUser()'       => [Reference::to(CurrentUser::class)],
        '__construct()'          => [
            'params' => $params['app/optimisticLock'] ?? [],
        ],
    ],
];
```

**Komponen Utama**:
- `ExampleRepository` / `AnotherExampleRepository` (`App\Infrastructure\Common\Persistence\...`): Implementasi repository
- `ExampleRepositoryInterface` / `AnotherExampleRepositoryInterface` (`App\Domain\...\Repository\...`): Kontrak repository
- `LockVersionConfig` + `CurrentUser` di-inject melalui setter untuk optimistic locking dan field audit

**Fitur**:
- Abstraksi akses data
- Optimistic locking via `LockVersionConfig` (params `app/optimisticLock`)
- Dukungan sync MongoDB

**Contoh Penggunaan**:
```php
// Using repository in application service
$example = $this->repository->findById(id: $id);
$examples = $this->repository->list(criteria: $criteria); // PaginatedResult
$this->repository->insert(entity: $example);
$this->repository->update(entity: $example);
$this->repository->delete(entity: $example);
$this->repository->restore(id: $id);
```

---

### security-di.php

**Tujuan**: Binding security — current user, actor, access checker dan authorizer

```php
<?php

declare(strict_types=1);

use App\Domain\Shared\Core\Security\AuthorizerInterface;
use App\Infrastructure\Core\Security\AccessChecker;
use App\Infrastructure\Core\Security\Actor;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Infrastructure\Core\Security\PermissionChecker;
use App\Infrastructure\Core\Security\RbacAuthorizer;

/** @var array $params */

// Core security bindings. Project-owned bindings may be merged from
// config/common/security.php and can override these defaults.
$projectBindings = \dirname(__DIR__) . '/security.php';

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
        '__construct()' => [
            require __DIR__ . '/../access.php',
        ],
    ],
    AuthorizerInterface::class => RbacAuthorizer::class,
], file_exists($projectBindings) ? require $projectBindings : []);
```

**Komponen Utama**:
- `CurrentUser`: Konteks user saat ini; `allow_god_mode` berasal dari `app.config.allow_god_mode`
- `Actor`: Actor yang diturunkan dari `CurrentUser`
- `AccessChecker` / `PermissionChecker`: Memeriksa permission terhadap `config/common/access.php`
- `AuthorizerInterface → RbacAuthorizer`: Kontrak otorisasi untuk sisi domain

**Contoh Penggunaan**:
```php
// In an application service / domain service
if (!$this->auth->can('example.delete')) {
    throw new ForbiddenException(/* ... */);
}
```

---

### service-di.php

**Tujuan**: Placeholder konfigurasi application service

```php
<?php

declare(strict_types=1);

/** @var array $params */

// Core service DI configuration. Add project-wide service bindings here.
// Project-owned bindings may be merged from config/common/service.php and
// can override these defaults.
$projectBindings = \dirname(__DIR__) . '/service.php';

return array_merge([
    // Service DI configuration
    // Add your service definitions here
], file_exists($projectBindings) ? require $projectBindings : []);
```

**Contoh Penggunaan**:
```php
// config/common/service.php — add application services here
return [
    ExampleServiceInterface::class => ExampleService::class,
];
```

---

### translator-di.php

**Tujuan**: Konfigurasi translasi dan lokalisasi untuk dukungan multi-bahasa

```php
<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;

/** @var array $params */

// Core translator binding. Project-owned bindings may be merged from
// config/common/translator.php and can override these defaults.
$projectBindings = \dirname(__DIR__) . '/translator.php';

return array_merge([
    TranslatorInterface::class => static function () {
        $translator = new Translator('en');

        $messageSource = new MessageSource(__DIR__ . '/../../../resources/messages');
        $formatter     = new IntlMessageFormatter();

        $translator->addCategorySources(
            new CategorySource('app', $messageSource, $formatter),
            new CategorySource('validation', $messageSource, $formatter),
            new CategorySource('error', $messageSource, $formatter),
            new CategorySource('success', $messageSource, $formatter),
        );

        return $translator;
    },
], file_exists($projectBindings) ? require $projectBindings : []);
```

**Komponen Utama**:
- `TranslatorInterface → Translator`: Service translasi (default locale `en`)
- `MessageSource`: Message source PHP yang membaca `resources/messages/{locale}/{category}.php`
- `IntlMessageFormatter`: Pemformatan pesan berbasis intl
- Kategori: `app`, `validation`, `error`, `success`

**Contoh Penggunaan**:
```php
// Translating messages
$translated = $this->translator->translate('Welcome!', [], 'app', 'en');
$translated = $this->translator->translate('Welcome!', [], 'app', 'id');
```

---

### File DI Lainnya

Referensi singkat untuk file-file lainnya di `config/common/di/`:

- **`application.php`**: Mem-bind `App\Shared\ApplicationParams` (name/version/language/environment) dari `$params['application']` (`config/common/application.php`).
- **`audit.php`**: `CurrentUserInterface → CurrentUser`; `AuditServiceInterface → DatabaseAuditService` (menulis ke tabel `audit_logs`).
- **`db-mysql.php` / `db-pgsql.php`**: `ConnectionInterface` di-bind ke `Yiisoft\Db\Mysql\Connection` atau `Yiisoft\Db\Pgsql\Connection` tergantung `$params['yiisoft/db']['driver']`; keduanya meregistrasi `FileCache` (`@runtime/cache`) dan `SchemaCache` yang aktif. File driver yang tidak aktif mengembalikan `[]`.
- **`db-mongodb.php`**: `MongoDB\Client` + `MongoDBService` dari params `mongodb/mongodb`; ketika MongoDB dinonaktifkan (atau extension tidak tersedia), `MongoDBService` yang dinonaktifkan yang di-bind.
- **`db-redis.php`**: `RedisService` (host/port dari env `redis.default.*`) digabung dengan binding proyek dari `config/common/redis.php`, yang dimuat *setelah* `common/repository.php` dan dapat meng-override binding repository.
- **`error-handler.php`**: Closure `traceLink` `HtmlRenderer` (menggunakan `$params['traceLink']`, mis. `phpstorm://open?...` yang diset di `environments/dev/params.php`, dan remapping path `APP_HOST_PATH`).
- **`hydrator.php`**: `AttributeResolverFactoryInterface → ContainerAttributeResolverFactory`, `ObjectFactoryInterface → ContainerObjectFactory`.
- **`json.php`**: Binding `App\Shared\Core\Utility\JsonHandler`.
- **`logger.php`**: `LoggerInterface → Yiisoft\Log\Logger` dengan `FileTarget`, `StreamTarget` dan `FileTarget` `log.target.security` (`@runtime/logs/security/security.log`, kategori `security`).
- **`optimistic-lock.php`**: `LockVersionConfig` dari `app/optimisticLock` (`enabled`, `disabledValues`).
- **`router.php`**: `RouteCollectionInterface` dibangun dari `$config->get('routes')` (`config/common/routes.php`).
- **`validator.php`**: `RuleHandlerResolverInterface → SimpleRuleHandlerContainer` (dengan `UniqueValueHandler`), `UniqueValueHandler` mendapatkan `ConnectionInterface` + `TranslatorInterface`, `ValidatorInterface → Validator`.

---

## 🔧 Best Practice Konfigurasi

### 1. **Pattern Dependency Injection**

#### **Constructor Injection**
```php
// ✅ Preferred pattern
return [
    ServiceInterface::class => [
        'class' => Service::class,
        '__construct()' => [
            Reference::to(DependencyInterface::class),
        ],
    ],
];
```

#### **Interface Segregation**
```php
// ✅ Separate interfaces for different concerns
interface RepositoryInterface { /* ... */ }
interface CacheableRepositoryInterface { /* ... */ }
```

### 2. **Organisasi Konfigurasi**

#### **Konfigurasi Spesifik Environment**
```php
// ✅ Use params groups populated from .env (config/common/params.php)
return [
    JwtService::class => [
        '__construct()' => [
            'secret' => $params['app/jwt']['secret'] ?? '',
            'algo'   => $params['app/jwt']['algorithm'] ?? 'HS256',
        ],
    ],
];
```

#### **Penggunaan Reference**
```php
// ✅ Use references for dependencies
Reference::to(ConnectionInterface::class)
Reference::to(LoggerInterface::class)
```

### 3. **Pertimbangan Keamanan**

#### **Data Sensitif**
```php
// ✅ Store secrets in environment variables, surfaced via params groups
'secret' => $params['app/jwt']['secret'],
```

#### **Access Control**
```php
// ✅ Configure RBAC properly
AuthorizerInterface::class => RbacAuthorizer::class,
```

---

## 📊 Dependensi Konfigurasi

### Grafik Dependensi

```
┌─────────────────────────────────────────────────────────────────────┐
│                    Application Layer Services                │
├─────────────────────────────────────────────────────────────────────┤
│                    Domain Layer Interfaces                    │
├─────────────────────────────────────────────────────────────────────┤
│                Infrastructure Layer Implementations           │
├─────────────────────────────────────────────────────────────────────┤
│                    External Dependencies (PSR)              │
└─────────────────────────────────────────────────────────────────────┘
```

### Dependensi Utama

#### **Interface PSR**
- `PSR-3`: Logger Interface
- `PSR-7`: HTTP Message Interface
- `PSR-11`: Container Interface
- `PSR-14`: Event Dispatcher
- `PSR-17`: HTTP Factories
- `PSR-20`: Clock Interface

#### **Komponen Yii3**
- `yiisoft/db`: Abstraksi database
- `yiisoft/cache`: Sistem caching
- `yiisoft/translator`: Sistem translasi
- `yiisoft/access`: Access control
- `yiisoft/middleware`: Middleware dispatcher

#### **Komponen Kustom**
- Interface domain layer
- Implementasi infrastructure
- Application service

---

## 🚀 Contoh Penggunaan

### 1. **Registrasi Service**

```php
// In application service (real constructor)
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private ExampleRepositoryInterface $repository,
        private ExampleDomainService $domainService
    ) {
    }
}
```

### 2. **Penggunaan Repository**

```php
// In application service
$example = $this->repository->findById(id: $id);
if ($example === null) {
    throw new NotFoundException(translate: Message::create(
        key: 'resource.not_found',
        params: ['resource' => Example::RESOURCE, 'field' => 'id', 'value' => $id]
    ));
}
```

### 3. **Integrasi Security**

```php
// In application service (AuthorizerInterface → RbacAuthorizer)
if (!$this->auth->can('example.create')) {
    throw new ForbiddenException(translate: Message::create(key: 'error.forbidden'));
}
```

### 4. **Audit Logging**

```php
// In repository (AuditServiceInterface → DatabaseAuditService)
$this->auditService->log(
    tableName: 'example',
    recordId: $example->getId(),
    action: 'create',
    newValues: $example->toArray(),
    actor: $this->currentUser->getActor(),
    ipAddress: $ipAddress,
    userAgent: $userAgent,
);
```

---

## 🔍 Troubleshooting

### Masalah Umum

#### **1. **Circular Dependency**
```php
// ❌ Avoid circular references
ServiceA -> ServiceB -> ServiceA

// ✅ Use interfaces to break cycles
ServiceA -> ServiceBInterface -> ServiceB
```

#### **2. **Dependensi yang Hilang**
```php
// ❌ Undefined reference
Reference::to(MissingInterface::class)

// ✅ Ensure interface is defined
Reference::to(DependencyInterface::class)
```

#### **3. **Konflik Konfigurasi**
```php
// ❌ Duplicate definitions
Interface::class => [ /* ... */ ],
Interface::class => [ /* ... */ ],

// ✅ Use single definition per interface
Interface::class => [ /* ... */ ],
```

### Tool Debugging

#### **1. **Inspeksi Container**
```php
// Check if service is registered
$container->has(ServiceInterface::class);

// Get service instance
$service = $container->get(ServiceInterface::class);
```

#### **2. **Validasi Konfigurasi**
```php
// Validate configuration
$config = require 'config/common/di/service-di.php';
if (!is_array($config)) {
    throw new \RuntimeException('Invalid configuration');
}
```

---

## 📚 Referensi

### Dokumentasi
- **[yiisoft/di](https://github.com/yiisoft/di)**: Dependency injection container
- **[yiisoft/definitions](https://github.com/yiisoft/definitions)**: Format definisi (`Reference`, `DynamicReference`, `ReferencesArray`)
- **[PSR-11 Documentation](https://www.php-fig.org/psr/psr-11/)**: Interface container
- **[PSR-20 Documentation](https://www.php-fig.org/psr/psr-20/)**: Interface clock

### Panduan Terkait
- **[Architecture Guide](02-architecture-guide.md)**: Gambaran arsitektur lengkap
- **[Migration Guide](17-migration-seeding-guide.md)**: Manajemen database
- **[Quality Guide](21-quality-guide.md)**: Prosedur quality assurance

---

## 🎯 Ringkasan

File konfigurasi DI menyediakan fondasi yang kuat untuk dependency injection dalam aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🏗️ Clean Architecture**: Pemisahan yang jelas antar lapisan
- **🔒 Type Safety**: Pengelolaan dependensi berbasis interface
- **🧪 Testability**: Dependensi mudah di-mock dan diuji
- **📦 Modularity**: Setiap file konfigurasi menangani concern spesifik
- **🔧 Flexibility**: Konfigurasi spesifik environment
- **🛡️ Security**: Access control dan enkripsi yang tepat

Dengan mengikuti pattern dan best practice yang diuraikan dalam panduan ini, Anda dapat memelihara konfigurasi dependency injection yang bersih, testable, dan aman untuk aplikasi Yii3 API Anda! 🚀
