# 📁 **Struktur Lengkap Project Yii3 API dengan DDD + Optimistic Locking**

## **🏗️ ROOT DIRECTORY**
```
yii3-api/
├── 📄 .dockerignore - Docker ignore rules
├── 📄 .editorconfig - Editor configuration
├── 📄 .env - Environment variables
├── 📄 .env.example - Environment variables example
├── 📄 .gitignore - Git ignore rules
├── 📄 .php-cs-fixer.php - PHP CS Fixer config
├── 📄 Makefile - Build automation
├── 📄 QUALITY_SUMMARY.md - Code quality summary
├── 📄 README-SKELETON.md - Skeleton documentation
├── 📄 c3.php - Codeception config
├── 📄 codeception.yml - Codeception config
├── 📄 composer-dependency-analyser.php - Dependency analysis
├── 📄 composer.json - PHP dependencies
├── 📄 composer.lock - Locked dependencies
├── 📄 composer-template.json - Composer template
├── 📄 infection.json.dist - Mutation testing config
├── 📄 psalm.xml - Static analysis config
├── 📄 quality - Quality assurance script
├── 📄 rector.php - PHP refactoring config
├── 📄 yii - Yii CLI executable
└── 📄 yii.bat - Yii CLI for Windows
```

## **📁 CONFIGURATION**
```
config/
├── 📄 .gitignore - Git ignore for config
├── 📄 configuration.php - Main configuration
├── 📁 common/ - Common configuration
│   ├── 📄 access.php - Access control config
│   ├── 📄 aliases.php - Class aliases
│   ├── 📄 application.php - Application config
│   ├── 📄 middleware.php - Middleware stack
│   ├── 📄 params.php - Application parameters
│   ├── 📄 routes.php - Route definitions
│   ├── 📄 security.php - Security configuration
│   └── 📁 di/ - Dependency injection
│       ├── 📄 access-di.php - Access DI config
│       ├── 📄 application.php - Application DI
│       ├── 📄 audit.php - Audit service DI
│       ├── 📄 db-mongodb.php - MongoDB DI
│       ├── 📄 db-pgsql.php - PostgreSQL DI
│       ├── 📄 db-redis.php - Redis DI
│       ├── 📄 error-handler.php - Error handling DI
│       ├── 📄 hydrator.php - Data hydrator DI
│       ├── 📄 infrastructure.php - Infrastructure DI
│       ├── 📄 json.php - JSON service DI
│       ├── 📄 jwt.php - JWT service DI
│       ├── 📄 logger.php - Logger DI
│       ├── 📄 middleware.php - Middleware DI
│       ├── 📄 monitoring.php - Monitoring DI
│       ├── 📄 repository.php - Repository DI
│       ├── 📄 router.php - Router DI
│       ├── 📄 security.php - Security DI
│       ├── 📄 service.php - Service DI
│       └── 📄 translator.php - Translation DI
├── 📁 console/ - Console configuration
│   ├── 📄 commands.php - Console commands
│   └── 📄 params.php - Console parameters
├── 📁 environments/ - Environment-specific configs
│   ├── 📁 dev/ - Development environment
│   │   └── 📄 params.php - Dev parameters
│   ├── 📁 prod/ - Production environment
│   │   └── 📄 params.php - Prod parameters
│   └── 📁 test/ - Test environment
│       └── 📄 params.php - Test parameters
└── 📁 web/ - Web configuration
    ├── 📁 di/ - Web DI
    │   ├── 📄 application.php - Web application DI
    │   └── 📄 psr17.php - PSR-17 DI
    └── 📄 params.php - Web parameters
```

## **📁 SOURCE CODE**
```
src/
├── 📄 autoload.php - Autoloader configuration
├── 📄 Environment.php - Environment management
├── 📁 Api/ - API layer
│   ├── 📄 IndexAction.php - Root API action
│   ├── 📁 Shared/ - Shared API components
│   │   ├── 📄 ExceptionResponderFactory.php - Exception response factory
│   │   ├── 📄 NotFoundMiddleware.php - 404 middleware
│   │   ├── 📄 ResponseFactory.php - Response factory
│   │   └── 📁 Presenter/ - Response presenters
│   │       ├── 📄 AsIsPresenter.php - Pass-through presenter
│   │       ├── 📄 CollectionPresenter.php - Collection presenter
│   │       ├── 📄 FailPresenter.php - Error presenter
│   │       ├── 📄 OffsetPaginatorPresenter.php - Pagination presenter
│   │       ├── 📄 PresenterInterface.php - Presenter contract
│   │       ├── 📄 SuccessPresenter.php - Success presenter
│   │       ├── 📄 SuccessWithMetaPresenter.php - Success with metadata
│   │       └── 📄 ValidationResultPresenter.php - Validation result presenter
│   └── 📁 V1/ - API v1 endpoints
│       └── 📁 Example/ - Example module API
│           ├── 📁 Action/ - Example actions
│           │   ├── 📄 ExampleCreateAction.php - Create example endpoint
│           │   ├── 📄 ExampleDataAction.php - Example data endpoint
│           │   ├── 📄 ExampleDeleteAction.php - Delete example endpoint
│           │   ├── 📄 ExampleRestoreAction.php - Restore example endpoint
│           │   ├── 📄 ExampleUpdateAction.php - Update example endpoint
│           │   └── 📄 ExampleViewAction.php - View example endpoint
│           └── 📁 Validation/ - Input validation
│               └── 📄 ExampleInputValidator.php - Example input validator
├── 📁 Application/ - Application layer
│   ├── 📁 Example/ - Example module
│   │   ├── 📄 ExampleApplicationService.php - Example business logic
│   │   ├── 📁 Command/ - Command objects
│   │   │   ├── 📄 CreateExampleCommand.php - Create example command
│   │   │   └── 📄 UpdateExampleCommand.php - Update example command
│   │   └── 📁 Dto/ - Data transfer objects
│   │       └── 📄 ExampleResponse.php - Example response DTO
│   └── 📁 Shared/ - Shared application components
│       ├── 📁 Core/ - Core shared components
│       │   └── 📁 Factory/ - Application factories
│       │       ├── 📄 DetailInfoFactory.php - Audit trail factory
│       │       └── 📄 SearchCriteriaFactory.php - Search criteria factory
│       └── 📁 Common/ - Common shared helpers
├── 📁 Console/ - Console commands
│   ├── 📄 HelloCommand.php - Hello world command
│   ├── 📄 MigrateModuleCommand.php - Isolated module migrations (migrate:module)
│   └── 📄 SeederCommand.php - Data seeding command
├── 📁 Domain/ - Domain layer
│   ├── 📁 Example/ - Example bounded context
│   │   ├── 📁 Entity/ - Domain entities
│   │   │   └── 📄 Example.php - Example entity with optimistic locking
│   │   ├── 📁 Repository/ - Repository contracts
│   │   │   └── 📄 ExampleRepositoryInterface.php - Example repository interface
│   │   └── 📁 Service/ - Domain services
│   │       └── 📄 ExampleDomainService.php - Example domain service
│   └── 📁 Shared/ - Shared domain components
│       ├── 📁 Core/ - Core shared components
│       │   ├── 📁 Audit/ - Audit contracts
│       │   │   └── 📄 AuditServiceInterface.php - Audit service contract
│       │   ├── 📁 Concerns/ - Reusable concerns
│       │   │   ├── 📁 Entity/ - Entity concerns
│       │   │   │   ├── 📄 ChangeLogged.php - Audit trail trait
│       │   │   │   ├── 📄 Descriptive.php - Name/description trait
│       │   │   │   ├── 📄 Identifiable.php - ID trait
│       │   │   │   └── 📄 Stateful.php - State management trait
│       │   │   └── 📁 Service/ - Service concerns
│       │   │       └── 📄 DomainValidator.php - Domain validation
│       │   ├── 📁 Contract/ - Domain contracts
│       │   │   ├── 📄 ActorInterface.php - Actor contract
│       │   │   ├── 📄 CurrentUserInterface.php - Current user contract
│       │   │   └── 📄 DateTimeProviderInterface.php - DateTime provider contract
│       │   ├── 📁 Security/ - Security contracts
│       │   │   └── 📄 AuthorizerInterface.php - Authorization contract
│       │   └── 📁 ValueObject/ - Value objects
│       │       ├── 📄 DetailInfo.php - Audit trail VO
│       │       ├── 📄 LockVersion.php - Optimistic locking VO
│       │       ├── 📄 ResourceStatus.php - Entity status VO
│       │       ├── 📄 SyncMdb.php - MongoDB sync flag VO
│       │       └── 📄 SyncSlave.php - Slave sync direction VO
│       └── 📁 Common/ - Common shared helpers
├── 📁 Infrastructure/ - Infrastructure layer
│   ├── 📁 Core/ - Core infrastructure
│   │   ├── 📁 Audit/ - Audit implementation
│   │   │   └── 📄 DatabaseAuditService.php - Database audit service
│   │   ├── 📁 Clock/ - Time management
│   │   │   └── 📄 SystemClock.php - System clock implementation
│   │   ├── 📁 Concerns/ - Infrastructure concerns
│   │   │   ├── 📄 Auditable.php - Auditable concern
│   │   │   └── 📄 HasCoreFeatures.php - Core features concern
│   │   ├── 📁 Database/ - Database implementations
│   │   │   ├── 📁 MongoDB/ - MongoDB implementation
│   │   │   │   ├── 📄 AbstractMongoDBRepository.php - MongoDB base repository
│   │   │   │   └── 📄 MongoDBService.php - MongoDB service
│   │   │   └── 📁 Redis/ - Redis implementation
│   │   │       ├── 📄 AbstractRedisRepository.php - Redis base repository
│   │   │       └── 📄 RedisService.php - Redis service
│   │   ├── 📁 Monitoring/ - Monitoring & observability
│   │   │   ├── 📄 CustomMonitoringService.php - Custom monitoring
│   │   │   ├── 📄 ErrorMonitoringMiddleware.php - Error monitoring middleware
│   │   │   ├── 📄 MetricsMiddleware.php - Metrics collection middleware
│   │   │   ├── 📄 MonitoringServiceInterface.php - Monitoring contract
│   │   │   ├── 📄 RequestIdMiddleware.php - Request ID middleware
│   │   │   └── 📄 StructuredLoggingMiddleware.php - Structured logging
│   │   ├── 📁 RateLimit/ - Rate limiting
│   │   │   └── 📄 DatabaseRateLimiter.php - Database rate limiter
│   │   ├── 📁 Security/ - Security implementation
│   │   │   ├── 📄 AccessChecker.php - Access control checker
│   │   │   ├── 📄 Actor.php - Actor implementation
│   │   │   ├── 📄 ActorProvider.php - Actor provider
│   │   │   ├── 📄 CurrentUser.php - Current user implementation
│   │   │   ├── 📄 CurrentUserAwareInterface.php - Current user awareness
│   │   │   ├── 📄 HstsMiddleware.php - HSTS middleware
│   │   │   ├── 📄 JwtService.php - JWT service
│   │   │   ├── 📄 PermissionChecker.php - Permission checker
│   │   │   ├── 📄 RbacAuthorizer.php - RBAC authorizer
│   │   │   └── 📁 Rule/ - Authorization rules
│   │   │       └── 📄 PermissionMapRule.php - Permission mapping rule
│   │   ├── 📁 Seeder/ - Seeder infrastructure
│   │   │   ├── 📄 AbstractSeederData.php - Seeder base class
│   │   │   └── 📄 SeederProviderInterface.php - Seeder provider contract
│   │   └── 📁 Time/ - Time infrastructure
│   │       └── 📄 AppDateTimeProvider.php - DateTime provider
│   └── 📁 Common/ - Common infrastructure
│       └── 📁 Persistence/ - Data persistence
│           └── 📁 Example/ - Example persistence
│               ├── 📄 ExampleRepository.php - Example repository with optimistic locking
│               └── 📄 MdbExampleSchema.php - MongoDB schema for Example
├── 📁 Migration/ - Database migrations (root files are applied by migrate:up)
│   ├── 📁 Auditable/ - Isolated: audit_logs + rate_limits (migrate:module auditable)
│   │   ├── 📄 M20240101000000CreateAuditLogsTable.php - Audit logs table
│   │   └── 📄 M20240101000001CreateRateLimitsTable.php - Rate limits table
│   └── 📁 Example/ - Isolated: Example module tables (migrate:module example)
│       ├── 📄 M20240101000000CreateExampleTable.php - Example table
│       └── 📄 M20260910104729CreateAnotherExampleTable.php - AnotherExample table
└── 📁 Shared/ - Shared components
    ├── 📄 ApplicationParams.php - Application parameters
    ├── 📁 Common/ - Common shared helpers
    └── 📁 Core/ - Core shared components
        ├── 📁 Context/ - Context objects
        │   └── 📄 ValidationContext.php - Validation context
        ├── 📁 Dto/ - Shared DTOs
        │   ├── 📄 PaginatedResult.php - Paginated result DTO
        │   └── 📄 SearchCriteria.php - Search criteria DTO
        ├── 📁 Enums/ - Shared enums
        │   ├── 📄 AppConstants.php - Application constants
        │   └── 📄 RecordStatus.php - Record status enum
        ├── 📁 ErrorHandler/ - Error handling
        │   └── 📄 ErrorHandlerResponse.php - Error response formatter
        ├── 📁 Exception/ - Exception hierarchy
        │   ├── 📄 BadRequestException.php - 400 Bad Request
        │   ├── 📄 ConflictException.php - 409 Conflict
        │   ├── 📄 ForbiddenException.php - 403 Forbidden
        │   ├── 📄 HttpException.php - Base HTTP exception
        │   ├── 📄 NoChangesException.php - No changes exception
        │   ├── 📄 NotFoundException.php - 404 Not Found
        │   ├── 📄 OptimisticLockException.php - Optimistic locking conflict
        │   ├── 📄 ServiceException.php - Service exception
        │   ├── 📄 TooManyRequestsException.php - 429 Too Many Requests
        │   ├── 📄 UnauthorizedException.php - 401 Unauthorized
        │   └── 📄 ValidationException.php - Validation error
        ├── 📁 Middleware/ - Shared middleware
        │   ├── 📄 AccessMiddleware.php - Access control middleware
        │   ├── 📄 CorsMiddleware.php - CORS middleware
        │   ├── 📄 JwtMiddleware.php - JWT authentication middleware
        │   ├── 📄 RateLimitMiddleware.php - Rate limiting middleware (in-memory)
        │   ├── 📄 RequestParamsMiddleware.php - Request parameters middleware
        │   ├── 📄 SecureHeadersMiddleware.php - Security headers middleware
        │   └── 📄 TrustedHostMiddleware.php - Trusted host middleware
        ├── 📁 Query/ - Query utilities
        │   └── 📄 QueryConditionApplier.php - Query condition builder
        ├── 📁 Request/ - Request utilities
        │   ├── 📄 DataParserInterface.php - Data parser contract
        │   ├── 📄 PaginationParams.php - Pagination parameters
        │   ├── 📄 RawParams.php - Raw request parameters
        │   ├── 📄 RequestDataParser.php - Request data parser
        │   ├── 📄 RequestParams.php - Request parameters handler
        │   └── 📄 SortParams.php - Sort parameters
        ├── 📁 Security/ - Security utilities
        │   └── 📄 InputSanitizer.php - Input sanitization
        ├── 📁 Utility/ - General utilities
        │   ├── 📄 Arrays.php - Array utilities
        │   ├── 📄 FieldMapper.php - Row field mapping utility
        │   └── 📄 JsonHandler.php - JSON data Handler
        ├── 📁 Validation/ - Validation utilities
        │   ├── 📄 AbstractValidator.php - Abstract validator
        │   ├── 📄 ValidationContextInterface.php - Validation context contract
        │   └── 📁 Rules/ - Custom validation rules
        │       ├── 📄 HasNoDependencies.php - No-dependencies rule
        │       ├── 📄 HasNoDependenciesHandler.php - Rule handler
        │       ├── 📄 UniqueValue.php - Unique value rule
        │       └── 📄 UniqueValueHandler.php - Rule handler
        └── 📁 ValueObject/ - Shared value objects
            ├── 📄 LockVersionConfig.php - Optimistic lock config VO
            └── 📄 Message.php - Translation message VO
```

## **📁 RESOURCES**
```
resources/
└── 📁 messages/ - Internationalization
    ├── 📁 en/ - English messages
    │   ├── 📄 app.php - Application messages
    │   ├── 📄 error.php - Error messages
    │   ├── 📄 success.php - Success messages
    │   └── 📄 validation.php - Validation messages
    └── 📁 id/ - Indonesian messages
        ├── 📄 app.php - Application messages
        ├── 📄 error.php - Error messages
        ├── 📄 success.php - Success messages
        └── 📄 validation.php - Validation messages
```

## **📁 PUBLIC**
```
public/
├── 📄 favicon.ico - Website favicon
├── 📄 index.php - Application entry point
└── 📄 robots.txt - Search engine rules
```

## **📁 DOCKER**
```
docker/
├── 📄 .env - Docker environment
├── 📄 Dockerfile - Docker image definition
├── 📄 compose.yml - Docker Compose base
├── 📁 dev/ - Development environment
│   ├── 📄 .env - Dev environment
│   ├── 📄 .gitignore - Git ignore
│   ├── 📄 compose.yml - Dev compose config
│   └── 📄 override.env.example - Override env example
├── 📁 prod/ - Production environment
│   ├── 📄 .env - Prod environment
│   ├── 📄 .gitignore - Git ignore
│   └── 📄 compose.yml - Prod compose config
└── 📁 test/ - Test environment
    ├── 📄 .env - Test environment
    ├── 📄 .gitignore - Git ignore
    └── 📄 compose.yml - Test compose config
```

---

## **🎯 KEY FEATURES & PATTERNS**

### **🔐 Optimistic Locking Implementation**
- **Value Object**: `LockVersion.php` - Type-safe version handling
- **Exception**: `OptimisticLockException.php` - Conflict handling
- **Repository**: `ExampleRepository.php` - Database-level optimistic locking
- **API**: `ExampleUpdateAction.php` - Client-side validation

### **🏗️ Domain-Driven Design (DDD)**
- **Entities**: Rich domain objects with behaviors
- **Value Objects**: Immutable value objects (`LockVersion`, `Status`, `DetailInfo`)
- **Repositories**: Data access abstraction
- **Domain Services**: Business logic services
- **Application Services**: Use case orchestration

### **🗄️ Multi-Database Support**
- **PostgreSQL**: Primary relational database
- **MongoDB**: NoSQL document storage with schema support
- **Redis**: Caching and session storage
- **Abstract Repositories**: Base classes for MongoDB and Redis

### **🔒 Security & Authentication**
- **JWT Middleware**: Token-based authentication
- **RBAC**: Role-based access control
- **Input Sanitization**: Comprehensive input validation
- **Rate Limiting**: Database-backed rate limiting
- **Security Headers**: HSTS, CORS, trusted hosts

### **📊 Observability & Monitoring**
- **Structured Logging**: JSON-formatted logs
- **Error Monitoring**: Centralized error tracking
- **Metrics Collection**: Application metrics
- **Audit Trail**: Complete audit logging
- **Request Tracing**: Request ID propagation

### **🌐 API Design**
- **RESTful**: Clean REST endpoints
- **Versioning**: API versioning (`/v1/`)
- **Error Handling**: Consistent error responses
- **Validation**: Context-aware validation
- **Pagination**: Offset-based pagination
- **Filtering & Sorting**: Query parameters

### **🚀 DevOps & Deployment**
- **Docker**: Containerized deployment
- **Multi-environment**: Dev, test, prod configs
- **Quality Gates**: Automated quality checks
- **CI/CD**: GitHub Actions workflow

---

## **📈 Project Statistics**
- **Total Files**: 200+ files
- **Source Code**: 50,000+ lines of PHP
- **Languages**: English & Indonesian support
- **Architecture**: Clean DDD with hexagonal pattern

---

## **🔧 Boilerplate Components**

### **🎯 Core Boilerplate Templates**
1. **Entity Pattern**: `src/Domain/Example/Entity/Example.php`
2. **Repository Pattern**: `src/Infrastructure/Common/Persistence/Example/ExampleRepository.php`
3. **Application Service**: `src/Application/Example/ExampleApplicationService.php`
4. **API Actions**: `src/Api/V1/Example/Action/*`
5. **Validation**: `src/Api/V1/Example/Validation/ExampleInputValidator.php`
6. **Commands & DTOs**: `src/Application/Example/Command/*` & `src/Application/Example/Dto/*`

### **🔄 Shared Components**
1. **Traits**: `src/Domain/Shared/Core/Concerns/Entity/*`
2. **Value Objects**: `src/Domain/Shared/Core/ValueObject/*`
3. **Exceptions**: `src/Shared/Core/Exception/*`
4. **Middleware**: `src/Shared/Core/Middleware/*`
5. **Validation**: `src/Shared/Core/Validation/*`

---

**Status: 🎯 Complete project structure documentation with DDD + Optimistic Locking implementation!**