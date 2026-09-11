# Yii3 API Skeleton Setup Guide

**Yii3 API Skeleton** is a starter project for building RESTful APIs using Yii3 with Domain-Driven Design (DDD) architecture. It provides a ready-to-use structure, helper scripts, and example configurations to accelerate your API development with clean architecture principles.

---

## 🏗️ Architecture Overview

This skeleton follows **Domain-Driven Design (DDD)** principles with clean architecture layers:

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

### Key Features

- **🎯 Domain-Driven Design**: Clean separation of business logic
- **🔧 Type Safety**: Full Psalm static analysis integration
- **🧪 Testing Ready**: Complete test suite setup
- **🔒 Security**: Authentication, authorization, and audit trail
- **📊 Quality Assurance**: Automated code quality checks
- **🐳 Docker Ready**: Complete containerization setup
- **📚 Documentation**: Comprehensive documentation included

---

## 🚀 Quick Start

### Prerequisites

- **PHP 8.3+** with required extensions
- **Composer** for dependency management
- **PostgreSQL** database
- **MongoDB** (optional, for audit trails)
- **Docker** (optional, for containerized development)

### 1. Create New Project

```bash
composer create-project --prefer-dist yiisoft/app-api ./
```

### 2. Add the repository and package to `composer.json` 

Open your project's `composer.json` and add the following sections:

### Add this to `composer.json` `repositories` 
```json
"repositories": [
        {
            "type": "composer",
            "url": "https://asset-packagist.org"
        },
        {
            "type": "vcs",
            "url": "https://github.com/rahmatsyaparudin/yii3-api-boilerplate.git"
        }
    ],
```

### Add this to `composer.json` `require-dev` 
```json
"rahmatsyaparudin/yii3-api-boilerplate": "dev-main"
```

### Add this to `composer.json` `scripts` 
```json
"skeleton-scripts": [
    "@php scripts/skeleton-scripts.php"
],
"skeleton-update": [
    "composer update rahmatsyaparudin/yii3-api-boilerplate --ignore-platform-reqs",
    "@php scripts/skeleton-scripts.php",
    "@php scripts/skeleton-update.php"
],
"skeleton-copy-config": [
    "@php scripts/skeleton-copy-config.php"
],
"skeleton-copy-examples": [
    "@php scripts/skeleton-copy-examples.php"
],
"skeleton-generate-module": [
    "@php scripts/generate-module.php"
]
```

### 3. Update Composer
Update composer dependencies
```bash
composer update --ignore-platform-reqs
```

### 4. Copy skeleton scripts

Make directory `scripts` and Copy the `scripts` folder from the package to your project root:

```bash
mkdir scripts; cp -r -Force vendor/rahmatsyaparudin/yii3-api-boilerplate/scripts/* ./scripts
```

### 5. Install Skeleton
Install skeleton structure
```bash
composer skeleton-update
```

Copy config files (first time only)

This copies `.env.example` → `.env`, `.gitignore`, message files (`resources/messages/{en,id}/`), and other skeleton configuration files.

```bash
composer skeleton-copy-config
```

Copy example files (first time only)
```bash
composer skeleton-copy-examples
```

### 6. Generate New Module

Use the built-in module generator to create new API modules with complete structure:
Generate a new module (e.g., Product)
```bash
composer skeleton-generate-module -- --module=Product --table=product_management
```

Or use direct PHP script (alternative):
```bash
php scripts/generate-module.php --module=Product --table=product_management
```

> **Note:** The skeleton comes with an Example module that demonstrates the complete structure. Use the generator above to create additional modules for your specific needs.

### What the Generator Creates

The module generator creates a complete module structure following DDD architecture. Here's what you get when generating a new module (based on the existing Example module):

#### **📁 API Layer** (`src/Api/V1/{Module}/`)
```
src/Api/V1/Product/
├── Action/
│   ├── ProductCreateAction.php    # POST /product/create
│   ├── ProductDataAction.php      # GET/POST /product & /product/data
│   ├── ProductDeleteAction.php    # DELETE /product/{id}
│   ├── ProductRestoreAction.php   # POST /product/{id}/restore
│   ├── ProductUpdateAction.php    # PUT /product/{id}
│   └── ProductViewAction.php      # GET /product/{id}
└── Validation/
    └── ProductInputValidator.php  # Request validation rules
```

#### **📁 Application Layer** (`src/Application/{Module}/`)
```
src/Application/Product/
├── Command/
│   ├── CreateProductCommand.php   # Create command DTO
│   └── UpdateProductCommand.php   # Update command DTO
├── Dto/
│   └── ProductResponse.php        # Response DTO
└── ProductApplicationService.php # Application service
```

#### **📁 Domain Layer** (`src/Domain/{Module}/`)
```
src/Domain/Product/
├── Entity/
│   └── Product.php               # Domain entity
├── Repository/
│   └── ProductRepositoryInterface.php # Repository interface
└── Service/
    └── ProductDomainService.php  # Domain service
```

#### **📁 Infrastructure Layer** (`src/Infrastructure/Common/Persistence/{Module}/`)
```
src/Infrastructure/Common/Persistence/Product/
├── ProductRepository.php         # Repository implementation
└── MdbProductSchema.php          # MongoDB schema
```

#### **📁 Database & Seeding**
```
src/Migration/
└── M20240130123457CreateProductTable.php  # Database migration

src/Seeder/
├── SeedProductData.php           # Seeder class
└── Fixtures/
    └── product.yaml              # Alice fixtures for test data
```
#### **⚙️ Configuration Updates**
The generator automatically updates configuration files:

- **`config/common/access.php`** - Adds access control rules  
- **`config/common/aliases.php`** - Adds aliases  
- **`config/common/routes.php`** - Adds API routes with proper permissions
- **`config/common/di/repository.php`** - Adds repository DI binding
- **`config/common/di/service.php`** - Adds service DI binding
- **`config/common/di/translator.php`** - Adds translator DI binding
- **`config/console/commands.php`** - Adds console commands

#### **🔧 Features Included**
- **✅ Complete CRUD Operations** - Create, Read, Update, Delete, Restore
- **✅ RESTful API Endpoints** - Following REST conventions
- **✅ Request Validation** - Input validation rules
- **✅ Permission System** - Role-based access control
- **✅ Database Migration** - Schema management
- **✅ Data Seeding** - Test data generation with Alice fixtures
- **✅ Type Safety** - Full Psalm compatibility
- **✅ Error Handling** - Standardized error responses

### Generated API Endpoints

For each module, the following endpoints are automatically created:

| Method   | Endpoint                    | Action      | Permission        |
|----------|-----------------------------|-------------|-------------------|
| GET      | `/v1/{module}`              | List items  | `{module}.index`  |
| POST     | `/v1/{module}/data`         | Create item | `{module}.data`   |
| GET      | `/v1/{module}/{id}`         | View item   | `{module}.view`   |
| POST     | `/v1/{module}/create`       | Create item | `{module}.create` |
| PUT      | `/v1/{module}/{id}`         | Update item | `{module}.update` |
| DELETE   | `/v1/{module}/{id}`         | Delete item | `{module}.delete` |
| POST     | `/v1/{module}/{id}/restore` | Restore item| `{module}.restore`|

### 📋 Current Available Modules

The skeleton includes the following modules out of the box:

#### **✅ Example Module** (Included)
- **Purpose:** Demonstrates complete module structure
- **Endpoints:** `/v1/example/*`
- **Usage:** Reference implementation for learning and testing
- **Files:** Complete DDD structure with all layers

#### **🔧 Custom Modules** (Generate as needed)
- **Product, Category, Brand, Order, User, etc.**
- **Purpose:** Your business-specific modules
- **Generation:** Use `composer skeleton-generate-module -- --module=ModuleName --table=table_name` or `php scripts/generate-module.php --module=ModuleName --table=table_name`
- **Custom Table:** Use `--table=table_name` for table names (e.g., `--module=Product --table=product_management`)
- **Customization:** Modify generated files according to your business logic

## 📁 Project Structure

After installation, your project will have this structure:

```
yii3-api/
├── config/                 # Application configuration
│   ├── common/             # Shared configuration
│   ├── console/            # Console configuration
│   ├── environments/       # Environment configs
│   └── web/                # Web configuration
├── docs/                   # Documentation
│   ├── architecture-guide.md # Architecture documentation
│   ├── quality-guide.md    # Quality assurance guide
│   └── setup-guide.md     # This setup guide
├── public/                 # Web root
│   └── index.php          # Application entry point
├── resources/              # Application resources
│   └── messages/           # Translation files
├── scripts/                # Utility scripts
│   ├── generate-module.php # Module generator
│   ├── skeleton-scripts.php # Skeleton script installer
│   ├── skeleton-update.php # Skeleton installer
│   ├── skeleton-copy-examples.php # Example files copier
│   ├── skeleton-copy-config.php # Config files copier
│   └── skeleton.version    # Installed skeleton version marker
├── src/                    # Source code
│   ├── Api/                # API layer
│   │   ├── V1/             # API version 1
│   │   │   ├── Example/    # Example API endpoints
│   │   │   └── Shared/     # Shared API components
│   │   └── Shared/         # Shared API components
│   ├── Application/        # Application layer
│   │   ├── Example/        # Application services
│   │   └── Shared/         # Shared application services
│   ├── Domain/             # Domain layer
│   │   ├── Example/        # Domain entities
│   │   └── Shared/         # Shared domain components
│   ├── Infrastructure/      # Infrastructure layer
│   │   ├── Core/           # Core infrastructure
│   │   │   ├── Audit/      # Audit services
│   │   │   ├── Database/   # Database implementations
│   │   │   └── Security/   # Security services
│   │   └── Common/         # Common infrastructure
│   │       └── Persistence/  # Repository implementations
│   │           └── Example/    # Example repository
│   ├── Migration/          # Database migrations (module subfolders are isolated)
│   │   ├── Auditable/     # audit_logs + rate_limits (opt-in, via migrate:module)
│   │   └── Example/       # Example module migrations (via migrate:module)
│   ├── Seeder/            # Data seeders
│   │   ├── Fixtures/      # Alice fixtures
│   │   │   └── example.yaml
│   │   ├── Faker/         # Faker providers
│   │   └── SeedExampleData.php
│   └── Shared/            # Shared utilities
│       ├── ApplicationParams.php
│       ├── Common/        # Common shared helpers
│       └── Core/          # Core shared components
│           ├── Context/   # Validation context
│           ├── Dto/       # Data Transfer Objects
│           ├── Enums/     # Shared enumerations
│           ├── ErrorHandler/ # Error handling utilities
│           ├── Exception/ # Custom exceptions
│           ├── Middleware/ # HTTP middleware
│           ├── Query/     # Query utilities
│           ├── Request/   # Request handling
│           ├── Security/  # Security utilities
│           ├── Utility/   # General utilities
│           ├── Validation/ # Validation classes
│           └── ValueObject/ # Value objects
├── tests/                  # Test suite
│   ├── Api/                # API tests
│   ├── Functional/         # Functional tests
│   ├── Support/            # Test support classes
│   └── Unit/              # Unit tests
└── vendor/                 # Dependencies
```

---

## 🔧 Configuration

### Environment Setup

#### 1. Copy Environment Files

```bash
# Copy environment configuration
cp .env.example .env
```

#### 2. Configure Environment

Edit `.env` file:

```bash
# Application Environment
APP_ENV=dev
APP_DEBUG=1

app.config.code=appAPI
app.config.name=appAPI
app.config.language=en
app.time.timezone=Asia/Jakarta
app.pagination.defaultPageSize=10
app.pagination.maxPageSize=100
app.rateLimit.maxRequests=100
app.rateLimit.windowSize=60
app.hsts.maxAge=31536000
app.hsts.includeSubDomains=true
app.hsts.preload=false
app.cors.allowedOrigins=["http://example.com:3000"]
app.cors.maxAge=86400
app.cors.allowCredentials=true
app.cors.allowedMethods=["GET","POST","PUT","PATCH","DELETE","OPTIONS"]
app.cors.allowedHeaders=["Content-Type","Authorization","X-Requested-With","Accept","Origin"]
app.cors.exposedHeaders=["X-Pagination-Total-Count","X-Pagination-Page-Count"]
app.trusted_hosts.allowedHosts=["127.0.0.1","::1","localhost"]

# Optimistic Lock Configuration
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=["example","example_1"]

# SSO Configuration (External Keycloak)
app.jwt.secret=secret-key-harus-panjang-256-bit
app.jwt.algorithm=HS256
app.jwt.issuer=https://sso.example.com
app.jwt.audience=https://sso.example.com

db.default.driver=pgsql
db.default.host=localhost
db.default.port=5432
db.default.name=dev_yii3
db.default.user=postgres
db.default.password=postgres

db.mongodb.dsn=localhost:27017
db.mongodb.name=db_example
db.mongodb.enabled=true

redis.default.host=127.0.0.1
redis.default.port=6379
redis.default.db=0
redis.default.password=null
```

> **Note:** `app.config.language` sets the application language (used for translations). When `APP_ENV=dev` (or `development`), `ApplicationParams::$environment` is set to `development` and the root index endpoint (`GET /`) includes it in the response:
>
> ```json
> {
>   "name": "appAPI",
>   "version": "1.0",
>   "language": "en",
>   "environment": "development"
> }
> ```
>
> In production (`APP_ENV` other than `dev`/`development`) the `environment` field is omitted.

#### 3. Database Migration

```bash
# Run database migrations
./yii migrate:up

# Seed initial data (development only)
./yii seed --module=example

# Or seed with custom options (development only)
./yii seed --module=example --count=10

# Note: Seed commands only work in development environment (APP_ENV=dev)
```

##### Isolated Module Migrations

Migrations placed in a subfolder of `src/Migration/` are **isolated** — `migrate:up` only scans the root directory and will not apply them. Use the `migrate:module` command to apply a specific group:

```bash
# Apply migrations from src/Migration/Example (namespace App\Migration\Example)
./yii migrate:module example

# Apply migrations from src/Migration/Auditable (audit_logs + rate_limits tables)
./yii migrate:module auditable

# Options
./yii migrate:module example -y      # skip confirmation
./yii migrate:module example -l 1    # limit number of migrations
```

The skeleton ships with two isolated groups:

| Folder | Namespace | Tables | Required? |
|--------|-----------|--------|-----------|
| `src/Migration/Example/` | `App\Migration\Example` | `example`, `another_example` | Only for the demo module |
| `src/Migration/Auditable/` | `App\Migration\Auditable` | `audit_logs`, `rate_limits` | Opt-in, see [Audit Trail](#audit-trail) |

New module migrations generated by `generate-module.php` are placed flat in `src/Migration/` and are applied by the regular `migrate:up`.

#### 4. Optimistic Lock Configuration

The skeleton includes configurable optimistic locking to prevent concurrent update conflicts:

```bash
# Enable/disable optimistic locking (global)
app.optimistic_lock.enabled=true    # Default: true

# Disable optimistic locking for specific validators (JSON array)
app.optimistic_lock.disabled.values=["example","example_1"]
```

**🔧 Optimistic Lock Features:**

- **✅ Automatic Version Management** - Each entity has a `lock_version` field
- **✅ Concurrent Update Prevention** - Throws exception on version mismatch
- **✅ Configurable** - Can be enabled/disabled globally or per validator
- **✅ Performance Optimized** - Skips verification when disabled
- **✅ Per-Validator Control** - Fine-grained control per validator type
- **✅ Smart Normalization** - Automatic validator name normalization

**📋 Configuration Options:**

| Setting                               | Type       | Default | Description                                    |
|---------------------------------------|------------|---------|------------------------------------------------|
| `app.optimistic_lock.enabled`         | boolean    | `true`  | Enable/disable optimistic locking globally     |
| `app.optimistic_lock.disabled.values` | JSON array | `[]`    | List of disabled validators (normalized names) |

**🚀 Usage Examples:**

```bash
# Disable optimistic locking globally
app.optimistic_lock.enabled=false

# Disable for specific validators
app.optimistic_lock.disabled.values=["example","user","product"]

# Enable all validators (empty disabled list)
app.optimistic_lock.disabled.values=[]

# Enable in production for data integrity
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=[]
```

**🔧 Validator Name Normalization:**

The system automatically normalizes validator names for configuration:

```php
// Validator Class → Normalized Name → Environment Key
ExampleInputValidator → "example" → app.optimistic_lock.disabled.values=["example"]
UserInputValidator → "user" → app.optimistic_lock.disabled.values=["user"]
ProductInputValidator → "product" → app.optimistic_lock.disabled.values=["product"]
```

**🔧 Implementation in Validators:**

Optimistic lock validation is automatically integrated into validators:

```php
// In your InputValidator class
final class ExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::UPDATE => [
                'id' => [new Required(), new Integer(min: 1)],
                'name' => [new StringValue(skipOnEmpty: true)],
                // Unique validation with optimistic lock awareness
                'name' => [
                    new Required(),
                    new StringValue(),
                    new UniqueValue(
                        targetClass: ExampleRepository::class,
                        targetAttribute: 'name',
                        filter: fn() => $this->getFilterForUnique(),
                        // Automatically respects optimistic lock configuration
                        skipOnEmpty: fn() => !$this->isOptimisticLockEnabled()
                    ),
                ],
                // lock_version automatically added/removed based on configuration
                'lock_version' => [
                    new Required(
                        when: fn() => $this->isOptimisticLockEnabled()
                    ),
                    new Integer(
                        min: 1,
                        skipOnEmpty: fn() => !$this->isOptimisticLockEnabled()
                    ),
                ],
            ],
            // ... other contexts
        };
    }
}
```

**🔧 Advanced Validation Features:**

The system includes advanced validation rules that integrate with optimistic locking:

```php
// UniqueValue Rule - Prevents duplicate names with optimistic lock support
new UniqueValue(
    targetClass: ExampleRepository::class,
    targetAttribute: 'name',
    filter: fn() => $this->getFilterForUnique(),
    message: 'Name must be unique',
    skipOnEmpty: true
)

// HasNoDependencies Rule - Validates entity has no dependencies before deletion
new HasNoDependencies(
    dependencyChecker: $this->dependencyChecker,
    errorMessage: 'Cannot delete entity with existing dependencies',
    skipOnEmpty: false
)
```

**🔧 Implementation in Entities:**

Entities use the `OptimisticLock` trait for automatic version management:

```php
// In your Entity class
use App\Domain\Shared\Core\Concerns\Entity\OptimisticLock;

final class Example extends Entity
{
    use OptimisticLock;
    
    // Automatic lock_version management
    // - verifyLockVersion() for validation
    // - upgradeLockVersion() for increment
    // - getLockVersion() for current version
}
```

**📝 Configuration Examples:**

```bash
# Development: Disable for testing entities
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=["example","test"]

# Production: Enable for all entities
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=[]

# Maintenance: Disable all optimistic locking
app.optimistic_lock.enabled=false
```

**🔧 API Usage:**

When optimistic locking is enabled, include `lock_version` in UPDATE/DELETE requests:

```bash
# Update with optimistic lock
curl -X PUT http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "lock_version": 5
  }'

# Delete with optimistic lock
curl -X DELETE http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -d '{"lock_version": 5}'
```

When disabled for a validator, `lock_version` is optional:

```bash
# Update without lock_version (when disabled)
curl -X PUT http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Name"}'
```

#### 5. Translation Message Files

Message files in `resources/messages/{en,id}/` are split into **skeleton-managed** and **project-owned** files:

| File | Owner | Notes |
|------|-------|-------|
| `app.php` | **Project** | Add your custom messages here. Never overwritten by `composer skeleton-update`. |
| `error.php` | Skeleton | Do not edit or add keys — overwritten by `composer skeleton-update`. |
| `success.php` | Skeleton | Do not edit or add keys — overwritten by `composer skeleton-update`. |
| `validation.php` | Skeleton | Do not edit or add keys — overwritten by `composer skeleton-update`. |

Put project-specific error, success, or validation messages in `app.php` for each locale:

```php
// resources/messages/en/app.php
return [
    'success' => 'Success',
    'validation.custom_rule' => 'The {field} is invalid.',
];
```

Messages are referenced in code via `Message::create()`:

```php
use App\Shared\Core\ValueObject\Message;

throw new BadRequestException(
    translate: Message::create(
        domain: 'validation',          // message file: validation.php
        key: 'resource.not_deleted',
        params: ['resource' => 'example', 'id' => $id]
    )
);
```

---

## 🎯 Development Workflow

### Quality Assurance

The skeleton includes comprehensive quality assurance tools:

```bash
# Run complete quality check suite
php quality quality:check

# Auto-fix code style issues
php quality quality:check --fix

# Generate test coverage reports
php quality quality:check --coverage

# Generate detailed analysis reports
php quality quality:check --report
```

### Testing

```bash
# Run all tests
vendor/bin/phpunit

# Run specific test suite
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Api/
vendor/bin/phpunit tests/Functional/

# Run tests with coverage
vendor/bin/phpunit --coverage-html tests/coverage/html
```

### Static Analysis

```bash
# Run Psalm static analysis
vendor/bin/psalm

# Clear cache and re-run
vendor/bin/psalm --clear-cache

# Check specific file
vendor/bin/psalm src/Domain/Example/Entity/Example.php
```

---

## 🏗️ Architecture Components

### Domain Layer

The domain layer contains business logic and entities:

```php
// src/Domain/Example/Entity/Example.php
final class Example
{
    use Identifiable, Stateful, OptimisticLock;
    
    public static function create(string $name, Status $status, DetailInfo $detailInfo): self
    {
        self::guardInitialStatus($status, null, self::RESOURCE);
        return new self(null, $name, $status, $detailInfo, null, LockVersion::create());
    }
}
```

### Application Layer

Application services coordinate use cases:

```php
// src/Application/Example/ExampleApplicationService.php
final class ExampleApplicationService
{
    public function create(CreateExampleCommand $command): ExampleResponse
    {
        // Business logic validation
        $this->domainService->ensureUnique(...);
        
        // Entity creation
        $example = Example::create(...);
        
        // Persistence
        return ExampleResponse::fromEntity($this->repository->insert($example));
    }
}
```

### Infrastructure Layer

Repository implementations handle data persistence:

```php
// src/Infrastructure/Common/Persistence/Example/ExampleRepository.php
final class ExampleRepository implements ExampleRepositoryInterface
{
    public function insert(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            // Database operations with MongoDB sync
        });
    }
}
```

### API Layer

Controllers handle HTTP requests:

```php
// src/Api/V1/Action/Example/ExampleCreateAction.php
final class ExampleCreateAction
{
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        // Request validation
        $command = new CreateExampleCommand(...);
        
        // Business logic
        $response = $this->applicationService->create($command);
        
        // Response formatting
        return $this->responseFactory->success($response->toArray());
    }
}
```

---

## 🔒 Security Features

### Authentication & Authorization

```php
// JWT Authentication
$app->addMiddleware(new AuthenticationMiddleware($jwtAuthenticator));

// RBAC Authorization
$app->addMiddleware(new AuthorizationMiddleware($rbacAuthorizer));
```

### Audit Trail

Audit logging is **opt-in** — the `audit_logs` table is only created when you run the isolated migration group:

```bash
./yii migrate:module auditable   # creates audit_logs + rate_limits
```

`AuditServiceInterface` is already bound to `DatabaseAuditService` in `config/common/di/audit.php`. Inject it where you need logging:

```php
use App\Domain\Shared\Core\Audit\AuditServiceInterface;

final class ExampleApplicationService
{
    public function __construct(
        private AuditServiceInterface $audit,
    ) {}

    public function update(int $id, array $oldValues, array $newValues): void
    {
        // ... update logic ...

        $this->audit->log('example', $id, 'UPDATE', $oldValues, $newValues);
    }
}
```

```php
// Read audit history
$this->audit->getHistory('example', $recordId);        // history per record
$this->audit->getUserActivity($userId, $from, $to);    // activity per user
```

> **Note:** The `App\Infrastructure\Core\Concerns\Auditable` trait is designed for Active Record-style classes (`beforeSave`/`afterSave`/`getIsNewRecord`). This project uses the Query Builder in repositories, so use `AuditServiceInterface` directly instead.

### Database Rate Limiter (opt-in)

The active `RateLimitMiddleware` uses **in-memory** storage and does not need a table. A persistent DB-backed limiter (`App\Infrastructure\Core\RateLimit\DatabaseRateLimiter`) is available but not wired anywhere — it uses the `rate_limits` table created by `migrate:module auditable`:

```php
use App\Infrastructure\Core\RateLimit\DatabaseRateLimiter;

public function __construct(private DatabaseRateLimiter $limiter) {}

$key = "login:{$clientIp}";

if (!$this->limiter->isAllowed($key, limit: 5, window: 60)) {
    throw new TooManyRequestsException(/* ... */);
}

$this->limiter->hit($key);
$remaining = $this->limiter->getRemaining($key, 5, 60);
$resetAt   = $this->limiter->getResetTime($key, 60);
```

To enforce it through middleware, modify `RateLimitMiddleware` to use `DatabaseRateLimiter`, or call the limiter manually in specific actions (e.g., login).

### Current Actor

`CurrentUser::getActor()` always returns an `ActorInterface` — it is never `null`. Unauthenticated/system contexts get the default actor (`id: 0`, `username: 'system'`). Audit logging and `DetailInfoFactory` rely on this, so you can call `$actor->getUsername()` directly without null-safe operators.

---

## 🔄 Data Synchronization

The skeleton ships with value objects and a factory for tracking record synchronization — both to MongoDB and between master/origin instances.

### MongoDB Sync Flag — `SyncMdb`

`App\Domain\Shared\Core\ValueObject\SyncMdb` wraps the `sync_mdb` column (`null` = synced, `1` = pending):

```php
use App\Domain\Shared\Core\ValueObject\SyncMdb;

$sync = SyncMdb::pending();          // mark record as needing MongoDB sync
$sync = SyncMdb::synced();           // mark record as synced
$sync = SyncMdb::fromInt($row['sync_mdb']);
$sync = SyncMdb::fromString($input); // accepts string input, throws on non-numeric

$sync->isPending();  // true when sync_mdb = 1
$sync->isSynced();   // true when sync_mdb = null
$sync->toInt();      // null | 1 — for DB writes
```

### Master–Origin Sync — `SyncFlag`

`App\Domain\Shared\Core\ValueObject\SyncFlag` manages the `origin_id` / `sync_flag` columns plus a sync direction. `origin_id` is an integer (default `null`); `sync_flag` is a smallint (`null` = synced, `1` = not synced, default `1`). Status and direction are typed enums in `App\Domain\Shared\Core\Enum`:

| Enum | Case | DB Value | Meaning |
|------|------|----------|---------|
| `SyncStatus` | `SYNCED` | `null` | Record already synced |
| `SyncStatus` | `NOT_SYNCED` | `1` | Record needs syncing |
| `SyncDirection` | `NONE` | `0` | No direction |
| `SyncDirection` | `MASTER_TO_ORIGIN` | `1` | Push from master to origin |
| `SyncDirection` | `ORIGIN_TO_MASTER` | `2` | Push from origin to master |
| `SyncDirection` | `BIDIRECTIONAL` | `3` | Sync both ways |

```php
use App\Domain\Shared\Core\Enum\SyncStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;

// Direction is auto-resolved when not given:
//   sync_flag=null           -> SyncDirection::NONE
//   sync_flag=1, no origin   -> SyncDirection::MASTER_TO_ORIGIN
//   sync_flag=1, origin set  -> SyncDirection::ORIGIN_TO_MASTER
$sync = SyncFlag::create(originId: null, status: SyncStatus::NOT_SYNCED);

$sync = SyncFlag::masterToOrigin();        // to all origins
$sync = SyncFlag::masterToOrigin(5);       // to origin #5
$sync = SyncFlag::originToMaster(5);       // origin #5 -> master
$sync = SyncFlag::bidirectional(5);        // both ways
$sync = SyncFlag::synced();

$sync = SyncFlag::fromArray($row);         // from request/DB array
$sync = SyncFlag::fromEntity($entity);     // reads getOriginId()/getSyncFlag()/getSyncDirection()

$sync->needsSyncToOrigin();  // pending && master->origin direction
$sync->needsSyncToMaster();  // pending && origin->master direction
$sync->markForSync();        // returns new instance with sync_flag=1
$sync->markSynced();         // returns new instance with sync_flag=null
$sync->toArray();            // origin_id + sync_flag + direction
$sync->toDbArray();          // origin_id + sync_flag only
```

Invalid `sync_flag` values (not `null`/`1`) or directions (not `0`–`3`) throw `BadRequestException` with translated messages (`sync_flag.invalid_value`, `sync_flag.invalid_direction`).

### `SyncFlagFactory`

`App\Application\Shared\Core\Factory\SyncFlagFactory` is the application-layer helper that wraps `SyncFlag` and adds actor/timestamp-aware payload building:

```php
use App\Application\Shared\Core\Factory\SyncFlagFactory;

final class ExampleApplicationService
{
    public function __construct(
        private SyncFlagFactory $syncFlagFactory,
    ) {}

    public function create(CreateExampleCommand $command): void
    {
        // Build from request data / entity / record
        $sync = $this->syncFlagFactory->fromRequest($command->data);

        if ($this->syncFlagFactory->shouldPushToOrigin($sync)) {
            // Queue payload includes table, record_id, origin_id, direction,
            // operation, payload, created_at and created_by (current actor)
            $payload = $this->syncFlagFactory->buildMasterToOriginPayload(
                originId: $sync->getOriginId(),
                table: 'example',
                recordId: $id,
                operation: 'INSERT',
                data: $command->data,
            );
        }

        // Merge sync state into detail_info
        $detailInfo = $this->syncFlagFactory->mergeIntoDetailInfo($sync, $detailInfo);

        // Audit-style sync log entry (timestamp + current actor)
        $log = $this->syncFlagFactory->buildSyncLog($sync, 'example', $id, 'INSERT');
    }
}
```

---

## 🚀 API Usage Examples

### Create Resource

```bash
curl -X POST http://localhost:8080/api/v1/examples \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Example Resource",
    "status": "active",
    "detail_info": {
      "description": "Example description"
    }
  }'
```

### List Resources

```bash
curl -X GET "http://localhost:8080/api/v1/examples?page=1&pageSize=10&sort=name&dir=asc" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Update Resource

```bash
curl -X PUT http://localhost:8080/api/v1/examples/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Updated Resource",
    "lock_version": 1
  }'
```

### Delete Resource

```bash
curl -X DELETE http://localhost:8080/api/v1/examples/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

---

## 🐳 Docker Development

### Development Environment

```bash
# Start development containers
docker-compose -f docker/dev/compose.yml up -d

# Run commands in container
docker-compose -f docker/dev/compose.yml exec app php yii migrate
docker-compose -f docker/dev/compose.yml exec app php quality
```

### Production Environment

```bash
# Build and run production containers
docker-compose -f docker/prod/compose.yml up -d --build

# View logs
docker-compose -f docker/prod/compose.yml logs -f
```

---

## 📚 Documentation

### Available Documentation

- **[Architecture Guide](architecture-guide.md)**: Complete architecture overview
- **[Quality Guide](quality-guide.md)**: Quality assurance procedures
- **[API Documentation](docs/api/)**: API endpoint documentation
- **[Development Guide](docs/development/)**: Development setup and guidelines

### Generating Documentation

```bash
# Run quality checks with coverage
php quality quality:check --coverage

# Run quality checks with detailed reports
php quality quality:check --report

# Run quality checks with both coverage and reports
php quality quality:check --coverage --report

# Fix code style issues automatically
php quality quality:check --fix
```

---

## 🧪 Testing Strategy

### Test Types

1. **Unit Tests**: Test individual classes and methods
2. **Functional Tests**: Test application workflows
3. **API Tests**: Test API endpoints
4. **Integration Tests**: Test database and external service integration

### Running Tests

```bash
# Run all tests using quality script
php quality test:run

# Run only unit tests
php quality test:run --unit

# Run only integration tests
php quality test:run --integration

# Run tests with coverage
php quality test:run --coverage

# Run specific test with filter
php quality test:run --filter=ExampleTest

# Alternative: Direct PHPUnit commands
vendor/bin/phpunit
vendor/bin/phpunit --coverage-html tests/coverage/html
vendor/bin/phpunit tests/Unit/Domain/Example/ExampleTest.php
```
---

## 🔧 Maintenance

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

### Troubleshooting

#### Common Issues

```bash
# Clear all caches
vendor/bin/psalm --clear-cache

# Reinstall dependencies
composer install --no-dev --optimize-autoloader
```

---

## 📞 Support & Resources

### Documentation

- **[Yii3 Documentation](https://yiisoft.github.io/docs/guide/intro/what-is-yii.html)**: Official Yii3 guide
- **[Yii3 Validator Guide](https://github.com/yiisoft/validator/blob/master/docs/guide/en/README.md)**: Official Yii3 Validator guide
- **[Psalm Documentation](https://psalm.dev/)**: Static analysis tool
- **[PHPUnit Documentation](https://phpunit.de/)**: Testing framework

### Community

- **[Yii3 API GitHub](https://github.com/yiisoft/app-api)**: Official repository
- **[Yii3 Discord](https://discord.gg/yiisoft)**: Community chat

### Quality Tools

- **[PHP CS Fixer](https://cs.symfony.com/)**: Code style fixer
- **[Composer Audit](https://github.com/composer/composer/blob/main/src/Composer/Command/AuditCommand.php)**: Security audit
- **[Codeception](https://codeception.com/)**: Testing framework

---

## 🎯 Best Practices

### Code Quality

- **Type Safety**: Always use strict types and type annotations
- **Error Handling**: Implement proper exception handling
- **Testing**: Maintain high test coverage (>80%)
- **Documentation**: Keep documentation up-to-date

### Security

- **Input Validation**: Validate all user inputs
- **Authentication**: Use JWT tokens for API authentication
- **Authorization**: Implement RBAC for access control
- **Audit Trail**: Log all important operations

### Performance

- **Database Optimization**: Use proper indexes and query optimization
- **Caching**: Implement multi-level caching strategy
- **Async Processing**: Use queues for long-running operations
- **Monitoring**: Monitor application performance

---

## 🎉 Conclusion

The Yii3 API Skeleton provides a solid foundation for building modern, scalable, and maintainable RESTful APIs with Domain-Driven Design principles. The included quality assurance tools, comprehensive documentation, and clean architecture patterns ensure that your API development follows best practices from day one.

Key benefits:

- **🏗️ Clean Architecture**: DDD principles for maintainable code
- **🔒 Type Safety**: Full static analysis with Psalm
- **🧪 Testing Ready**: Complete test suite setup
- **📊 Quality Assurance**: Automated quality checks
- **🐳 Docker Ready**: Containerization support
- **📚 Comprehensive Docs**: Complete documentation included

Start building your next API project with confidence using the Yii3 API Skeleton! 🚀
