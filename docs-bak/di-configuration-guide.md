# Dependency Injection Configuration Guide

## 📋 Overview

This guide covers the Dependency Injection (DI) configuration files in `config/common/di/`. These files define how services, repositories, and infrastructure components are wired together in the Yii3 API application using the Domain-Driven Design (DDD) architecture.

---

## 🏗️ DI Architecture Overview

### Configuration Structure

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

All `config/common/di/*.php` files are merged into the `di` group by `config/configuration.php` (which also merges `common/repository.php` first). `config/web/di/*.php` files (`application.php`, `psr17.php`) extend `di` for the web application.

Several `*-di.php` files additionally merge **project-owned override files** from `config/common/`:

| DI file | Project override file |
|---------|----------------------|
| `infrastructure-di.php` | `config/common/infrastructure.php` |
| `security-di.php` | `config/common/security.php` |
| `service-di.php` | `config/common/service.php` |
| `translator-di.php` | `config/common/translator.php` |
| `db-redis.php` | `config/common/redis.php` |
| `repository-di.php` | `config/common/repository.php` (full definition lives there) |

---

## 🔧 Configuration Files

### access-di.php

**Purpose**: Access control and Role-Based Access Control (RBAC) configuration

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

**Key Components**:
- `AccessChecker` (`Yiisoft\Access\AccessChecker`): Package access checker built from `Assignment` + `RuleFactory`
- `Assignment`: Role and permission assignment
- `RuleFactory`: Rule factory for access control
- `PermissionMapRule` (`App\Infrastructure\Core\Security\Rule\PermissionMapRule`): Permission mapping rule backed by `config/common/access.php`

**Usage Example**:
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

**Purpose**: JWT (JSON Web Token) authentication configuration

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Security\ActorProvider;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Infrastructure\Core\Security\JwtService;
use App\Shared\Core\Middleware\JwtMiddleware;

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

**Key Components**:
- `JwtService` (`App\Infrastructure\Core\Security\JwtService`): JWT token generation and validation
- `JwtMiddleware` (`App\Shared\Core\Middleware\JwtMiddleware`): request authentication middleware (part of the web middleware stack in `config/web/di/application.php`)

**Configuration Parameters**:
```bash
# In .env file
app.jwt.secret=secret-key-harus-panjang-256-bit
app.jwt.algorithm=HS256
app.jwt.issuer=https://sso.example.com
app.jwt.audience=https://sso.example.com
app.jwt.publicPaths=["/","/auth/login","/auth/refresh"]
```

**Usage Example**:
```php
// Decoding and validating a JWT token (throws UnauthorizedException on failure)
$payload = $this->jwtService->decode($token);
```

---

### middleware-di.php

**Purpose**: DI definitions for the HTTP middleware used by the request processing pipeline

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
use App\Shared\Core\Middleware\AccessMiddleware;
use App\Shared\Core\Middleware\CorsMiddleware;
use App\Shared\Core\Middleware\RateLimitMiddleware;
use App\Shared\Core\Middleware\RequestParamsMiddleware;
use App\Shared\Core\Middleware\SecureHeadersMiddleware;
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

**Key Components**:
- `RequestParamsMiddleware`: Request parameter processing
- `CorsMiddleware`: CORS handling
- `RateLimitMiddleware`: Rate limiting
- `SecureHeadersMiddleware`: Security headers
- `HstsMiddleware`: HTTP Strict Transport Security
- `RequestIdMiddleware`: Request ID tracking
- `StructuredLoggingMiddleware`: Structured logging
- `MetricsMiddleware`: Application metrics
- `ErrorMonitoringMiddleware`: Error monitoring
- `AccessMiddleware`: Access control

**Middleware Stack**:

These are only DI definitions — the actual execution order is assembled in `config/web/di/application.php` (`Application::class` → `MiddlewareDispatcher::withMiddlewares()`):

1. `FormatDataResponseAsJson` + `ContentNegotiator`: JSON (and optional XML) responses
2. `ErrorCatcher` + `ExceptionResponderFactory`: error catching and API error responses
3. `TrustedHostMiddleware` (`App\Shared\Core\Middleware\TrustedHostMiddleware`): host allowlist
4. `CorsMiddleware`: CORS handling
5. `JwtMiddleware`: JWT authentication
6. `RequestIdMiddleware`, `StructuredLoggingMiddleware`, `MetricsMiddleware`: observability
7. `RateLimitMiddleware`, `SecureHeadersMiddleware`, `ErrorMonitoringMiddleware`
8. `RequestBodyParser`, `AccessMiddleware`, `Router`, `NotFoundMiddleware`

Extra middleware can be listed in `config/common/middleware.php` (project-owned stack).

**Usage Example**:
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

**Purpose**: Application monitoring and performance tracking configuration

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

**Key Components**:
- `CustomMonitoringService` (`App\Infrastructure\Core\Monitoring\CustomMonitoringService`): monitoring implementation
- `MonitoringServiceInterface` (`App\Infrastructure\Core\Monitoring\MonitoringServiceInterface`): monitoring contract

**Features**:
- Performance metrics tracking (`MetricsMiddleware`, `app/monitoring` params)
- Error monitoring (`ErrorMonitoringMiddleware`)
- Request logging (`StructuredLoggingMiddleware`, `RequestIdMiddleware`)

---

### repository-di.php

**Purpose**: Repository pattern configuration for data access layer

`repository-di.php` simply delegates to the project-owned `config/common/repository.php`:

```php
<?php

declare(strict_types=1);

/** @var array $params */

return require \dirname(__DIR__) . '/repository.php';
```

The actual bindings live in `config/common/repository.php`:

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

**Key Components**:
- `ExampleRepository` / `AnotherExampleRepository` (`App\Infrastructure\Common\Persistence\...`): Repository implementations
- `ExampleRepositoryInterface` / `AnotherExampleRepositoryInterface` (`App\Domain\...\Repository\...`): Repository contracts
- `LockVersionConfig` + `CurrentUser` are injected via setters for optimistic locking and audit fields

**Features**:
- Data access abstraction
- Optimistic locking via `LockVersionConfig` (`app/optimisticLock` params)
- MongoDB sync support

**Usage Example**:
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

**Purpose**: Security bindings — current user, actor, access checker and authorizer

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

**Key Components**:
- `CurrentUser`: current user context; `allow_god_mode` comes from `app.config.allow_god_mode`
- `Actor`: the actor derived from `CurrentUser`
- `AccessChecker` / `PermissionChecker`: check permissions against `config/common/access.php`
- `AuthorizerInterface → RbacAuthorizer`: domain-facing authorization contract

**Usage Example**:
```php
// In an application service / domain service
if (!$this->auth->can('example.delete')) {
    throw new ForbiddenException(/* ... */);
}
```

---

### service-di.php

**Purpose**: Application services configuration placeholder

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

**Usage Example**:
```php
// config/common/service.php — add application services here
return [
    ExampleServiceInterface::class => ExampleService::class,
];
```

---

### translator-di.php

**Purpose**: Translation and localization configuration for multi-language support

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

**Key Components**:
- `TranslatorInterface → Translator`: Translation service (default locale `en`)
- `MessageSource`: PHP message source reading `resources/messages/{locale}/{category}.php`
- `IntlMessageFormatter`: intl-based message formatting
- Categories: `app`, `validation`, `error`, `success`

**Usage Example**:
```php
// Translating messages
$translated = $this->translator->translate('Welcome!', [], 'app', 'en');
$translated = $this->translator->translate('Welcome!', [], 'app', 'id');
```

---

### Other DI Files

Brief reference for the remaining files in `config/common/di/`:

- **`application.php`**: binds `App\Shared\ApplicationParams` (name/version/language/environment) from `$params['application']` (`config/common/application.php`).
- **`audit.php`**: `CurrentUserInterface → CurrentUser`; `AuditServiceInterface → DatabaseAuditService` (writes to the `audit_logs` table).
- **`db-mysql.php` / `db-pgsql.php`**: `ConnectionInterface` bound to `Yiisoft\Db\Mysql\Connection` or `Yiisoft\Db\Pgsql\Connection` depending on `$params['yiisoft/db']['driver']`; both register `FileCache` (`@runtime/cache`) and an enabled `SchemaCache`. Inactive driver file returns `[]`.
- **`db-mongodb.php`**: `MongoDB\Client` + `MongoDBService` from `mongodb/mongodb` params; when MongoDB is disabled (or the extension missing) a disabled `MongoDBService` is bound instead.
- **`db-redis.php`**: `RedisService` (host/port from `redis.default.*` env) merged with project bindings from `config/common/redis.php`, which are loaded *after* `common/repository.php` and can override repository bindings.
- **`error-handler.php`**: `HtmlRenderer` `traceLink` closure (uses `$params['traceLink']`, e.g. `phpstorm://open?...` set in `environments/dev/params.php`, and `APP_HOST_PATH` path remapping).
- **`hydrator.php`**: `AttributeResolverFactoryInterface → ContainerAttributeResolverFactory`, `ObjectFactoryInterface → ContainerObjectFactory`.
- **`json.php`**: `App\Shared\Core\Utility\JsonHandler` binding.
- **`logger.php`**: `LoggerInterface → Yiisoft\Log\Logger` with `FileTarget`, `StreamTarget` and a `log.target.security` `FileTarget` (`@runtime/logs/security/security.log`, category `security`).
- **`optimistic-lock.php`**: `LockVersionConfig` from `app/optimisticLock` (`enabled`, `disabledValues`).
- **`router.php`**: `RouteCollectionInterface` built from `$config->get('routes')` (`config/common/routes.php`).
- **`validator.php`**: `RuleHandlerResolverInterface → SimpleRuleHandlerContainer` (with `UniqueValueHandler`), `UniqueValueHandler` gets `ConnectionInterface` + `TranslatorInterface`, `ValidatorInterface → Validator`.

---

## 🔧 Configuration Best Practices

### 1. **Dependency Injection Patterns**

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

### 2. **Configuration Organization**

#### **Environment-Specific Configuration**
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

#### **Reference Usage**
```php
// ✅ Use references for dependencies
Reference::to(ConnectionInterface::class)
Reference::to(LoggerInterface::class)
```

### 3. **Security Considerations**

#### **Sensitive Data**
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

## 📊 Configuration Dependencies

### Dependency Graph

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

### Key Dependencies

#### **PSR Interfaces**
- `PSR-3`: Logger Interface
- `PSR-7`: HTTP Message Interface
- `PSR-11`: Container Interface
- `PSR-14`: Event Dispatcher
- `PSR-17`: HTTP Factories
- `PSR-20`: Clock Interface

#### **Yii3 Components**
- `yiisoft/db`: Database abstraction
- `yiisoft/cache`: Caching system
- `yiisoft/translator`: Translation system
- `yiisoft/access`: Access control
- `yiisoft/middleware`: Middleware dispatcher

#### **Custom Components**
- Domain layer interfaces
- Infrastructure implementations
- Application services

---

## 🚀 Usage Examples

### 1. **Service Registration**

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

### 2. **Repository Usage**

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

### 3. **Security Integration**

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

### Common Issues

#### **1. **Circular Dependencies**
```php
// ❌ Avoid circular references
ServiceA -> ServiceB -> ServiceA

// ✅ Use interfaces to break cycles
ServiceA -> ServiceBInterface -> ServiceB
```

#### **2. **Missing Dependencies**
```php
// ❌ Undefined reference
Reference::to(MissingInterface::class)

// ✅ Ensure interface is defined
Reference::to(DependencyInterface::class)
```

#### **3. **Configuration Conflicts**
```php
// ❌ Duplicate definitions
Interface::class => [ /* ... */ ],
Interface::class => [ /* ... */ ],

// ✅ Use single definition per interface
Interface::class => [ /* ... */ ],
```

### Debugging Tools

#### **1. **Container Inspection**
```php
// Check if service is registered
$container->has(ServiceInterface::class);

// Get service instance
$service = $container->get(ServiceInterface::class);
```

#### **2. **Configuration Validation**
```php
// Validate configuration
$config = require 'config/common/di/service-di.php';
if (!is_array($config)) {
    throw new \RuntimeException('Invalid configuration');
}
```

---

## 📚 References

### Documentation
- **[yiisoft/di](https://github.com/yiisoft/di)**: Dependency injection container
- **[yiisoft/definitions](https://github.com/yiisoft/definitions)**: Definition format (`Reference`, `DynamicReference`, `ReferencesArray`)
- **[PSR-11 Documentation](https://www.php-fig.org/psr/psr-11/)**: Container interface
- **[PSR-20 Documentation](https://www.php-fig.org/psr/psr-20/)**: Clock interface

### Related Guides
- **[Architecture Guide](architecture-guide.md)**: Complete architecture overview
- **[Migration Guide](migration-seeding-guide.md)**: Database management
- **[Quality Guide](quality-guide.md)**: Quality assurance procedures

---

## 🎯 Summary

The DI configuration files provide a robust foundation for dependency injection in the Yii3 API application. Key benefits include:

- **🏗️ Clean Architecture**: Clear separation between layers
- **🔒 Type Safety**: Interface-based dependency management
- **🧪 Testability**: Easy to mock and test dependencies
- **📦 Modularity**: Each configuration file handles specific concerns
- **🔧 Flexibility**: Environment-specific configurations
- **🛡️ Security**: Proper access control and encryption

By following the patterns and best practices outlined in this guide, you can maintain a clean, testable, and secure dependency injection configuration for your Yii3 API application! 🚀
