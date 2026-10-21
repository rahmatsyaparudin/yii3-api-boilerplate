# 📁 **Struktur Lengkap Project Yii3 API dengan DDD + Optimistic Locking**

## **🏗️ ROOT DIRECTORY**
```
yii3-api-boilerplate/
├── 📄 .dockerignore - Aturan ignore Docker
├── 📄 .editorconfig - Konfigurasi editor
├── 📄 .env - Environment variables
├── 📄 .env.example - Contoh environment variables
├── 📄 .gitignore - Aturan ignore Git
├── 📄 .php-cs-fixer.php - Konfigurasi PHP CS Fixer
├── 📄 Makefile - Otomasi build
├── 📄 QUALITY_SUMMARY.md - Ringkasan kualitas kode
├── 📄 c3.php - Konfigurasi Codeception (coverage)
├── 📄 codeception.yml - Konfigurasi Codeception
├── 📄 composer-dependency-analyser.php - Analisis dependency
├── 📄 composer.json - Dependency PHP
├── 📄 composer.lock - Dependency yang terkunci
├── 📄 composer-template.json - Template composer
├── 📄 infection.json.dist - Konfigurasi mutation testing
├── 📄 psalm.xml - Konfigurasi static analysis
├── 📄 quality - Script quality assurance
├── 📄 rector.php - Konfigurasi refactoring PHP
├── 📄 yii - Yii CLI executable
└── 📄 yii.bat - Yii CLI untuk Windows
```

## **📁 CONFIGURATION**
```
config/
├── 📄 .gitignore - Git ignore untuk config
├── 📄 configuration.php - Konfigurasi utama
├── 📁 common/ - Konfigurasi bersama
│   ├── 📄 access.php - Konfigurasi access control
│   ├── 📄 aliases.php - Alias class
│   ├── 📄 application.php - Konfigurasi aplikasi
│   ├── 📄 middleware.php - Middleware stack
│   ├── 📄 params.php - Parameter aplikasi
│   ├── 📄 routes.php - Definisi route
│   ├── 📄 security.php - Konfigurasi keamanan
│   └── 📁 di/ - Dependency injection
│       ├── 📄 access-di.php - DI access control
│       ├── 📄 application.php - DI aplikasi
│       ├── 📄 audit.php - DI service audit
│       ├── 📄 db-mongodb.php - DI MongoDB
│       ├── 📄 db-pgsql.php - DI PostgreSQL
│       ├── 📄 db-redis.php - DI Redis
│       ├── 📄 error-handler.php - DI error handling
│       ├── 📄 hydrator.php - DI data hydrator
│       ├── 📄 infrastructure.php - DI infrastructure
│       ├── 📄 json.php - DI service JSON
│       ├── 📄 jwt.php - DI service JWT
│       ├── 📄 logger.php - DI logger
│       ├── 📄 middleware.php - DI middleware
│       ├── 📄 monitoring.php - DI monitoring
│       ├── 📄 repository.php - DI repository
│       ├── 📄 router.php - DI router
│       ├── 📄 security.php - DI security
│       ├── 📄 service.php - DI service
│       └── 📄 translator.php - DI translator
├── 📁 console/ - Konfigurasi console
│   ├── 📄 commands.php - Console commands
│   └── 📄 params.php - Parameter console
├── 📁 environments/ - Konfigurasi per-environment
│   ├── 📁 dev/ - Environment development
│   │   └── 📄 params.php - Parameter dev
│   ├── 📁 prod/ - Environment production
│   │   └── 📄 params.php - Parameter prod
│   └── 📁 test/ - Environment test
│       └── 📄 params.php - Parameter test
└── 📁 web/ - Konfigurasi web
    ├── 📁 di/ - DI web
    │   ├── 📄 application.php - DI aplikasi web (middleware stack)
    │   └── 📄 psr17.php - DI PSR-17
    └── 📄 params.php - Parameter web
```

## **📁 SOURCE CODE**
```
src/
├── 📄 bootstrap.php - Bootstrap: load vendor autoload + .env
├── 📄 Environment.php - Manajemen environment
├── 📁 Api/ - API layer
│   ├── 📄 IndexAction.php - Action root API
│   ├── 📁 Shared/ - Komponen API bersama
│   │   ├── 📄 ExceptionResponderFactory.php - Factory response exception
│   │   ├── 📄 NotFoundMiddleware.php - Middleware 404
│   │   ├── 📄 ResponseFactory.php - Factory response
│   │   └── 📁 Presenter/ - Response presenter
│   │       ├── 📄 AsIsPresenter.php - Presenter pass-through
│   │       ├── 📄 CollectionPresenter.php - Presenter collection
│   │       ├── 📄 FailPresenter.php - Presenter error
│   │       ├── 📄 OffsetPaginatorPresenter.php - Presenter pagination
│   │       ├── 📄 PresenterInterface.php - Kontrak presenter
│   │       ├── 📄 SuccessPresenter.php - Presenter sukses
│   │       ├── 📄 SuccessWithMetaPresenter.php - Presenter sukses dengan metadata
│   │       └── 📄 ValidationResultPresenter.php - Presenter hasil validasi
│   └── 📁 V1/ - API v1 endpoints
│       ├── 📁 Example/ - API modul Example
│       │   ├── 📁 Action/ - Action Example
│       │   │   ├── 📄 ExampleCreateAction.php - Endpoint create example
│       │   │   ├── 📄 ExampleDataAction.php - Endpoint data example
│       │   │   ├── 📄 ExampleDeleteAction.php - Endpoint delete example
│       │   │   ├── 📄 ExampleRestoreAction.php - Endpoint restore example
│       │   │   ├── 📄 ExampleUpdateAction.php - Endpoint update example
│       │   │   └── 📄 ExampleViewAction.php - Endpoint view example
│       │   └── 📁 Validation/ - Validasi input
│       │       └── 📄 ExampleInputValidator.php - Validator input example
│       ├── 📁 AnotherExample/ - API modul AnotherExample (child dari Example)
│       │   ├── 📁 Action/ - Action AnotherExample
│       │   │   ├── 📄 AnotherExampleCreateAction.php - Endpoint create
│       │   │   ├── 📄 AnotherExampleDataAction.php - Endpoint data
│       │   │   ├── 📄 AnotherExampleDeleteAction.php - Endpoint delete
│       │   │   ├── 📄 AnotherExampleRestoreAction.php - Endpoint restore
│       │   │   ├── 📄 AnotherExampleUpdateAction.php - Endpoint update
│       │   │   └── 📄 AnotherExampleViewAction.php - Endpoint view
│       │   └── 📁 Validation/ - Validasi input
│       │       └── 📄 AnotherExampleInputValidator.php - Validator input
│       └── 📁 System/ - API modul System
│           ├── 📁 Cache/ - Endpoint Cache
│           │   └── 📁 Action/ - Action cache
│           │       └── 📄 CachePurgeAction.php - Purge semua store cache
│           └── 📁 ServiceToken/ - Endpoint Service Token
│               ├── 📁 Action/ - Action service token
│               │   ├── 📄 ServiceTokenCreateAction.php - Issue token (plaintext tampil sekali)
│               │   ├── 📄 ServiceTokenDataAction.php - List token
│               │   ├── 📄 ServiceTokenDeleteAction.php - Revoke token (soft delete)
│               │   ├── 📄 ServiceTokenUpdateAction.php - Update + rotasi token
│               │   └── 📄 ServiceTokenViewAction.php - Detail token
│               └── 📁 Validation/ - Validasi input
│                   └── 📄 ServiceTokenInputValidator.php - Validator input service token
├── 📁 Application/ - Application layer
│   ├── 📁 Example/ - Modul Example
│   │   ├── 📄 ExampleApplicationService.php - Business logic Example
│   │   ├── 📁 Command/ - Command object
│   │   │   ├── 📄 CreateExampleCommand.php - Command create example
│   │   │   └── 📄 UpdateExampleCommand.php - Command update example
│   │   └── 📁 Dto/ - Data transfer object
│   │       └── 📄 ExampleResponse.php - Response DTO example
│   ├── 📁 AnotherExample/ - Modul AnotherExample
│   │   ├── 📄 AnotherExampleApplicationService.php - Business logic
│   │   ├── 📄 AnotherExampleDetailInfoFactory.php - Factory detail_info custom
│   │   ├── 📁 Command/ - Command object
│   │   │   ├── 📄 CreateAnotherExampleCommand.php - Command create
│   │   │   └── 📄 UpdateAnotherExampleCommand.php - Command update
│   │   └── 📁 Dto/ - Data transfer object
│   │       ├── 📄 AnotherExampleDetailInfo.php - DTO detail_info (embed parent)
│   │       └── 📄 AnotherExampleResponse.php - Response DTO
│   ├── 📁 System/ - Modul System
│   │   ├── 📁 Cache/ - Cache purge
│   │   │   └── 📄 CacheApplicationService.php - Purge semua store cache
│   │   └── 📁 ServiceToken/ - Service Token
│   │       ├── 📄 ServiceTokenApplicationService.php - Issue/rotasi/revoke + authenticate()
│   │       └── 📁 Dto/ - Data transfer object
│   │           └── 📄 ServiceTokenResponse.php - Response DTO service token
│   └── 📁 Shared/ - Komponen application bersama
│       ├── 📁 Core/ - Komponen core bersama
│       │   └── 📁 Factory/ - Application factory
│       │       ├── 📄 DetailInfoFactory.php - Factory audit trail
│       │       └── 📄 SearchCriteriaFactory.php - Factory search criteria
│       └── 📁 Common/ - Helper bersama project
├── 📁 Console/ - Console commands
│   ├── 📁 Core/ - Console command skeleton (frozen)
│   │   ├── 📄 HelloCommand.php - Command hello world
│   │   ├── 📄 MigrateModuleCommand.php - Migrasi terisolasi per modul (migrate:module)
│   │   ├── 📄 MigrationGuardCommand.php - Blokir command destruktif di prod
│   │   └── 📄 SeederCommand.php - Command data seeding
│   └── 📁 Common/ - Console command project
├── 📁 Domain/ - Domain layer
│   ├── 📁 Example/ - Bounded context Example
│   │   ├── 📁 Entity/ - Domain entity
│   │   │   └── 📄 Example.php - Entity Example dengan optimistic locking
│   │   ├── 📁 Repository/ - Kontrak repository
│   │   │   └── 📄 ExampleRepositoryInterface.php - Interface repository Example
│   │   └── 📁 Service/ - Domain service
│   │       └── 📄 ExampleDomainService.php - Domain service Example
│   ├── 📁 AnotherExample/ - Bounded context AnotherExample (child dari Example)
│   │   ├── 📁 Entity/ - Domain entity
│   │   │   └── 📄 AnotherExample.php - Entity dengan example_id + SyncFlag
│   │   ├── 📁 Repository/ - Kontrak repository
│   │   │   └── 📄 AnotherExampleRepositoryInterface.php - Interface repository
│   │   └── 📁 Service/ - Domain service
│   │       └── 📄 AnotherExampleDomainService.php - Domain service
│   ├── 📁 System/ - Bounded context System
│   │   └── 📁 ServiceToken/ - Service Token
│   │       ├── 📁 Entity/ - Domain entity
│   │       │   └── 📄 ServiceToken.php - Entity service token (hanya hash SHA-256)
│   │       └── 📁 Repository/ - Kontrak repository
│   │           └── 📄 ServiceTokenRepositoryInterface.php - Interface repository service token
│   └── 📁 Shared/ - Komponen domain bersama
│       ├── 📁 Core/ - Komponen core bersama
│       │   ├── 📁 Audit/ - Kontrak audit
│       │   │   └── 📄 AuditServiceInterface.php - Kontrak service audit
│       │   ├── 📁 Concerns/ - Concern yang bisa dipakai ulang
│       │   │   ├── 📁 Entity/ - Entity concern
│       │   │   │   ├── 📄 ChangeLogged.php - Trait audit trail
│       │   │   │   ├── 📄 Descriptive.php - Trait name/description
│       │   │   │   ├── 📄 Identifiable.php - Trait ID
│       │   │   │   └── 📄 Stateful.php - Trait manajemen status
│       │   │   └── 📁 Service/ - Service concern
│       │   │       └── 📄 DomainValidator.php - Validasi domain
│       │   ├── 📁 Contract/ - Kontrak domain
│       │   │   ├── 📄 ActorInterface.php - Kontrak actor
│       │   │   ├── 📄 CurrentUserInterface.php - Kontrak current user
│       │   │   └── 📄 DateTimeProviderInterface.php - Kontrak DateTime provider
│       │   ├── 📁 Enum/ - Domain enum
│       │   │   ├── 📄 SyncDirection.php - Enum arah sync
│       │   │   └── 📄 SyncStatus.php - Enum status sync
│       │   ├── 📁 Security/ - Kontrak security
│       │   │   └── 📄 AuthorizerInterface.php - Kontrak authorization
│       │   └── 📁 ValueObject/ - Value object
│       │       ├── 📄 DetailInfo.php - VO audit trail
│       │       ├── 📄 LockVersion.php - VO optimistic locking
│       │       ├── 📄 ResourceStatus.php - VO status entity
│       │       ├── 📄 SyncMdb.php - VO flag sync MongoDB
│       │       └── 📄 SyncFlag.php - VO flag/arah sync origin
│       └── 📁 Common/ - Helper domain bersama
├── 📁 Infrastructure/ - Infrastructure layer
│   ├── 📁 Core/ - Core infrastructure
│   │   ├── 📁 Audit/ - Implementasi audit
│   │   │   └── 📄 DatabaseAuditService.php - Service audit database
│   │   ├── 📁 Cache/ - Managed cache
│   │   │   └── 📄 DatabaseCache.php - remember/refresh/forget atas PSR-16
│   │   ├── 📁 Clock/ - Manajemen waktu
│   │   │   └── 📄 SystemClock.php - Implementasi system clock
│   │   ├── 📁 Concerns/ - Infrastructure concern
│   │   │   ├── 📄 Auditable.php - Concern auditable
│   │   │   └── 📄 HasCoreFeatures.php - Concern core features
│   │   ├── 📁 Database/ - Implementasi database
│   │   │   ├── 📁 MongoDB/ - Implementasi MongoDB
│   │   │   │   ├── 📄 AbstractMongoDBRepository.php - Base repository MongoDB
│   │   │   │   └── 📄 MongoDBService.php - Service MongoDB
│   │   │   └── 📁 Redis/ - Implementasi Redis
│   │   │       ├── 📄 AbstractRedisRepository.php - Base repository Redis
│   │   │       └── 📄 RedisService.php - Service Redis
│   │   ├── 📁 Monitoring/ - Monitoring & observability
│   │   │   ├── 📄 CustomMonitoringService.php - Monitoring custom
│   │   │   ├── 📄 ErrorMonitoringMiddleware.php - Middleware error monitoring
│   │   │   ├── 📄 MetricsMiddleware.php - Middleware koleksi metrics
│   │   │   ├── 📄 MonitoringServiceInterface.php - Kontrak monitoring
│   │   │   ├── 📄 RequestIdMiddleware.php - Middleware request ID
│   │   │   └── 📄 StructuredLoggingMiddleware.php - Structured logging
│   │   ├── 📁 RateLimit/ - Rate limiting
│   │   │   └── 📄 DatabaseRateLimiter.php - Rate limiter database
│   │   ├── 📁 Security/ - Implementasi security
│   │   │   ├── 📄 AccessChecker.php - Access control checker
│   │   │   ├── 📄 Actor.php - Implementasi actor
│   │   │   ├── 📄 ActorProvider.php - Provider actor
│   │   │   ├── 📄 CurrentUser.php - Implementasi current user
│   │   │   ├── 📄 CurrentUserAwareInterface.php - Kesadaran current user
│   │   │   ├── 📄 HstsMiddleware.php - Middleware HSTS
│   │   │   ├── 📄 JwtService.php - Service JWT
│   │   │   ├── 📄 PermissionChecker.php - Permission checker
│   │   │   ├── 📄 RbacAuthorizer.php - Authorizer RBAC
│   │   │   └── 📁 Rule/ - Aturan authorization
│   │   │       └── 📄 PermissionMapRule.php - Aturan permission mapping
│   │   ├── 📁 Seeder/ - Infrastructure seeder
│   │   │   ├── 📄 AbstractSeederData.php - Base class seeder
│   │   │   └── 📄 SeederProviderInterface.php - Kontrak provider seeder
│   │   └── 📁 Time/ - Infrastructure waktu
│   │       └── 📄 AppDateTimeProvider.php - Provider DateTime
│   └── 📁 Common/ - Common infrastructure
│       └── 📁 Persistence/ - Persistensi data
│           ├── 📁 Example/ - Persistensi Example
│           │   ├── 📄 ExampleRepository.php - Repository Example dengan optimistic locking
│           │   └── 📄 MdbExampleSchema.php - Schema MongoDB untuk Example
│           ├── 📁 AnotherExample/ - Persistensi AnotherExample
│           │   ├── 📄 AnotherExampleRepository.php - Repository AnotherExample
│           │   └── � MdbAnotherExampleSchema.php - Schema MongoDB
│           └── 📁 System/ - Persistensi System
│               └── 📄 ServiceTokenRepository.php - Repository service token
├── 📁 Migration/ - Migrasi database (subfolder per modul, di-map di config/common/migration.php)
│   ├── 📁 Auditable/ - Terisolasi: audit_logs + rate_limits (migrate:module auditable)
│   │   ├── 📄 M20240101000000CreateAuditLogsTable.php - Tabel audit_logs
│   │   └── 📄 M20240101000001CreateRateLimitsTable.php - Tabel rate_limits
│   ├── 📁 Example/ - Terisolasi: tabel modul Example (migrate:module example)
│   │   ├── 📄 M20240101000000CreateExampleTable.php - Tabel example
│   │   └── 📄 M20260910104729CreateAnotherExampleTable.php - Tabel another_example
│   └── 📁 ServiceToken/ - Terisolasi: service_tokens (migrate:module serviceToken)
│       └── 📄 M20261007000000CreateServiceTokensTable.php - Tabel service_tokens
└── 📁 Shared/ - Komponen bersama
    ├── 📄 ApplicationParams.php - Parameter aplikasi
    ├── 📁 Common/ - Helper bersama project
    │   └── 📁 Context/ - Context object
    │       └── 📄 ValidationContext.php - Konteks validasi
    └── 📁 Core/ - Komponen core bersama
        ├── 📁 Dto/ - DTO bersama
        │   ├── 📄 PaginatedResult.php - DTO hasil pagination
        │   └── 📄 SearchCriteria.php - DTO search criteria
        ├── 📁 Enums/ - Enum bersama
        │   ├── 📄 AppConstants.php - Konstanta aplikasi
        │   └── 📄 RecordStatus.php - Enum status record
        ├── 📁 ErrorHandler/ - Error handling
        │   └── 📄 ErrorHandlerResponse.php - Formatter response error
        ├── 📁 Exception/ - Hierarki exception
        │   ├── 📄 BadRequestException.php - 400 Bad Request
        │   ├── 📄 ConflictException.php - 409 Conflict
        │   ├── 📄 ForbiddenException.php - 403 Forbidden
        │   ├── 📄 HttpException.php - Base HTTP exception
        │   ├── 📄 NoChangesException.php - Exception tidak ada perubahan
        │   ├── 📄 NotFoundException.php - 404 Not Found
        │   ├── 📄 OptimisticLockException.php - Konflik optimistic locking
        │   ├── 📄 ServiceException.php - Exception service
        │   ├── 📄 TooManyRequestsException.php - 429 Too Many Requests
        │   ├── 📄 UnauthorizedException.php - 401 Unauthorized
        │   └── 📄 ValidationException.php - Error validasi
        ├── 📁 Middleware/ - Middleware bersama
        │   ├── 📄 AccessMiddleware.php - Middleware access control
        │   ├── 📄 CorsMiddleware.php - Middleware CORS
        │   ├── 📄 JwtMiddleware.php - Middleware autentikasi JWT
        │   ├── 📄 ServiceTokenAuthMiddleware.php - Dispatcher bearer (service token / JWT)
        │   ├── 📄 QueryPathParamMiddleware.php - Buang key query path-like (rewrite nginx)
        │   ├── 📄 RateLimitMiddleware.php - Middleware rate limiting (in-memory)
        │   ├── 📄 RequestParamsMiddleware.php - Middleware request parameters
        │   ├── 📄 SecureHeadersMiddleware.php - Middleware security headers
        │   └── 📄 TrustedHostMiddleware.php - Middleware trusted host
        ├── 📁 Query/ - Utilitas query
        │   └── 📄 QueryConditionApplier.php - Builder kondisi query
        ├── 📁 Request/ - Utilitas request
        │   ├── 📄 DataParserInterface.php - Kontrak data parser
        │   ├── 📄 PaginationParams.php - Parameter pagination
        │   ├── 📄 RawParams.php - Parameter request mentah
        │   ├── 📄 RequestDataParser.php - Parser data request
        │   ├── 📄 RequestParams.php - Handler request parameters
        │   └── 📄 SortParams.php - Parameter sorting
        ├── 📁 Security/ - Utilitas security
        │   └── 📄 InputSanitizer.php - Sanitasi input
        ├── 📁 Utility/ - Utilitas umum
        │   ├── 📄 Arrays.php - Utilitas array
        │   ├── 📄 FieldMapper.php - Utilitas mapping field row
        │   └── 📄 JsonHandler.php - Handler data JSON
        ├── 📁 Validation/ - Utilitas validasi
        │   ├── 📄 AbstractValidator.php - Validator abstrak
        │   ├── 📄 ValidationContextInterface.php - Kontrak konteks validasi
        │   └── 📁 Rules/ - Validation rule custom
        │       ├── 📄 HasNoDependencies.php - Rule tanpa dependency
        │       ├── 📄 HasNoDependenciesHandler.php - Handler rule
        │       ├── 📄 UniqueValue.php - Rule unique value
        │       └── 📄 UniqueValueHandler.php - Handler rule
        └── 📁 ValueObject/ - Shared value object
            ├── 📄 LockVersionConfig.php - VO konfigurasi optimistic lock
            └── 📄 Message.php - VO pesan translasi
```

## **📁 RESOURCES**
```
resources/
└── 📁 messages/ - Internationalization
    ├── 📁 en/ - Pesan Bahasa Inggris
    │   ├── 📄 app.php - Pesan aplikasi (milik project)
    │   ├── 📄 error.php - Pesan error (milik skeleton)
    │   ├── 📄 success.php - Pesan sukses (milik skeleton)
    │   └── 📄 validation.php - Pesan validasi (milik skeleton)
    └── 📁 id/ - Pesan Bahasa Indonesia
        ├── 📄 app.php - Pesan aplikasi (milik project)
        ├── 📄 error.php - Pesan error (milik skeleton)
        ├── 📄 success.php - Pesan sukses (milik skeleton)
        └── 📄 validation.php - Pesan validasi (milik skeleton)
```

## **📁 PUBLIC**
```
public/
├── 📄 favicon.ico - Favicon website
├── 📄 index.php - Entry point aplikasi
└── 📄 robots.txt - Aturan search engine
```

## **📁 DOCKER**
```
docker/
├── 📄 .env - Environment Docker
├── 📄 Dockerfile - Definisi image Docker
├── 📄 compose.yml - Docker Compose dasar
├── 📁 dev/ - Environment development
│   ├── 📄 .env - Environment dev
│   ├── 📄 .gitignore - Git ignore
│   ├── 📄 compose.yml - Konfigurasi compose dev
│   └── 📄 override.env.example - Contoh override env
├── 📁 prod/ - Environment production
│   ├── 📄 .env - Environment prod
│   ├── 📄 .gitignore - Git ignore
│   └── 📄 compose.yml - Konfigurasi compose prod
└── 📁 test/ - Environment test
    ├── 📄 .env - Environment test
    ├── 📄 .gitignore - Git ignore
    └── 📄 compose.yml - Konfigurasi compose test
```

---

## **🎯 FITUR & PATTERN KUNCI**

### **🔐 Implementasi Optimistic Locking**
- **Value Object**: `LockVersion.php` - Penanganan versi type-safe
- **Exception**: `OptimisticLockException.php` - Penanganan konflik
- **Repository**: `ExampleRepository.php` - Optimistic locking di level database
- **API**: `ExampleUpdateAction.php` - Validasi sisi client

### **🏗️ Domain-Driven Design (DDD)**
- **Entity**: Domain object kaya dengan behavior
- **Value Object**: Value object immutable (`LockVersion`, `ResourceStatus`, `DetailInfo`)
- **Repository**: Abstraksi akses data
- **Domain Service**: Service business logic
- **Application Service**: Orkestrasi use case

### **🔀 Perbedaan Modul Example vs AnotherExample**

Keduanya modul demo, tapi mendemokan hal yang berbeda:

| Aspek | `Example` | `AnotherExample` |
|-------|-----------|------------------|
| Peran | Modul referensi dasar | Modul child — mendemokan relasi parent→child |
| Field khusus | `name`, `status`, `detail_info` | + `example_id` (FK ke `example`), `origin_id`, `sync_flag` |
| Sync | `SyncMdb` (MongoDB) saja | `SyncMdb` + `SyncFlag` (master–origin sync) |
| `detail_info` | `DetailInfoFactory` shared | Factory custom `AnotherExampleDetailInfoFactory` + DTO `AnotherExampleDetailInfo` (embed data parent `example`) |
| Migrasi | `src/Migration/Example/` | Juga di `src/Migration/Example/` (satu grup `migrate:module example`) |
| Permission | `example.*` | `another-example.*` (aturan role sama: `$isKasir`/`$isSuperAdmin`) |

Gunakan `Example` sebagai titik awal modul standalone, dan `AnotherExample` sebagai referensi kalau modulmu punya relasi ke entity lain atau butuh sync master–origin.

### **🗄️ Multi-Database Support**
- **PostgreSQL**: Database relasional utama
- **MongoDB**: Document storage NoSQL dengan schema
- **Redis**: Caching dan session storage
- **Abstract Repository**: Base class untuk MongoDB dan Redis

### **🔒 Security & Authentication**
- **JWT Middleware**: Autentikasi berbasis token
- **ServiceTokenAuthMiddleware**: Bearer opaque untuk service eksternal (alternatif JWT tanpa SSO)
- **RBAC**: Access control berbasis role
- **Input Sanitization**: Validasi input menyeluruh
- **Rate Limiting**: Rate limiting berbasis database (opt-in)
- **Security Headers**: HSTS, CORS, trusted hosts

### **📊 Observability & Monitoring**
- **Structured Logging**: Log berformat JSON
- **Error Monitoring**: Pelacakan error terpusat
- **Metrics Collection**: Metrics aplikasi
- **Audit Trail**: Audit logging lengkap
- **Request Tracing**: Propagasi request ID

### **🌐 API Design**
- **RESTful**: Endpoint REST yang bersih
- **Versioning**: API versioning (`/v1/`)
- **Error Handling**: Response error yang konsisten
- **Validation**: Validasi sadar konteks (context-aware)
- **Pagination**: Pagination berbasis offset
- **Filtering & Sorting**: Query parameters

### **🚀 DevOps & Deployment**
- **Docker**: Deployment terkontainer
- **Multi-environment**: Konfigurasi dev, test, prod
- **Quality Gates**: Quality check otomatis
- **CI/CD**: Workflow GitHub Actions

---

## **📈 Statistik Project**
- **Total File**: 200+ file
- **Source Code**: 50.000+ baris PHP
- **Bahasa**: Dukungan Inggris & Indonesia
- **Arsitektur**: Clean DDD dengan hexagonal pattern

---

## **🔧 Komponen Boilerplate**

### **🎯 Template Core Boilerplate**
1. **Pattern Entity**: `src/Domain/Example/Entity/Example.php`
2. **Pattern Repository**: `src/Infrastructure/Common/Persistence/Example/ExampleRepository.php`
3. **Application Service**: `src/Application/Example/ExampleApplicationService.php`
4. **API Action**: `src/Api/V1/Example/Action/*`
5. **Validasi**: `src/Api/V1/Example/Validation/ExampleInputValidator.php`
6. **Command & DTO**: `src/Application/Example/Command/*` & `src/Application/Example/Dto/*`

### **🔄 Komponen Bersama**
1. **Trait**: `src/Domain/Shared/Core/Concerns/Entity/*`
2. **Value Object**: `src/Domain/Shared/Core/ValueObject/*`
3. **Exception**: `src/Shared/Core/Exception/*`
4. **Middleware**: `src/Presentation/Core/Http/Middleware/*`
5. **Validasi**: `src/Shared/Core/Validation/*`

---

**Status: 🎯 Dokumentasi struktur project lengkap dengan DDD + Optimistic Locking!**
