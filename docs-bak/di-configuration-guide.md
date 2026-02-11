# Dependency Injection Configuration Guide

## 📋 Overview

This guide covers the Dependency Injection (DI) configuration files in `config/common/di/`. These files define how services, repositories, and infrastructure components are wired together in the Yii3 API application using the Domain-Driven Design (DDD) architecture.

---

## 🏗️ DI Architecture Overview

### Configuration Structure

```
config/common/di/
├── access-di.php           # Access control and RBAC configuration
├── audit.php               # Audit trail and logging configuration
├── db-mongodb.php          # MongoDB database configuration
├── db-pgsql.php            # PostgreSQL database configuration
├── db-redis.php            # Redis cache configuration
├── infrastructure.php      # Infrastructure services configuration
├── json.php                # JSON serialization and parsing configuration
├── jwt.php                 # JWT authentication configuration
├── middleware.php          # HTTP middleware stack configuration
├── monitoring.php          # Application monitoring configuration
├── repository.php          # Repository pattern configuration
├── security.php            # Security and encryption configuration
├── seed.php                # Database seeding configuration
├── service.php             # Application services configuration
└── translator.php          # Translation and localization configuration
```

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

// Shared Layer
use App\Infrastructure\Security\Rule\PermissionMapRule;

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
- `AccessChecker`: Main access control service
- `Assignment`: Role and permission assignment
- `RuleFactory`: Rule factory for access control
- `PermissionMapRule`: Permission mapping rule

**Usage Example**:
```php
// In controller or service
public function can(string $permission): bool
{
    return $this->accessChecker->can($this->actor, $permission);
}
```

---

### jwt.php

**Purpose**: JWT (JSON Web Token) authentication configuration

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Security\JwtService;

// @var array $params

return [
    JwtService::class => [
        '__construct()' => [
            'secret'   => $params['app/jwt']['secret'],
            'algo'     => $params['app/jwt']['algorithm'] ?? 'HS256',
            'issuer'   => $params['app/jwt']['issuer'] ?? null,
            'audience' => $params['app/jwt']['audience'] ?? null,
        ],
    ],
];
```

**Key Components**:
- `JwtService`: JWT token generation and validation

**Configuration Parameters**:
```bash
# In .env file
app/jwt/secret=secret-key-harus-panjang-256-bit
app/jwt/algorithm=HS256
app/jwt/issuer=https://sso.dev-enterkomputer.com
app/jwt/audience=https://sso.dev-enterkomputer.com
```

**Usage Example**:
```php
// Generating JWT token
$token = $this->jwtService->generateToken([
    'user_id' => $user->getId(),
    'username' => $user->getUsername(),
    'roles' => $user->getRoles(),
]);

// Validating JWT token
$payload = $this->jwtService->validateToken($token);
```

---

### middleware.php

**Purpose**: HTTP middleware stack configuration for request processing pipeline

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Monitoring\ErrorMonitoringMiddleware;
use App\Infrastructure\Monitoring\MetricsMiddleware;
use App\Infrastructure\Monitoring\RequestIdMiddleware;
use App\Infrastructure\Monitoring\StructuredLoggingMiddleware;
use App\Infrastructure\Security\AccessChecker;
use App\Infrastructure\Security\CurrentUser;
use App\Infrastructure\Security\HstsMiddleware;

// Shared Layer
use App\Shared\Middleware\AccessMiddleware;
use App\Shared\Middleware\CorsMiddleware;
use App\Shared\Middleware\RateLimitMiddleware;
use App\Shared\Middleware\RequestParamsMiddleware;
use App\Shared\Middleware\SecureHeadersMiddleware;

// PSR Interfaces
use Psr\Http\Message\ResponseFactoryInterface;

// Vendor Layer
use Yiisoft\Router\FastRoute\UrlMatcher;

// @var array $params

return [
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
1. `RequestIdMiddleware`: Request ID generation
2. `StructuredLoggingMiddleware`: Structured logging setup
3. `ErrorMonitoringMiddleware`: Error monitoring setup
4. `MetricsMiddleware`: Metrics collection
5. `RequestParamsMiddleware`: Request parameter processing
6. `CorsMiddleware`: CORS handling
7. `RateLimitMiddleware`: Rate limiting
8. `SecureHeadersMiddleware`: Security headers
9. `HstsMiddleware`: HSTS enforcement
10. `AccessMiddleware`: Access control

**Usage Example**:
```php
// Middleware registration
$app->addMiddleware(new RequestParamsMiddleware($defaultPageSize, $maxPageSize));
$app->addMiddleware(new CorsMiddleware($corsConfig, $responseFactory));
$app->addMiddleware(new RateLimitMiddleware($maxRequests, $windowSize));
```

---

### monitoring.php

**Purpose**: Application monitoring and performance tracking configuration

```php
<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Shared\Contract\MonitoringServiceInterface;

// Infrastructure Layer
use App\Infrastructure\Monitoring\MonitoringService;

// PSR Interfaces
use Psr\Log\LoggerInterface;

return [
    MonitoringServiceInterface::class => [
        'class' => MonitoringService::class,
        '__construct()' => [
            Reference::to(LoggerInterface::class),
        ],
    ],
];
```

**Key Components**:
- `MonitoringService`: Application monitoring implementation
- `MonitoringServiceInterface`: Monitoring service contract

**Features**:
- Performance metrics tracking
- Error monitoring
- Request logging
- Resource usage monitoring

**Usage Example**:
```php
// Monitoring application performance
$this->monitoringService->trackMetric('request_duration', $duration);
$this->monitoringService->trackError($exception);
$this->monitoringService->trackResourceUsage($memory, $cpu);
```

---

### repository.php

**Purpose**: Repository pattern configuration for data access layer

```php
<?php

declare(strict_types=1);

// Domain Layer
use App\Domain\Example\Repository\ExampleRepositoryInterface;

// Infrastructure Layer
use App\Infrastructure\Persistence\Example\ExampleRepository;

// PSR Interfaces
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    ExampleRepositoryInterface::class => [
        'class' => ExampleRepository::class,
        '__construct()' => [
            Reference::to(ConnectionInterface::class),
        ],
    ],
];
```

**Key Components**:
- `ExampleRepository`: Repository implementation
- `ExampleRepositoryInterface`: Repository contract

**Features**:
- Data access abstraction
- Transaction management
- Query optimization
- Caching integration

**Usage Example**:
```php
// Using repository in application service
$example = $this->repository->findById($id);
$examples = $this->repository->list($criteria);
$this->repository->insert($example);
$this->repository->update($example);
```

---

### security.php

**Purpose**: Security and encryption configuration for data protection

```php
<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Security\EncryptionService;
use App\Infrastructure\Security\EncryptionServiceInterface;

// @var array $params

return [
    EncryptionServiceInterface::class => [
        'class' => EncryptionService::class,
        '__construct()' => [
            'key' => $params['app.security.encryption_key'],
            'cipher' => $params['app.security.cipher'] ?? 'aes-256-gcm',
        ],
    ],
];
```

**Key Components**:
- `EncryptionService`: Data encryption service
- `EncryptionServiceInterface`: Encryption service contract

**Configuration Parameters**:
```bash
# In .env file
app.security.encryption_key=your-encryption-key-here
app.security.cipher=aes-256-gcm
```

**Usage Example**:
```php
// Encrypting sensitive data
$encrypted = $this->encryptionService->encrypt($sensitiveData);
$decrypted = $this->encryptionService->decrypt($encrypted);
```

---

### seed.php

**Purpose**: Database seeding configuration for development and testing

```php
<?php

declare(strict_types=1);

// Console Layer
use App\Console\SeedExampleCommand;

// PSR Interfaces
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    SeedExampleCommand::class => [
        'class' => SeedExampleCommand::class,
        '__construct()' => [
            Reference::to(ClockInterface::class),
            Reference::to(ConnectionInterface::class),
        ],
    ],
];
```

**Key Components**:
- `SeedExampleCommand`: Database seeding command
- Dependencies for seeding operations

**Features**:
- Environment-restricted seeding
- Flexible data generation
- Transaction-based operations

**Usage Example**:
```bash
# Run seeding command
./yii seed:example
./yii seed:example --count=20 --truncate
```

---

### service.php

**Purpose**: Application services configuration placeholder

```php
<?php

declare(strict_types=1);

return [
    // Service DI configuration
    // Add your service definitions here
];
```

**Usage Example**:
```php
// Add application services
return [
    UserService::class => [
        'class' => UserService::class,
        '__construct()' => [
            Reference::to(UserRepositoryInterface::class),
            Reference::to(PasswordEncoderInterface::class),
        ],
    ],
];
```

---

### translator.php

**Purpose**: Translation and localization configuration for multi-language support

```php
<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Translator\MessageSourceInterface;
use Yiisoft\Translator\Message\Php\MessageSource;

return [
    TranslatorInterface::class => Translator::class,
    MessageSourceInterface::class => [
        'class' => MessageSource::class,
        '__construct()' => [
            'basePath' => '@resources/messages',
            'category' => 'app',
        ],
    ],
];
```

**Key Components**:
- `Translator`: Translation service
- `MessageSource`: Message source implementation

**Configuration**:
```php
// Message source paths
'basePath' => '@resources/messages',
'category' => 'app',
```

**Usage Example**:
```php
// Translating messages
$translated = $this->translator->translate('Welcome!', 'app', 'en');
$translated = $this->translator->translate('Welcome!', 'app', 'id');
```

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
// ✅ Use environment variables
return [
    JwtService::class => [
        '__construct()' => [
            'secret' => $params['app.jwt.secret'],
            'algorithm' => $params['app.jwt.algorithm'],
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
// ✅ Store secrets in environment variables
'secret' => $params['app.jwt.secret'],
'encryption_key' => $params['app.security.encryption_key'],
```

#### **Access Control**
```php
// ✅ Configure RBAC properly
AccessCheckerInterface::class => AccessChecker::class,
RoleCollectorInterface::class => RoleCollector::class,
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
// In application service
final class ExampleApplicationService
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
        private DomainValidator $domainService
    ) {}
}
```

### 2. **Repository Usage**

```php
// In application service
$example = $this->repository->findById($id);
if ($example === null) {
    throw new NotFoundException('Example not found');
}
```

### 3. **Security Integration**

```php
// In controller
if (!$this->authorizer->can('example.create')) {
    throw new ForbiddenException('Access denied');
}
```

### 4. **Audit Logging**

```php
// In repository
$this->auditService->log(
    tableName: 'example',
    recordId: $example->getId(),
    action: 'create',
    newValues: $example->toArray(),
    actor: $this->actor
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
$config = require 'config/common/di/service.php';
if (!is_array($config)) {
    throw new \RuntimeException('Invalid configuration');
}
```

---

## 📚 References

### Documentation
- **[Yii3 DI Documentation](https://www.yiiframework.com/doc/guide/2.0/en/concept-di-container.html)**: Dependency injection container
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
