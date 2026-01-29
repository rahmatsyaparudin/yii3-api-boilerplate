# Architecture Guide

## 📋 Overview

This document provides a comprehensive overview of the Yii3 API project architecture, including design principles, directory structure, and component interactions.

## 🏗️ Architecture Overview

### Design Principles

The Yii3 API follows Domain-Driven Design (DDD) principles with clean architecture layers:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    API Layer (Controllers & Middleware)             │
├─────────────────────────────────────────────────────────────────────┤
│                Application Layer (Services & Use Cases)             │
├─────────────────────────────────────────────────────────────────────┤
│                  Domain Layer (Entities & Business Logic)           │
├─────────────────────────────────────────────────────────────────────┤
│               Infrastructure Layer (Repositories & External APIs)   │
└─────────────────────────────────────────────────────────────────────┘
```

### Key Architectural Patterns

- **Domain-Driven Design (DDD)**: Business logic separated from infrastructure concerns
- **Clean Architecture**: Clear separation between layers
- **Dependency Injection**: IoC container for managing dependencies
- **Repository Pattern**: Abstract data access from domain logic
- **Command Query Separation**: Read/write operations separated
- **Event-Driven Architecture**: Domain events for loose coupling

## 📁 Directory Structure

### Root Directory Structure

```
yii3-api/
├── .github/                # GitHub Actions workflows
│   └── workflows/          # CI/CD pipeline configurations
│       └── quality.yml     # Quality checks pipeline
├── config/                 # Application configuration
│   ├── common/             # Shared configuration across environments
│   │   ├── di/             # Dependency injection container configuration
│   │   │   ├── access-di.php # Access control and RBAC configuration
│   │   │   ├── aliases.php # Path and service aliases
│   │   │   ├── application.php # Application parameters and settings
│   │   │   ├── audit.php # Audit trail and logging configuration
│   │   │   ├── db-mongodb.php # MongoDB database configuration
│   │   │   ├── db-pgsql.php # PostgreSQL database configuration
│   │   │   ├── db-redis.php # Redis cache configuration
│   │   │   ├── error-handler.php # Error handling and exception configuration
│   │   │   ├── hydrator.php # Data hydration and transformation configuration
│   │   │   ├── infrastructure.php # Infrastructure services configuration
│   │   │   ├── json.php # JSON serialization and parsing configuration
│   │   │   ├── jwt.php # JWT authentication configuration
│   │   │   ├── logger.php # Logging system configuration
│   │   │   ├── middleware.php # HTTP middleware stack configuration
│   │   │   ├── monitoring.php # Application monitoring configuration
│   │   │   ├── repository.php # Repository pattern configuration
│   │   │   ├── router.php # URL routing configuration
│   │   │   ├── security.php # Security and encryption configuration
│   │   │   ├── seed.php # Database seeding configuration
│   │   │   ├── service.php # Application services configuration
│   │   │   └── translator.php # Translation and localization configuration
│   │   ├── access.php      # Global access control settings and permissions
│   │   ├── aliases.php     # Path and service aliases for autoloading
│   │   ├── application.php # Main application configuration and settings
│   │   ├── middleware.php  # Global middleware configuration
│   │   ├── params.php      # Application parameters and environment variables
│   │   ├── routes.php      # URL routing configuration and route definitions
│   │   └── security.php    # Security settings and encryption configuration
│   ├── console/            # Console application configuration
│   │   ├── commands.php    # Console command definitions
│   │   └── params.php      # Console parameters
│   ├── environments/       # Environment-specific configurations
│   │   ├── dev/            # Development environment settings
│   │   │   └── params.php # Development parameters and debug settings
│   │   ├── prod/           # Production environment settings
│   │   │   └── params.php # Production parameters and security settings
│   │   └── test/           # Testing environment settings
│   │       └── params.php # Testing parameters and test database settings
│   ├── web/                # Web application configuration
│   │   ├── di/             # Web-specific DI configuration
│   │   │   ├── application.php # Web application services and middleware
│   │   │   └── psr17.php      # PSR-17 HTTP factory configuration
│   │   └── params.php       # Web application parameters and settings
│   ├── .gitignore          # Git ignore patterns
│   └── configuration.php    # Main configuration loader
├── docker/                 # Docker containerization files
│   ├── dev/                # Development Docker setup
│   │   ├── compose.yml     # Development Docker Compose
│   │   └── override.env.example # Environment variables template
│   ├── prod/               # Production Docker setup
│   │   └── compose.yml     # Production Docker Compose
│   ├── test/               # Testing Docker setup
│   │   └── compose.yml     # Testing Docker Compose
│   ├── Dockerfile          # Main Docker image definition
│   ├── compose.yml         # Default Docker Compose
│   └── .dockerignore       # Docker ignore patterns
├── docs/                   # Documentation
│   ├── architecture/       # Architecture documentation
│   │   └── 01-architecture.md # Architecture overview
│   ├── api/                # API documentation
│   │   ├── v1/             # API version 1 documentation
│   │   └── v2/             # API version 2 documentation
│   ├── development/        # Development guides
│   │   ├── README.md       # Development setup guide
│   │   ├── setup.md        # Local development setup
│   │   └── testing.md      # Testing guidelines
│   ├── deployment/         # Deployment documentation
│   │   ├── docker.md       # Docker deployment guide
│   │   └── production.md   # Production deployment
│   ├── architecture-guide.md # Complete architecture guide
│   └── quality-guide.md    # Quality assurance guide
├── public/                 # Web root directory
│   ├── index.php           # Application entry point
│   ├── robots.txt          # Search engine directives
│   └── favicon.ico         # Website favicon
├── resources/              # Application resources
│   ├── messages/           # Translation files
│   │   ├── en/             # English translations
│   │   │   └── validation.php # Validation messages
│   │   └── id/             # Indonesian translations
│   │       └── validation.php # Validation messages
│   └── views/              # View templates (if using views)
├── scripts/                # Utility and maintenance scripts
│   ├── install-skeleton.php # Skeleton installation script
│   ├── skeleton-copy-examples.php # Example file copier
│   └── setup-composer-template.sh # Composer template setup
├── src/                    # Source code
│   ├── Api/                # API layer
│   │   ├── V1/             # API version 1
│   │   │   ├── Action/     # API action classes
│   │   │   │   ├── Example/ # Example-related actions
│   │   │   │   │   ├── ExampleCreateAction.php # Create endpoint
│   │   │   │   │   ├── ExampleUpdateAction.php # Update endpoint
│   │   │   │   │   ├── ExampleDeleteAction.php # Delete endpoint
│   │   │   │   │   ├── ExampleListAction.php # List endpoint
│   │   │   │   │   └── ExampleViewAction.php # View endpoint
│   │   │   │   └── IndexAction.php # API index endpoint
│   │   │   ├── Middleware/ # API middleware
│   │   │   │   ├── AccessMiddleware.php # Access control
│   │   │   │   └── RequestParamsMiddleware.php # Request parameter handling
│   │   │   └── Validator/  # API validators
│   │   │       └── ExampleValidator.php # Example validation rules
│   │   ├── Shared/         # Shared API components
│   │   │   ├── Action/     # Shared action base classes
│   │   │   ├── Middleware/ # Shared middleware
│   │   │   └── Validator/  # Shared validators
│   │   └── IndexAction.php # Main API index endpoint
│   ├── Application/        # Application layer
│   │   ├── Example/        # Example application services
│   │   │   ├── ExampleApplicationService.php # Main example service
│   │   │   ├── Command/    # Application command objects
│   │   │   │   ├── CreateExampleCommand.php # Create command
│   │   │   │   └── UpdateExampleCommand.php # Update command
│   │   │   ├── Factory/    # Application factories
│   │   │   │   └── DetailInfoFactory.php # Detail info factory
│   │   │   └── Response/   # Application response objects
│   │   │       └── ExampleResponse.php # Example response formatter
│   │   └── Shared/         # Shared application components
│   │       ├── Factory/    # Shared factories
│   │       └── Validator/  # Shared validators
│   ├── Console/            # Console commands
│   │   ├── HelloCommand.php # Example console command
│   │   ├── SimpleGenerateCommand.php # Simple generator command
│   │   └── TemplateGeneratorCommand.php # Template generator command
│   ├── Domain/             # Domain layer
│   │   ├── Example/        # Example domain entities
│   │   │   ├── Entity/     # Domain entities
│   │   │   │   └── Example.php # Main example entity
│   │   │   ├── Repository/ # Repository interfaces
│   │   │   │   └── ExampleRepositoryInterface.php # Example repository contract
│   │   │   ├── Service/    # Domain services
│   │   │   │   └── ExampleDomainService.php # Example domain logic
│   │   │   └── ValueObject/ # Domain value objects
│   │   │       ├── DetailInfo.php # Detail information value object
│   │   │       └── Status.php # Status value object
│   │   ├── Shared/         # Shared domain components
│   │   │   ├── Concerns/   # Domain traits
│   │   │   │   └── Entity/ # Entity traits
│   │   │   │       ├── Identifiable.php # Identity trait
│   │   │   │       ├── Stateful.php # State management trait
│   │   │   │       ├── Descriptive.php # Description trait
│   │   │   │       └── OptimisticLock.php # Optimistic locking trait
│   │   │   ├── Service/    # Shared domain services
│   │   │   │   └── DomainValidator.php # Domain validation service
│   │   │   └── ValueObject/ # Shared value objects
│   │   │       ├── LockVersion.php # Lock version value object
│   │   │       └── Message.php # Message value object
│   │   └── ValueObject/    # Global value objects
│   │       └── Status.php # Global status enumeration
│   ├── Environment.php      # Environment configuration
│   ├── autoload.php        # Custom autoloader
│   └── Shared/             # Shared utilities
│       ├── Concerns/       # Shared traits
│       │   └── Service/    # Service traits
│       ├── Exception/      # Shared exceptions
│       │   ├── HttpException.php # Base HTTP exception
│       │   ├── BadRequestException.php # Bad request exception
│       │   ├── NotFoundException.php # Not found exception
│       │   └── OptimisticLockException.php # Optimistic lock exception
│       ├── Middleware/     # Shared middleware
│       │   ├── AccessMiddleware.php # Access control middleware
│       │   └── RequestParamsMiddleware.php # Request parameter middleware
│       ├── Query/          # Query builders and utilities
│       │   └── QueryConditionApplier.php # Query condition applier
│       ├── Request/        # Request handling utilities
│       │   ├── RequestParams.php # Request parameter parser
│       │   └── DataParserInterface.php # Data parser interface
│       ├── Utility/        # General utilities
│       │   ├── Arrays.php # Array utilities
│       │   └── JsonDataHydrator.php # JSON data hydrator
│       ├── ValueObject/    # Shared value objects
│       │   ├── Message.php # Message value object
│       │   └── PaginatedResult.php # Paginated result value object
│       └── Dto/            # Data transfer objects
│           ├── PaginatedResult.php # Paginated result DTO
│           └── SearchCriteria.php # Search criteria DTO
├── tests/                  # Test suite
│   ├── Api/                # API tests
│   │   ├── IndexCest.php   # API index test
│   │   ├── NotFoundCest.php # Not found test
│   │   └── Example/        # Example API tests
│   ├── Console/            # Console tests
│   │   ├── HelloCommandCest.php # Hello command test
│   │   └── YiiCest.php     # Yii framework test
│   ├── Functional/         # Functional tests
│   │   └── HomePageCest.php # Home page functional test
│   ├── Support/            # Test support classes
│   │   ├── _generated/     # Generated test files
│   │   ├── ApiTester.php   # API test helper
│   │   ├── ConsoleTester.php # Console test helper
│   │   ├── FunctionalTester.php # Functional test helper
│   │   └── UnitTester.php  # Unit test helper
│   ├── Unit/              # Unit tests
│   ├── .gitignore          # Test git ignore patterns
│   ├── Api.suite.yml       # API test suite configuration
│   ├── Console.suite.yml   # Console test suite configuration
│   ├── Functional.suite.yml # Functional test suite configuration
│   └── Unit.suite.yml      # Unit test suite configuration
└── vendor/                 # Composer dependencies
    └── ...                 # Third-party packages
```

### Layer Responsibilities

#### API Layer (`src/Api/`)
- **Controllers**: Handle HTTP requests and responses
- **Middleware**: Cross-cutting concerns (authentication, logging, etc.)
- **Request/Response**: Data transfer objects and formatting
- **Validation**: Input validation and sanitization

#### Application Layer (`src/Application/`)
- **Application Services**: Coordinate use cases and workflows
- **Command/Query**: Input/output data transfer objects
- **Factories**: Create complex objects and entities
- **Event Handling**: Domain event processing

#### Domain Layer (`src/Domain/`)
- **Entities**: Core business objects with identity and behavior
- **Value Objects**: Immutable data structures
- **Domain Services**: Business logic that doesn't fit in entities
- **Repositories**: Abstract data access interfaces
- **Domain Events**: Events that represent important occurrences

#### Infrastructure Layer (`src/Infrastructure/`)
- **Repositories**: Concrete data access implementations
- **Database**: Database connections and queries
- **External APIs**: Third-party service integrations
- **Security**: Authentication, authorization, encryption
- **Audit**: Logging and audit trails

## 🔄 Data Flow

### Request Processing Flow

```
HTTP Request
    ↓
Middleware Chain
    ↓
Controller Action
    ↓
Application Service
    ↓
Repository
    ↓
Database/External API
    ↓
Response
```

### Example: Create Example Entity

```
1. HTTP POST /api/v1/examples
2. ExampleCreateAction validates input
3. ExampleCreateAction calls ExampleApplicationService
4. ApplicationService validates business rules
5. ApplicationService creates Example entity
6. ApplicationService calls ExampleRepository.insert()
7. Repository saves to database and syncs to MongoDB
8. Response returned with created entity data
```

## 🧩 Components

### Core Components

#### Domain Entities (`src/Domain/Example/Entity/Example.php`)
```php
final class Example
{
    use Identifiable, Stateful, OptimisticLock;
    
    public function __construct(
        ?int $id,
        string $name,
        Status $status,
        DetailInfo $detailInfo,
        ?int $syncMdb = null,
        ?LockVersion $lockVersion = null
    ) {
        // Entity initialization
    }
    
    public static function create(string $name, Status $status, DetailInfo $detailInfo, ?int $syncMdb = null): self
    {
        self::guardInitialStatus($status, null, self::RESOURCE);
        
        return new self(null, $name, $status, $detailInfo, $hyncMdb, LockVersion::create());
    }
}
```

#### Application Service (`src/Application/Example/ExampleApplicationService.php`)
```php
final class ExampleApplicationService
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
        DomainValidator $domainService,
        DetailInfoFactory $detailInfoFactory
    ) {}
    
    public function create(CreateExampleCommand $command): ExampleResponse
    {
        // Business logic validation
        $this->domainService->validateUniqueValue(
            value: $command->name,
            field: 'name',
            resource: Example::RESOURCE,
            repository: $this->repository,
            excludeId: null
        );
        
        // Entity creation
        $example = Example::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfoFactory->create([])->withApproved()->build(),
            syncMdb: $command->syncMdb !== null ? ($command->syncMdb ? 1 : 0) : null
        );
        
        // Persistence
        return ExampleResponse::fromEntity($this->repository->insert($example));
    }
}
```

#### Repository (`src/Infrastructure/Persistence/Example/ExampleRepository.php`)
```php
final class ExampleRepository implements ExampleRepositoryInterface
{
    use HasCoreFeatures;
    
    public function insert(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            // Database insert
            $this->db->createCommand()
                ->insert(self::TABLE, [
                    'name' => $example->getName(),
                    'status' => $example->getStatus()->value(),
                    'detail_info' => $example->getDetailInfo()->toArray(),
                    'sync_mdb' => $example->getSyncMdb(),
                    'lock_version' => 1,
                ])
                ->execute();
            
            // Get new ID
            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);
            
            // Reconstitute with new ID
            return Example::reconstitute(
                id: $newId,
                name: $example->getName(),
                status: $example->getStatus(),
                detailInfo: $example->getDetailInfo(),
                syncMdb: $example->getSyncMdb(),
                lockVersion: 1
            );
        });
    }
}
```

## 🔌 Design Patterns

### Repository Pattern

```php
// Interface (Domain Layer)
interface ExampleRepositoryInterface
{
    public function insert(Example $example): Example;
    public function update(Example $example): Example;
    public function delete(Example $example): Example;
    public function findById(int $id): ?Example;
    public function list(SearchCriteria $criteria): PaginatedResult;
}

// Implementation (Infrastructure Layer)
final class ExampleRepository implements ExampleRepositoryInterface
{
    public function insert(Example $example): Example
    {
        // Concrete implementation with Yiisoft/Db
        return $this->db->transaction(function() use ($example) {
            // Database operations
        });
    }
}
```

### Factory Pattern

```php
// Domain Factory
final class ExampleFactory
{
    public static function create(array $data): Example
    {
        return Example::create(
            name: $data['name'],
            status: Status::from($data['status']),
            detailInfo: DetailInfo::fromJson($data['detail_info'] ?? []),
            syncMdb: $data['sync_mdb'] ?? null
        );
    }
}
```

### Command Query Separation

```php
// Commands (Application Layer)
final class CreateExampleCommand
{
    public function __construct(
        public readonly string $name,
        public readonly ?int $status,
        public readonly ?array $detailInfo,
        public readonly ?bool $syncMdb
    ) {}
}

// Queries (Application Layer)
final class ExampleResponse
{
    public static function fromEntity(Example $example): array
    {
        return [
            'id' => $example->getId(),
            'name' => $example->getName(),
            'status' => $example->getStatus()->name(),
            'detail_info' => $example->getDetailInfo()->toArray(),
            'sync_mdb' => $example->getSyncMdb(),
            'created_at' => $example->getCreatedAt()?->format('Y-m-d H:i:s'),
            'updated_at' => $example->getUpdatedAt()?->format('Y-m-d H:i:s'),
        ];
    }
}
```

## 🔐 Security Architecture

### Authentication & Authorization

```php
// Middleware Chain
$app->addMiddleware(
    new AuthenticationMiddleware($authenticator),
    new AuthorizationMiddleware($authorizer),
    new RateLimitMiddleware($rateLimiter)
);

// Authorization Service
final class RbacAuthorizer implements AuthorizerInterface
{
    public function can(string $permission): bool
    {
        return $this->checker->can($this->actor, $permission);
    }
}
```

### Input Validation

```php
// Request Validation
final class RequestValidator
{
    public function validate(array $data, ValidationContext $context): void
    {
        $validator = $this->getValidator($context);
        $validator->validate($data);
    }
}

// Input Sanitization
final class InputSanitizer
{
    public function sanitize(array $data): array
    {
        // Sanitize and validate input data
        return $this->processArray($data, 0);
    }
}
```

### Audit Trail

```php
final class DatabaseAuditService implements AuditServiceInterface
{
    public function log(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?ActorInterface $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void {
        // Log audit trail to database
    }
}
```

## 🚀 Performance Considerations

### Database Optimization

#### Query Optimization
```php
// Use query caching
$query = (new Query($this->db))
    ->cache(3600) // Cache for 1 hour
    ->where(['status' => Status::ACTIVE->value()]);
```

#### Connection Pooling
```php
// Database connection pool configuration
'db' => [
    'class' => 'yii\db\Connection',
    'dsn' => 'mysql:host=localhost;dbname=yii3-api',
    'username' => 'root',
    'password' => 'password',
    'charset' => 'utf8mb4',
    'enableCache' => true,
    'enableProfiling' => false,
],
```

#### Caching Strategy
```php
// Multi-level caching
$cache = new FileCache([
    'yii2' => [
        'duration' => 3600, // 1 hour
        'class' => FileCache::class,
    ],
    'db' => [
        'duration' => 600, // 10 minutes
        'class' => DbCache::class,
    ],
]);
```

### Async Operations

```php
// Async processing
public function processAsync(array $data): Promise
{
    return $this->queue->push('process_data', $data);
}

// Queue configuration
'queue' => [
    'class' => \yii\queue\db\Queue::class,
    'db' => 'db',
    'table' => 'queue',
    'channel' => 'default',
],
```

## 📊 Monitoring & Logging

### Application Logging

```php
// Structured logging
$logger = Yii::getLogger();
$logger->info('User created', ['user_id' => $userId, 'ip' => $ip]);

// Contextual logging
Yii::info('Processing request', [
    'method' => $request->getMethod(),
    'url' => $request->getUri(),
    'user_id' => $currentUser?->getId(),
]);
```

### Error Handling

```php
try {
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    Yii::error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```

### Performance Monitoring

```php
// Performance metrics
$startTime = microtime(true);
$result = $this->complexOperation();
$endTime = microtime(true);
$duration = ($endTime - $startTime) * 1000; // milliseconds

Yii::info('Operation completed', ['duration' => $duration]);
```

## 🔧 Development Workflow

### Local Development

```bash
# Start development server
php yii serve

# Run quality checks
php quality

# Run specific tests
vendor/bin/phpunit tests/Unit/ExampleTest.php

# Generate coverage report
php quality --coverage
```

### Testing Strategy

#### Unit Tests
```php
class ExampleRepositoryTest extends TestCase
{
    public function testInsert(): void
    {
        $example = ExampleFactory::create([
            'name' => 'Test Example',
            'status' => Status::ACTIVE->value(),
        ]);
        
        $result = $this->repository->insert($example);
        
        $this->assertNotNull($result);
        $this->assertEquals('Test Example', $result->getName());
    }
}
```

#### Integration Tests
```php
class ExampleApiCest extends ApiTester
{
    public function testCreateExample(): void
    {
        $this->sendPost('/api/v1/examples', [
            'name' => 'Test Example',
            'status' => Status::ACTIVE->value(),
        ]);
        
        $this->seeResponseCode(201);
        $this->seeJsonContains([
            'name' => 'Test Example',
            'status' => 'active',
        ]);
    }
}
```

#### Functional Tests
```php
class HomePageCest extends AcceptanceTester
{
    public function testHomePageLoads(AcceptanceTester $I): void
    {
        $I->amOnPage('/');
        $I->see('Yii3 API');
    }
}
```

## 🚀 Deployment

### Environment Configuration

#### Development Environment
```bash
# Development configuration
APP_ENV=development
APP_DEBUG=true
YII_DEBUG=true
YII_ENV=dev
YII_TRACE_LEVEL=0
```

#### Production Environment
```bash
# Production configuration
APP_ENV=production
APP_DEBUG=false
YII_DEBUG=false
YII_ENV=prod
YII_TRACE_LEVEL=0
```

### Docker Deployment

#### Dockerfile
```dockerfile
FROM php:8.1-fpm-alpine
WORKDIR /app

# Install dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader

# Copy application
COPY . .

# Set permissions
RUN chown -R www-data:www-data
RUN chmod -R 755 storage
RUN chmod -R 777 runtime/cache
RUN chmod -R 777 runtime/logs

# Expose port
EXPOSE 8080

CMD ["php", "yii", "serve"]
```

#### Docker Compose
```yaml
version: '3.8'
services:
  app:
    build: .
    ports:
      - "8080:8080"
    environment:
      - YII_ENV=production
    volumes:
      - .:/app
      - ./runtime:/app/runtime
      - ./logs:/app/logs
    depends_on:
      - db
      - cache
```

### CI/CD Pipeline

#### GitHub Actions
```yaml
name: Quality Check
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: php quality
      - run: php quality --coverage
      - name: Upload coverage reports
        uses: actions/upload-artifact@v3
        with:
          name: coverage-reports
          path: tests/coverage/
```

## 📚 Maintenance

### Regular Tasks

#### Weekly
- Update dependencies: `composer update`
- Run quality checks: `php quality`
- Review test coverage trends
- Check security advisories

#### Monthly
- Review and update quality configuration
- Update coding standards
- Add new quality checks as needed
- Performance optimization review

#### Quarterly
- Major dependency updates
- Quality gate threshold reviews
- Tool version upgrades
- Architecture review meetings

### Version Updates

#### Tool Updates
```bash
# Update PHP CS Fixer
composer require --dev friendsofphp/php-cs-fixer

# Update Psalm
composer require --dev vimeo/psalm

# Update PHPUnit
composer require --dev phpunit/phpunit
```

#### Configuration Updates
```bash
# Update quality configuration
vim .php-cs-fixer.php
vim psalm.xml
vim quality
```

---

## 🎯 Conclusion

The Yii3 API architecture follows clean architecture principles with clear separation of concerns and maintainable code structure. The DDD approach ensures business logic remains independent of infrastructure concerns, making the system more testable, maintainable, and scalable.

Key architectural benefits:

- **Maintainability**: Clear layer boundaries make changes easier
- **Testability**: Business logic can be tested in isolation
- **Scalability**: Clean architecture supports growth
- **Flexibility**: Easy to modify and extend
- **Quality**: Automated checks ensure code quality standards

This architecture provides a solid foundation for building robust and maintainable API applications with Yii3! 🚀
