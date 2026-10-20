# Panduan Arsitektur

## 📋 Gambaran Umum

Dokumen ini memberikan gambaran menyeluruh tentang arsitektur proyek Yii3 API, termasuk prinsip desain, struktur direktori, dan interaksi antar komponen.

## 🏗️ Gambaran Arsitektur

### Prinsip Desain

Yii3 API mengikuti prinsip Domain-Driven Design (DDD) dengan lapisan clean architecture:

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

### Pola Arsitektur Utama

- **Domain-Driven Design (DDD)**: Logika bisnis dipisahkan dari urusan infrastruktur
- **Clean Architecture**: Pemisahan yang jelas antar lapisan
- **Dependency Injection**: IoC container untuk mengelola dependensi
- **Repository Pattern**: Abstraksi akses data dari logika domain
- **Command Query Separation**: Operasi baca/tulis dipisahkan
- **Value Objects & Enums**: Primitive domain yang type-safe (`ResourceStatus`, `DetailInfo`, `LockVersion`, `SyncMdb`, `SyncFlag`)

## 📁 Struktur Direktori

### Struktur Direktori Root

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
│   ├── 08-input-validator-guide.md # Input validator guide
│   └── 22-sync-flag-guide.md  # SyncFlag guide
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
│   ├── skeleton-generate-module.php # Module scaffolding script
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
│   │   ├── Core/           # Skeleton console commands (frozen)
│   │   │   ├── HelloCommand.php # Example console command
│   │   │   ├── MigrateModuleCommand.php # Module migration helper
│   │   │   ├── MigrationGuardCommand.php # Blocks destructive commands in prod
│   │   │   └── SeederCommand.php # Database seeder command
│   │   └── Common/         # Project console commands
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
│   └── bootstrap.php       # Bootstrap: loads vendor autoload + .env
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

### Tanggung Jawab Lapisan

#### API Layer (`src/Api/`)
- **Actions**: Kelas invokable yang menangani HTTP request dan mengembalikan response PSR-7
- **Middleware**: Cross-cutting concern (authentication, logging, dll.)
- **Presenters/ResponseFactory**: Pemformatan response (`src/Api/Shared/`)
- **Validation**: Validasi dan sanitasi input (`*/Validation/*InputValidator.php`)

#### Application Layer (`src/Application/`)
- **Application Services**: Mengoordinasikan use case dan workflow
- **Command/Response DTOs**: Data transfer object untuk input/output
- **Factories**: `DetailInfoFactory`, `SearchCriteriaFactory`, `SyncFlagFactory`

#### Domain Layer (`src/Domain/`)
- **Entities**: Objek bisnis inti dengan identity dan behavior
- **Value Objects**: Struktur data immutable (`src/Domain/Shared/Core/ValueObject/`)
- **Enums**: `SyncStatus`, `SyncDirection` (`src/Domain/Shared/Core/Enum/`)
- **Domain Services**: Logika bisnis yang tidak cocok ditempatkan di entity
- **Repositories**: Interface abstraksi akses data
- **Concerns**: Trait entity/service yang dapat dipakai ulang (`Identifiable`, `Stateful`, `Descriptive`, `DomainValidator`)

#### Infrastructure Layer (`src/Infrastructure/`)
- **Repositories**: Implementasi konkret akses data (`src/Infrastructure/Common/Persistence/`)
- **Database**: Koneksi Yiisoft/Db, service MongoDB dan Redis
- **Security**: Authentication, authorization, provider actor/current-user
- **Audit**: Logging dan audit trail (`DatabaseAuditService`)
- **Monitoring**: Request id, structured logging, metrics, middleware error monitoring

## 🔄 Alur Data

### Alur Pemrosesan Request

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

### Contoh: Membuat Entity Example

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

## 🧩 Komponen

### Komponen Inti

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

`src/Domain/AnotherExample/Entity/AnotherExample.php` mengikuti pola yang sama namun
juga membawa `?SyncFlag $syncFlag` (kolom sync master/origin `origin_id` + `sync_flag`)
serta referensi `exampleId` ke `Example`.

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

`AnotherExampleApplicationService` juga meng-inject `SyncFlagFactory` dan membangun
sync flag master/origin dari command (`origin_id` + `sync_flag`, `1` = belum tersinkron):

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

`AnotherExampleRepository` identik bentuknya namun juga memetakan `origin_id` /
`sync_flag` melalui `SyncFlag::fieldOriginId()` / `SyncFlag::fieldSyncFlag()`.

## 🔌 Design Pattern

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

Binding interface → implementasi berada di `config/common/repository.php`, yang juga
me-wire `LockVersionConfig` dan `CurrentUser` ke dalam repository melalui pemanggilan setter.

### Factory Pattern

Factory berada di `src/Application/Shared/Core/Factory/` dan `src/Application/AnotherExample/`:

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

## 🔐 Arsitektur Security

### Authentication & Authorization

```php
// Middleware are defined in config/common/di/middleware-di.php and stacked via
// config/common/middleware.php or per-route group (see config/common/routes.php):
//   JwtMiddleware            – JWT authentication (App\Presentation\Core\Http\Middleware)
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

### Validasi Input

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

## 🚀 Pertimbangan Performa

### Optimasi Database

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

#### Strategi Caching
```php
// PSR-16 cache implementations are available:
//   yiisoft/cache-file  → FileCache (used for DB schema cache)
//   yiisoft/cache-redis → Redis-backed cache (params: 'yiisoft/cache-redis')
// RedisService (App\Infrastructure\Core\Database\Redis) can also be used for
// write-through caching — see AnotherExampleApplicationService::createWithRedisCache().
```

## 📊 Monitoring & Logging

### Logging Aplikasi

```php
// PSR-3 logger injection (yiisoft/log, file target)
public function __construct(
    private LoggerInterface $logger
) {}

$this->logger->info('User created', ['user_id' => $userId, 'ip' => $ip]);
```

### HTTP Monitoring Middleware

Observability pada level request ditangani oleh middleware di
`src/Infrastructure/Core/Monitoring/` (dikonfigurasi di `config/common/di/middleware-di.php`
dan diatur melalui params `app/monitoring`):

- `RequestIdMiddleware` — memberikan/meneruskan header `X-Request-Id`
- `StructuredLoggingMiddleware` — structured logging untuk request/response
- `MetricsMiddleware` — metric response time, jumlah request, status code, dan memori
- `ErrorMonitoringMiddleware` — penangkapan dan pelaporan exception/error

### Penanganan Error

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

### Monitoring Performa

```php
// Performance metrics
$startTime = microtime(true);
$result = $this->complexOperation();
$duration = (microtime(true) - $startTime) * 1000; // milliseconds

$this->logger->info('Operation completed', ['duration' => $duration]);
```

## 🔧 Workflow Development

### Development Lokal

```bash
# Jalankan development server (composer script: @php ./yii serve)
composer serve

# Jalankan semua quality check (PHP CS Fixer + Psalm + unit test + composer audit)
php quality quality:check

# Perbaiki masalah code style secara otomatis
php quality quality:check --fix

# Jalankan test (Codeception)
composer test                       # codecept run — semua suite
vendor/bin/codecept run Unit        # hanya unit suite
php quality test:run --unit         # sama, via quality runner

# Generate laporan coverage
php quality quality:check --coverage
```

### Strategi Testing

#### Unit Test
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

#### API Test (format Cest Codeception)
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

#### Functional Test
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

### Konfigurasi Environment

Environment variable dideklarasikan di `.env` (lihat `.env.example`) dan dibaca oleh
`App\Environment` / `config/common/params.php`.

#### Environment Development
```bash
# Konfigurasi development
APP_ENV=dev
APP_DEBUG=1
```

#### Environment Production
```bash
# Konfigurasi production
APP_ENV=prod
APP_DEBUG=0
```

Variabel penting lainnya: `db.default.*` (driver/host/kredensial SQL),
`db.mongodb.*` (target sync MongoDB), `redis.default.*`, `app.jwt.*`,
`app.cors.*`, `app.optimistic_lock.*`, `app.rateLimit.*`.

### Docker Deployment

#### Dockerfile

`docker/Dockerfile` adalah multi-stage build FrankenPHP:

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

`docker/compose.yml` adalah definisi dasar yang minimal; overlay per-environment berada
di `docker/dev/compose.yml`, `docker/test/compose.yml` dan
`docker/prod/compose.yml`. `Makefile` membungkus task-task umum:

```bash
make up        # docker compose -f docker/compose.yml -f docker/dev/compose.yml up -d
make down      # hentikan environment dev
make test      # jalankan codecept di dalam environment test
make prod-build  # build image production (docker/Dockerfile --target prod)
```

### CI/CD Pipeline

Tidak ada pipeline CI yang di-commit ke template proyek ini. Pipeline pada umumnya
sebaiknya menjalankan pemeriksaan yang sama seperti yang dipakai secara lokal:

```bash
composer install
php quality quality:check            # cs-fixer + psalm + unit test + composer audit
php quality quality:check --coverage # dengan laporan coverage
composer test                        # codecept run
```

## 📚 Maintenance

### Tugas Rutin

#### Mingguan
- Update dependensi: `composer update`
- Jalankan quality check: `php quality quality:check`
- Tinjau tren test coverage
- Periksa security advisory: `composer audit`

#### Bulanan
- Tinjau dan update konfigurasi quality
- Update coding standard
- Tambahkan quality check baru sesuai kebutuhan
- Review optimasi performa

#### Kuartalan
- Update dependensi mayor
- Review threshold quality gate
- Upgrade versi tool
- Meeting review arsitektur

### Update Versi

#### Update Tool
```bash
# Update PHP CS Fixer
composer require --dev friendsofphp/php-cs-fixer

# Update Psalm
composer require --dev vimeo/psalm

# Update Codeception / PHPUnit
composer require --dev codeception/codeception phpunit/phpunit
```

#### Update Konfigurasi
```bash
# Update konfigurasi quality
vim .php-cs-fixer.php
vim psalm.xml
vim quality
```

---

## 🎯 Kesimpulan

Arsitektur Yii3 API mengikuti prinsip clean architecture dengan pemisahan concern yang jelas dan struktur kode yang maintainable. Pendekatan DDD memastikan logika bisnis tetap independen dari urusan infrastruktur, sehingga sistem menjadi lebih testable, maintainable, dan scalable.

Manfaat arsitektur utama:

- **Maintainability**: Batas lapisan yang jelas memudahkan perubahan
- **Testability**: Logika bisnis dapat diuji secara terisolasi
- **Scalability**: Clean architecture mendukung pertumbuhan
- **Flexibility**: Mudah dimodifikasi dan diperluas
- **Quality**: Pemeriksaan otomatis memastikan standar kualitas kode

Arsitektur ini menyediakan fondasi yang solid untuk membangun aplikasi API yang robust dan maintainable dengan Yii3! 🚀
