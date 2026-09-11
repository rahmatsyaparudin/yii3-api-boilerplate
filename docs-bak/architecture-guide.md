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
- **Value Objects & Enums**: Type-safe domain primitives (`ResourceStatus`, `DetailInfo`, `LockVersion`, `SyncMdb`, `SyncFlag`)

## 📁 Directory Structure

### Root Directory Structure

```
yii3-api/
├── config/                 # Application configuration
│   ├── common/             # Shared configuration across environments
│   │   ├── di/             # Dependency injection container configuration
│   │   │   ├── access-di.php # Access control and RBAC configuration
│   │   │   ├── application.php # ApplicationParams service definition
│   │   │   ├── audit.php # Audit trail and logging configuration
│   │   │   ├── db-mongodb.php # MongoDB database configuration
│   │   │   ├── db-mysql.php # MySQL/MariaDB database configuration
│   │   │   ├── db-pgsql.php # PostgreSQL database configuration
│   │   │   ├── db-redis.php # Redis service configuration
│   │   │   ├── error-handler.php # Error handling and exception configuration
│   │   │   ├── hydrator.php # Data hydration and transformation configuration
│   │   │   ├── infrastructure-di.php # Infrastructure services configuration
│   │   │   ├── json.php # JSON serialization and parsing configuration
│   │   │   ├── jwt.php # JWT authentication configuration
│   │   │   ├── logger.php # Logging system configuration
│   │   │   ├── middleware-di.php # HTTP middleware DI definitions
│   │   │   ├── monitoring.php # Application monitoring configuration
│   │   │   ├── optimistic-lock.php # LockVersionConfig wiring
│   │   │   ├── repository-di.php # Repository bindings (delegates to repository.php)
│   │   │   ├── router.php # URL routing configuration
│   │   │   ├── security-di.php # Security services configuration
│   │   │   ├── service-di.php # Application services configuration
│   │   │   ├── translator-di.php # Translation and localization configuration
│   │   │   └── validator.php # Validator configuration
│   │   ├── access.php      # Global access control settings and permissions
│   │   ├── aliases.php     # Path aliases for autoloading
│   │   ├── application.php # Application params (name, version, language)
│   │   ├── infrastructure.php # Infrastructure toggles
│   │   ├── middleware.php  # Global middleware stack
│   │   ├── params.php      # Application parameters (env-driven)
│   │   ├── redis.php       # Project Redis bindings
│   │   ├── repository.php  # Repository interface → implementation map
│   │   ├── routes.php      # Route definitions
│   │   ├── security.php    # Security settings
│   │   ├── service.php     # Service definitions
│   │   └── translator.php  # Translation sources
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
│   └── configuration.php    # yiisoft/config plugin file
├── docker/                 # Docker containerization files
│   ├── dev/                # Development Docker setup
│   │   ├── compose.yml     # Development Docker Compose overlay
│   │   └── override.env.example # Environment variables template
│   ├── prod/               # Production Docker setup
│   │   └── compose.yml     # Production Docker Compose overlay
│   ├── test/               # Testing Docker setup
│   │   └── compose.yml     # Testing Docker Compose overlay
│   ├── Dockerfile          # Multi-stage FrankenPHP image (dev / prod)
│   └── compose.yml         # Base Docker Compose definition
├── docs/                   # Documentation and API reference
│   ├── index.html          # API documentation page
│   ├── Yii3-API.postman_collection.json # Postman collection
│   ├── input-validator-guide.md # Input validator guide
│   └── sync-flag-guide.md  # SyncFlag guide
├── public/                 # Web root directory
│   ├── index.php           # Application entry point
│   ├── robots.txt          # Search engine directives
│   ├── favicon.ico         # Website favicon
│   └── .htaccess.example   # Apache configuration template
├── resources/              # Application resources
│   └── messages/           # Translation files
│       ├── en/             # English translations
│       │   ├── app.php     # Project-owned application messages
│       │   ├── error.php   # Error messages
│       │   ├── success.php # Success messages
│       │   └── validation.php # Validation messages
│       └── id/             # Indonesian translations (same files)
├── scripts/                # Utility and maintenance scripts
│   ├── generate-module.php # Module scaffolding script
│   ├── skeleton-copy-config.php # Skeleton config copier
│   ├── skeleton-copy-examples.php # Example file copier
│   ├── skeleton-scripts.php # Skeleton composer scripts
│   ├── skeleton-update.php # Skeleton update script
│   └── skeleton.version    # Skeleton version marker
├── src/                    # Source code (PSR-4: App\ → src/)
│   ├── Api/                # API layer
│   │   ├── V1/             # API version 1
│   │   │   ├── Example/    # Example endpoints
│   │   │   │   ├── Action/ # Invokable action classes
│   │   │   │   │   ├── ExampleCreateAction.php # Create endpoint
│   │   │   │   │   ├── ExampleUpdateAction.php # Update endpoint
│   │   │   │   │   ├── ExampleDeleteAction.php # Delete endpoint
│   │   │   │   │   ├── ExampleRestoreAction.php # Restore endpoint
│   │   │   │   │   ├── ExampleDataAction.php   # List/search endpoint
│   │   │   │   │   └── ExampleViewAction.php   # View endpoint
│   │   │   │   └── Validation/
│   │   │   │       └── ExampleInputValidator.php # Example validation rules
│   │   │   └── AnotherExample/ # AnotherExample endpoints (Action/ + Validation/)
│   │   ├── Shared/         # Shared API components
│   │   │   ├── Presenter/  # Response presenters (success/fail/paginator)
│   │   │   ├── ExceptionResponderFactory.php # Exception → response factory
│   │   │   ├── NotFoundMiddleware.php # 404 fallback middleware
│   │   │   └── ResponseFactory.php # Standard API response factory
│   │   └── IndexAction.php # Main API index endpoint (/)
│   ├── Application/        # Application layer
│   │   ├── Example/        # Example application services
│   │   │   ├── ExampleApplicationService.php # Main example service
│   │   │   ├── Command/    # Application command objects
│   │   │   │   ├── CreateExampleCommand.php # Create command
│   │   │   │   └── UpdateExampleCommand.php # Update command
│   │   │   └── Dto/        # Application DTOs
│   │   │       └── ExampleResponse.php # Example response DTO
│   │   ├── AnotherExample/ # AnotherExample application services
│   │   │   ├── AnotherExampleApplicationService.php
│   │   │   ├── AnotherExampleDetailInfoFactory.php
│   │   │   ├── Command/    # CreateAnotherExampleCommand, UpdateAnotherExampleCommand
│   │   │   └── Dto/        # AnotherExampleResponse, AnotherExampleDetailInfo
│   │   └── Shared/         # Shared application components
│   │       ├── Common/     # Shared application helpers
│   │       └── Core/Factory/ # Shared factories
│   │           ├── DetailInfoFactory.php # DetailInfo builder (change_log)
│   │           ├── SearchCriteriaFactory.php # RequestParams → SearchCriteria
│   │           └── SyncFlagFactory.php # SyncFlag value object factory
│   ├── Console/            # Console commands
│   │   ├── HelloCommand.php # Example console command
│   │   ├── MigrateModuleCommand.php # Module migration helper
│   │   └── SeederCommand.php # Database seeder command
│   ├── Domain/             # Domain layer
│   │   ├── Example/        # Example domain
│   │   │   ├── Entity/     # Domain entities
│   │   │   │   └── Example.php # Main example entity
│   │   │   ├── Repository/ # Repository interfaces
│   │   │   │   └── ExampleRepositoryInterface.php # Example repository contract
│   │   │   └── Service/    # Domain services
│   │   │       └── ExampleDomainService.php # Example domain logic
│   │   ├── AnotherExample/ # AnotherExample domain (same structure)
│   │   │   ├── Entity/AnotherExample.php
│   │   │   ├── Repository/AnotherExampleRepositoryInterface.php
│   │   │   └── Service/AnotherExampleDomainService.php
│   │   └── Shared/         # Shared domain components
│   │       └── Core/
│   │           ├── Audit/  # Audit contracts
│   │           │   └── AuditServiceInterface.php
│   │           ├── Concerns/ # Domain traits
│   │           │   ├── Entity/ # Entity traits
│   │           │   │   ├── Identifiable.php # Identity + sync_mdb + lock_version trait
│   │           │   │   ├── Stateful.php    # Status management trait
│   │           │   │   ├── Descriptive.php # DetailInfo trait
│   │           │   │   └── ChangeLogged.php # change_log helpers
│   │           │   └── Service/
│   │           │       └── DomainValidator.php # Domain guard helpers trait
│   │           ├── Contract/ # Domain contracts
│   │           │   ├── ActorInterface.php
│   │           │   ├── CurrentUserInterface.php
│   │           │   └── DateTimeProviderInterface.php
│   │           ├── Enum/   # Domain enums
│   │           │   ├── SyncStatus.php    # sync_flag status (null=synced, 1=not synced)
│   │           │   └── SyncDirection.php # Sync direction (master/origin/bidirectional)
│   │           ├── Security/ # Domain security contracts
│   │           │   └── AuthorizerInterface.php
│   │           └── ValueObject/ # Domain value objects
│   │               ├── DetailInfo.php    # JSON detail_info payload
│   │               ├── LockVersion.php   # Optimistic locking version
│   │               ├── ResourceStatus.php # Entity status value object
│   │               ├── SyncFlag.php      # Master/origin sync flag
│   │               └── SyncMdb.php       # MongoDB sync flag
│   ├── Infrastructure/     # Infrastructure layer
│   │   ├── Common/Persistence/ # Concrete repositories (Yiisoft/Db)
│   │   │   ├── Example/    # ExampleRepository + MdbExampleSchema
│   │   │   └── AnotherExample/ # AnotherExampleRepository + MdbAnotherExampleSchema
│   │   └── Core/           # Infrastructure services
│   │       ├── Audit/      # DatabaseAuditService
│   │       ├── Clock/      # SystemClock
│   │       ├── Concerns/   # Repository traits
│   │       │   ├── HasCoreFeatures.php  # Status scoping helpers
│   │       │   ├── HasMongoDBSync.php   # MongoDB sync + sync_mdb marking
│   │       │   ├── ManagesPersistence.php # Optimistic-lock persistence helpers
│   │       │   └── Auditable.php        # Audit logging helper
│   │       ├── Database/   # External storage services
│   │       │   ├── MongoDB/ # MongoDBService, AbstractMongoDBRepository
│   │       │   └── Redis/   # RedisService, AbstractRedisRepository
│   │       ├── Monitoring/ # RequestId/StructuredLogging/Metrics/ErrorMonitoring middleware
│   │       ├── RateLimit/  # DatabaseRateLimiter
│   │       ├── Security/   # Actor, CurrentUser, JwtService, RbacAuthorizer, etc.
│   │       ├── Seeder/     # AbstractSeederData
│   │       └── Time/       # AppDateTimeProvider
│   ├── Migration/          # Database migrations (yiisoft/db-migration)
│   │   ├── Auditable/      # audit_logs + rate_limits tables
│   │   └── Example/        # example + another_example tables
│   ├── Seeder/             # Database seeders
│   │   ├── Faker/          # SeedDataPoolFaker
│   │   ├── Fixtures/       # example.yaml, anotherexample.yaml
│   │   ├── SeedExampleData.php
│   │   └── SeedAnotherExampleData.php
│   ├── Shared/             # Shared utilities
│   │   ├── Common/         # Shared common helpers
│   │   │   └── Context/ValidationContext.php # Validation context constants
│   │   ├── Core/
│   │   │   ├── Dto/        # Data transfer objects
│   │   │   │   ├── PaginatedResult.php # Paginated result DTO
│   │   │   │   └── SearchCriteria.php  # Search criteria DTO
│   │   │   ├── Enums/      # Shared enums
│   │   │   │   ├── AppConstants.php    # Application constants
│   │   │   │   └── RecordStatus.php    # Record status enum
│   │   │   ├── ErrorHandler/ # ErrorHandlerResponse
│   │   │   ├── Exception/  # HTTP exceptions (BadRequest, NotFound, etc.)
│   │   │   ├── Middleware/ # AccessMiddleware, CorsMiddleware, JwtMiddleware,
│   │   │   │               # RateLimitMiddleware, RequestParamsMiddleware,
│   │   │   │               # SecureHeadersMiddleware, TrustedHostMiddleware
│   │   │   ├── Query/      # QueryConditionApplier
│   │   │   ├── Request/    # RequestParams, RawParams, PaginationParams, SortParams
│   │   │   ├── Security/   # InputSanitizer
│   │   │   ├── Utility/    # Arrays, FieldMapper, JsonHandler
│   │   │   ├── Validation/ # AbstractValidator + custom rules
│   │   │   └── ValueObject/ # Message, LockVersionConfig
│   │   └── ApplicationParams.php # Application parameters DTO
│   ├── Environment.php      # Environment variables (APP_ENV, APP_DEBUG, ...)
│   └── autoload.php        # Custom autoloader
├── tests/                  # Test suite (Codeception)
│   ├── Api/                # API tests
│   │   ├── IndexCest.php   # API index test
│   │   └── NotFoundCest.php # Not found test
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
│   │   ├── Api/Shared/    # Api shared component tests
│   │   └── EnvironmentTest.php # Environment test
│   ├── bootstrap.php       # Test bootstrap
│   ├── .gitignore          # Test git ignore patterns
│   ├── Api.suite.yml       # API test suite configuration
│   ├── Console.suite.yml   # Console test suite configuration
│   ├── Functional.suite.yml # Functional test suite configuration
│   └── Unit.suite.yml      # Unit test suite configuration
├── composer.json           # Composer manifest (App\ → src, App\Tests\ → tests)
├── yii                     # Console entry point
├── quality                 # Quality tools runner (PHP CS Fixer, Psalm, Codeception)
├── Makefile                # Docker/Make task runner
├── codeception.yml         # Codeception configuration
├── psalm.xml               # Psalm configuration
├── rector.php              # Rector configuration
├── .env.example            # Environment variable template
└── vendor/                 # Composer dependencies
    └── ...                 # Third-party packages
```

### Layer Responsibilities

#### API Layer (`src/Api/`)
- **Actions**: Invokable classes that handle HTTP requests and return PSR-7 responses
- **Middleware**: Cross-cutting concerns (authentication, logging, etc.)
- **Presenters/ResponseFactory**: Response formatting (`src/Api/Shared/`)
- **Validation**: Input validation and sanitization (`*/Validation/*InputValidator.php`)

#### Application Layer (`src/Application/`)
- **Application Services**: Coordinate use cases and workflows
- **Command/Response DTOs**: Input/output data transfer objects
- **Factories**: `DetailInfoFactory`, `SearchCriteriaFactory`, `SyncFlagFactory`

#### Domain Layer (`src/Domain/`)
- **Entities**: Core business objects with identity and behavior
- **Value Objects**: Immutable data structures (`src/Domain/Shared/Core/ValueObject/`)
- **Enums**: `SyncStatus`, `SyncDirection` (`src/Domain/Shared/Core/Enum/`)
- **Domain Services**: Business logic that doesn't fit in entities
- **Repositories**: Abstract data access interfaces
- **Concerns**: Reusable entity/service traits (`Identifiable`, `Stateful`, `Descriptive`, `DomainValidator`)

#### Infrastructure Layer (`src/Infrastructure/`)
- **Repositories**: Concrete data access implementations (`src/Infrastructure/Common/Persistence/`)
- **Database**: Yiisoft/Db connections, MongoDB and Redis services
- **Security**: Authentication, authorization, actor/current-user providers
- **Audit**: Logging and audit trails (`DatabaseAuditService`)
- **Monitoring**: Request id, structured logging, metrics, error monitoring middleware

## 🔄 Data Flow

### Request Processing Flow

```
HTTP Request
    ↓
Middleware Chain
    ↓
Invokable Action (PSR-15 handler)
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
1. HTTP POST /v1/example/create
2. RequestParamsMiddleware parses the request into a `payload` (RequestParams) attribute
3. ExampleCreateAction whitelists + sanitizes input and runs ExampleInputValidator (CREATE context)
4. ExampleCreateAction builds CreateExampleCommand and calls ExampleApplicationService
5. ApplicationService builds DetailInfo via DetailInfoFactory and creates the Example entity
6. ApplicationService calls ExampleRepository.insert()
7. Repository saves to database, reconstitutes the entity, and syncs to MongoDB
   (sync_mdb is set to 1 when the MongoDB sync fails, null when synced)
8. ResponseFactory wraps ExampleResponse data in a success response
```

## 🧩 Components

### Core Components

#### Domain Entities (`src/Domain/Example/Entity/Example.php`)
```php
final class Example
{
    use Identifiable, Stateful, Descriptive;

    public const RESOURCE = 'Example';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource    = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function create(
        string $name,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(status: $status, resource: self::RESOURCE);

        return new self(null, $name, $status, $detailInfo, $syncMdb, LockVersion::create());
    }

    public static function reconstitute(
        int $id,
        string $name,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?LockVersion $lockVersion = null,
    ): self { /* ... */ }
}
```

`src/Domain/AnotherExample/Entity/AnotherExample.php` follows the same pattern but
additionally carries a `?SyncFlag $syncFlag` (the `origin_id` + `sync_flag` master/origin
sync columns) and an `exampleId` reference to `Example`.

#### Application Service (`src/Application/Example/ExampleApplicationService.php`)
```php
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private ExampleRepositoryInterface $repository,
        private ExampleDomainService $domainService
    ) {}

    public function create(CreateExampleCommand $command): ExampleResponse
    {
        // DetailInfo with change_log created by the factory (uses CurrentUser + clock)
        $detailInfo = $this->detailInfoFactory
            ->create(detailInfo: [])
            ->build();

        // Entity creation (status guarded by guardInitialStatus)
        $data = Example::create(
            name: $command->name,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        // Persistence
        return ExampleResponse::fromEntity(
            entity: $this->repository->insert(entity: $data)
        );
    }
}
```

`AnotherExampleApplicationService` additionally injects `SyncFlagFactory` and builds the
master/origin sync flag from the command (`origin_id` + `sync_flag`, `1` = not synced):

```php
$syncFlag = $this->syncFlagFactory->create(
    originId: $command->originId,
    syncFlag: $command->syncFlag ?? 1,
);

$data = AnotherExample::create(
    name: $command->name,
    status: ResourceStatus::from($command->status),
    detailInfo: $detailInfo,
    exampleId: $command->exampleId,
    syncFlag: $syncFlag,
);
```

#### Repository (`src/Infrastructure/Common/Persistence/Example/ExampleRepository.php`)
```php
final class ExampleRepository implements ExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use HasMongoDBSync;
    use ManagesPersistence;

    public const TABLE_NAME  = 'example';
    public const SEQUENCE_ID = 'example_id_seq';

    public function insert(Example $entity): Example
    {
        return $this->db->transaction(function () use ($entity) {
            $this->db->createCommand()
                ->insert(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(
                        entity: $entity,
                        lockVersion: LockVersion::create()->value()
                    )
                )
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            // Reconstitute with the new ID
            $newEntity = Example::reconstitute(
                id: $newId,
                name: $entity->getName(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            // Push to MongoDB; sets sync_mdb=1 when the sync fails
            $this->syncMongoDB(entity: $newEntity, schemaClass: MdbExampleSchema::class);

            return $newEntity;
        });
    }
}
```

`AnotherExampleRepository` is identical in shape but also maps `origin_id` /
`sync_flag` via `SyncFlag::fieldOriginId()` / `SyncFlag::fieldSyncFlag()`.

## 🔌 Design Patterns

### Repository Pattern

```php
// Interface (Domain Layer) — src/Domain/Example/Repository/ExampleRepositoryInterface.php
interface ExampleRepositoryInterface
{
    public function getResource(): string;
    public function findById(int $id, ?int $status = null): ?Example;
    public function findByName(string $name, ?int $status = null): ?Example;
    public function existsByName(string $name, ?int $status = null): bool;
    public function list(SearchCriteria $criteria): PaginatedResult;
    public function insert(Example $example): Example;
    public function update(Example $example): Example;
    public function delete(Example $example): Example;
    public function restore(int $id): ?Example;
}

// Implementation (Infrastructure Layer) — src/Infrastructure/Common/Persistence/Example/
final class ExampleRepository implements ExampleRepositoryInterface, CurrentUserAwareInterface
{
    public function insert(Example $entity): Example
    {
        // Concrete implementation with Yiisoft/Db
        return $this->db->transaction(function () use ($entity) {
            // Database operations + MongoDB sync
        });
    }
}
```

Interface → implementation bindings live in `config/common/repository.php`, which also
wires `LockVersionConfig` and `CurrentUser` into the repositories via setter calls.

### Factory Pattern

Factories live in `src/Application/Shared/Core/Factory/` and `src/Application/AnotherExample/`:

```php
// DetailInfoFactory — builder-style factory that wraps payloads with change_log audit fields
final class DetailInfoFactory
{
    public function __construct(
        private DateTimeProviderInterface $dateTime,
        private CurrentUser $currentUser
    ) {}

    public function create(array $detailInfo = []): self
    {
        $this->current = DetailInfo::createdLog(
            dateTime: $this->dateTime,
            user: $this->currentUser->getActor()->getUsername(),
            payload: $detailInfo
        );

        return $this; // call ->build() to obtain the DetailInfo value object
    }
}

// SyncFlagFactory — builds SyncFlag value objects (origin_id + sync_flag + direction)
$syncFlag = $syncFlagFactory->create(originId: 5, syncFlag: 1); // from raw values
$syncFlag = $syncFlagFactory->fromRequest($data);               // from request/array input
$syncFlag = $syncFlagFactory->fromRecord($row);                 // from a DB row
$syncFlag = $syncFlagFactory->masterToOrigin(originId: 5);      // master → origin
$syncFlag = $syncFlagFactory->originToMaster(originId: 5);      // origin → master
$syncFlag = $syncFlagFactory->bidirectional(originId: 5);       // both directions
$syncFlag = $syncFlagFactory->synced();                         // already synced

// SearchCriteriaFactory — builds SearchCriteria from parsed RequestParams
$criteria = $searchCriteriaFactory->createFromRequest(
    params: $payload,                                    // RequestParams from the 'payload' attribute
    allowedSort: ['id' => 'id', 'name' => 'name'],
);
```

### Command Query Separation

```php
// Commands (Application Layer) — src/Application/Example/Command/CreateExampleCommand.php
final readonly class CreateExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?array $detailInfo,
    ) {}
}

// CreateAnotherExampleCommand additionally carries:
//   public int $exampleId, public ?int $originId, public ?int $syncFlag

// Response DTOs (Application Layer) — src/Application/Example/Dto/ExampleResponse.php
final readonly class ExampleResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Example $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}

// AnotherExampleResponse additionally exposes example_id, origin_id and sync_flag.
```

## 🔐 Security Architecture

### Authentication & Authorization

```php
// Middleware are defined in config/common/di/middleware-di.php and stacked via
// config/common/middleware.php or per-route group (see config/common/routes.php):
//   JwtMiddleware            – JWT authentication (App\Shared\Core\Middleware)
//   AccessMiddleware         – per-route permission check ('permission' route default)
//   RateLimitMiddleware      – request rate limiting
//   CorsMiddleware, SecureHeadersMiddleware, TrustedHostMiddleware, HstsMiddleware
//   RequestParamsMiddleware  – parses filter/pagination/sort into the 'payload' attribute

// Authorization Service — src/Infrastructure/Core/Security/RbacAuthorizer.php
final class RbacAuthorizer implements AuthorizerInterface
{
    public function __construct(
        private Actor $actor,
        private PermissionChecker $checker
    ) {}

    public function can(string $permission): bool
    {
        return $this->checker->can($this->actor, $permission);
    }
}
```

### Input Validation

```php
// Actions validate input via an AbstractValidator subclass + a ValidationContext
// (src/Api/V1/Example/Validation/ExampleInputValidator.php)
final class ExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'name'   => [new StopOnError([new Required(), new StringValue(), new Length(min: 3, max: 255)])],
                'status' => [new Required(), new Integer(), new In(RecordStatus::draftOnlyStates())],
            ],
            // UPDATE / DELETE / SEARCH contexts ...
            default => [],
        };
    }
}

// In the action:
$this->inputValidator->validate(data: $params, context: ValidationContext::CREATE);

// Input Sanitization — App\Shared\Core\Security\InputSanitizer
$sanitized = InputSanitizer::process($rawInput);
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

#### Query Builder
```php
// Repositories use Yiisoft\Db\Query directly
$row = (new Query($this->db))
    ->from(self::TABLE_NAME)
    ->where(['id' => $id])
    ->andWhere($this->scopeWhereNotDeleted())
    ->one();
```

#### Schema Caching
```php
// DB schema is cached via FileCache + SchemaCache
// (wired in config/common/di/db-pgsql.php / db-mysql.php)
SchemaCache::class => [
    'class'         => SchemaCache::class,
    '__construct()' => [Reference::to(FileCache::class)],
    'setEnabled()'  => [true],
],
```

#### Caching Strategy
```php
// PSR-16 cache implementations are available:
//   yiisoft/cache-file  → FileCache (used for DB schema cache)
//   yiisoft/cache-redis → Redis-backed cache (params: 'yiisoft/cache-redis')
// RedisService (App\Infrastructure\Core\Database\Redis) can also be used for
// write-through caching — see AnotherExampleApplicationService::createWithRedisCache().
```

## 📊 Monitoring & Logging

### Application Logging

```php
// PSR-3 logger injection (yiisoft/log, file target)
public function __construct(
    private LoggerInterface $logger
) {}

$this->logger->info('User created', ['user_id' => $userId, 'ip' => $ip]);
```

### HTTP Monitoring Middleware

Request-level observability is handled by middleware in
`src/Infrastructure/Core/Monitoring/` (configured in `config/common/di/middleware-di.php`
and tuned via the `app/monitoring` params):

- `RequestIdMiddleware` — assigns/propagates the `X-Request-Id` header
- `StructuredLoggingMiddleware` — structured request/response logging
- `MetricsMiddleware` — response time, request count, status code and memory metrics
- `ErrorMonitoringMiddleware` — exception/error capture and reporting

### Error Handling

```php
try {
    $result = $this->riskyOperation();
} catch (\Throwable $e) {
    $this->logger->error('Operation failed', [
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
$duration = (microtime(true) - $startTime) * 1000; // milliseconds

$this->logger->info('Operation completed', ['duration' => $duration]);
```

## 🔧 Development Workflow

### Local Development

```bash
# Start development server (composer script: @php ./yii serve)
composer serve

# Run all quality checks (PHP CS Fixer + Psalm + unit tests + composer audit)
php quality quality:check

# Fix code style issues automatically
php quality quality:check --fix

# Run tests (Codeception)
composer test                       # codecept run — all suites
vendor/bin/codecept run Unit        # unit suite only
php quality test:run --unit         # same, via the quality runner

# Generate coverage report
php quality quality:check --coverage
```

### Testing Strategy

#### Unit Tests
```php
final class ExampleTest extends TestCase
{
    public function testCreate(): void
    {
        $example = Example::create(
            name: 'Test Example',
            status: ResourceStatus::draft(),
            detailInfo: DetailInfo::fromArray([]),
        );

        $this->assertNull($example->getId());
        $this->assertSame('Test Example', $example->getName());
    }
}
```

#### API Tests (Codeception Cest format)
```php
final class IndexCest
{
    public function getHome(ApiTester $I): void
    {
        $I->sendGET('/');
        $I->seeResponseCodeIs(HttpCode::OK);
        $I->seeResponseIsJson();
        $I->seeResponseContainsJson(['status' => 'success']);
    }
}
```

#### Functional Tests
```php
final class HomePageCest
{
    public function base(FunctionalTester $tester): void
    {
        $response = $tester->sendRequest(new ServerRequest(uri: '/'));

        $output = $response->getBody()->getContents();
        assertJson($output);
    }
}
```

## 🚀 Deployment

### Environment Configuration

Environment variables are declared in `.env` (see `.env.example`) and read by
`App\Environment` / `config/common/params.php`.

#### Development Environment
```bash
# Development configuration
APP_ENV=dev
APP_DEBUG=1
```

#### Production Environment
```bash
# Production configuration
APP_ENV=prod
APP_DEBUG=0
```

Other important variables: `db.default.*` (SQL driver/host/credentials),
`db.mongodb.*` (MongoDB sync target), `redis.default.*`, `app.jwt.*`,
`app.cors.*`, `app.optimistic_lock.*`, `app.rateLimit.*`.

### Docker Deployment

#### Dockerfile

`docker/Dockerfile` is a multi-stage FrankenPHP build:

```dockerfile
FROM dunglas/frankenphp:1-php8.2-bookworm AS base
# PHP extensions installed via install-php-extensions
# (opcache, mbstring, intl, dom, ctype, curl, phar, openssl, xml, pdo, ...)

FROM base AS dev
# adds xdebug + composer, runs as non-root 'appuser'

FROM base AS prod-builder
# composer install --no-dev --classmap-authoritative

FROM base AS prod
ENV APP_ENV=prod
# runs as www-data
```

#### Docker Compose

`docker/compose.yml` is a minimal base definition; per-environment overlays live
in `docker/dev/compose.yml`, `docker/test/compose.yml` and
`docker/prod/compose.yml`. The `Makefile` wraps common tasks:

```bash
make up        # docker compose -f docker/compose.yml -f docker/dev/compose.yml up -d
make down      # stop the dev environment
make test      # run codecept inside the test environment
make prod-build  # build the production image (docker/Dockerfile --target prod)
```

### CI/CD Pipeline

No CI pipeline is committed to this project template. A typical pipeline should
run the same checks used locally:

```bash
composer install
php quality quality:check            # cs-fixer + psalm + unit tests + composer audit
php quality quality:check --coverage # with coverage report
composer test                        # codecept run
```

## 📚 Maintenance

### Regular Tasks

#### Weekly
- Update dependencies: `composer update`
- Run quality checks: `php quality quality:check`
- Review test coverage trends
- Check security advisories: `composer audit`

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

# Update Codeception / PHPUnit
composer require --dev codeception/codeception phpunit/phpunit
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
