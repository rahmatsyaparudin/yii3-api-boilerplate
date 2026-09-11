# Yii3 API Skeleton Setup Guide

**Yii3 API Boilerplate** (`rahmatsyaparudin/yii3-api-boilerplate`) is a starter project for building RESTful APIs using Yii3 with Domain-Driven Design (DDD) architecture. It provides a ready-to-use structure, helper scripts (`scripts/skeleton-*.php`, `generate-module.php`), and example configurations to accelerate your API development with clean architecture principles.

---

## 🏗️ Architecture Overview

This skeleton follows **Domain-Driven Design (DDD)** principles with clean architecture layers:

```
┌─────────────────────────────────────────────────────────────────────┐
│                    API Layer (Controllers & Middleware)                │
├─────────────────────────────────────────────────────────────────────┤
│                Application Layer (Services & Use Cases)              │
├─────────────────────────────────────────────────────────────────────┤
│                  Domain Layer (Entities & Business Logic)                │
├─────────────────────────────────────────────────────────────────────┤
│               Infrastructure Layer (Repositories & External APIs)           │
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

- **PHP 8.2 - 8.5** with required extensions
- **Composer** for dependency management
- **PostgreSQL** (or **MySQL/MariaDB** via `db.default.driver`)
- **MongoDB** (optional, for audit trails)
- **Redis** (optional, cache/side service)
- **Docker** (optional, for containerized development)

### 1. Create New Project

```bash
# Create new project from the boilerplate
composer create-project rahmatsyaparudin/yii3-api-boilerplate yii3-api

# Navigate to project directory
cd yii3-api
```

### 2. Add Skeleton Repository

To consume the boilerplate as an updatable package, add this to your `composer.json`:

```json
{
    "repositories": [
        {
            "type": "vcs",
            "url": "https://github.com/rahmatsyaparudin/yii3-api-boilerplate.git"
        }
    ],
    "require-dev": {
        "rahmatsyaparudin/yii3-api-boilerplate": "dev-main"
    },
    "scripts": {
        "skeleton-scripts": [
            "php scripts/skeleton-scripts.php"
        ],
        "skeleton-update": [
            "composer update rahmatsyaparudin/yii3-api-boilerplate --ignore-platform-reqs",
            "php scripts/skeleton-update.php"
        ],
        "skeleton-copy-config": [
            "php scripts/skeleton-copy-config.php"
        ],
        "skeleton-copy-examples": [
            "php scripts/skeleton-copy-examples.php"
        ]
    }
}
```

### 3. Install Skeleton

```bash
# Update dependencies
composer update --ignore-platform-reqs

# Install skeleton structure (shared classes, config, console commands, quality script)
composer skeleton-update

# Copy config files (first time only)
composer skeleton-copy-config

# Copy example files (first time only)
composer skeleton-copy-examples
```

The current skeleton version is tracked in `scripts/skeleton.version` (currently `1.1.0`). A new module can also be generated with `php scripts/generate-module.php --module=Product`.

---

## 📁 Project Structure

After installation, your project will have this structure:

```
yii3-api/
├── config/                 # Application configuration
│   ├── common/             # Shared configuration (params, routes, access, ...)
│   │   └── di/             # DI definitions (db-*, jwt, middleware-di, ...)
│   ├── console/            # Console configuration (commands.php)
│   ├── environments/       # Environment configs (dev, test, prod)
│   ├── web/                # Web configuration
│   │   └── di/             # Web DI (application.php, psr17.php)
│   └── configuration.php   # yiisoft/config plugin map
├── docs/                   # Documentation
├── public/                 # Web root
│   └── index.php           # Application entry point
├── resources/              # Application resources
│   └── messages/           # Translation files (en, id)
├── scripts/                # Skeleton & generator scripts
│   ├── skeleton-update.php
│   ├── skeleton-copy-config.php
│   ├── skeleton-copy-examples.php
│   ├── generate-module.php
│   └── skeleton.version    # Current skeleton version
├── src/                    # Source code
│   ├── Api/                # API layer
│   │   ├── IndexAction.php # GET / action
│   │   ├── Shared/         # Shared API components (ResponseFactory, presenters)
│   │   └── V1/             # API version 1 (Example, AnotherExample)
│   ├── Application/        # Application layer
│   │   ├── Example/        # Application services, commands, DTOs
│   │   └── Shared/         # Shared application components (factories)
│   ├── Console/            # Console commands (hello, migrate:module, seed)
│   ├── Domain/             # Domain layer
│   │   ├── Example/        # Domain entities, repository contracts, services
│   │   └── Shared/         # Shared domain components
│   ├── Infrastructure/     # Infrastructure layer
│   │   ├── Common/Persistence/  # Repository implementations
│   │   └── Core/           # Audit, Clock, Database, Monitoring, Security, Seeder, Time
│   ├── Migration/          # Migrations, grouped per module namespace
│   ├── Seeder/             # Seeders, YAML fixtures, Faker providers
│   ├── Shared/             # Shared utilities (middleware, exceptions, value objects)
│   ├── Environment.php     # APP_ENV handling (dev/test/prod)
│   └── autoload.php        # Bootstrap: loads vendor autoload + .env
├── tests/                  # Codeception test suites
│   ├── Api/                # API tests (REST module)
│   ├── Console/            # Console tests
│   ├── Functional/         # Functional tests
│   ├── Support/            # Test support classes
│   └── Unit/               # Unit tests
├── quality                 # QA runner (quality:check, test:run)
├── yii                     # Console entry point
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
app.config.name="My Project"
app.config.version="1.0"
app.config.language=en
app.config.allow_god_mode=true
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
app.jwt.publicPaths=["/","/auth/login","/auth/refresh"]

# Database: pgsql | mysql | mariadb
db.default.driver=pgsql
db.default.host=localhost
db.default.port=5432
db.default.name=dev_yii3
db.default.user=postgres
db.default.password=postgres
db.default.charset=utf8mb4

db.mongodb.enabled=true
db.mongodb.dsn=localhost:27017
db.mongodb.name=db_example

redis.default.host=127.0.0.1
redis.default.port=6379
redis.default.db=0
redis.default.password=null
```

#### 3. Database Migration

```bash
# Run database migrations
./yii migrate:up

# Or per module (e.g. only App\Migration\Example)
./yii migrate:module example

# Seed initial data (development only)
./yii seed --module=example

# Or seed all modules with a custom count (development only)
./yii seed --count=10

# Note: Seed commands only work in development environment (APP_ENV=dev)
```

---

## 🎯 Development Workflow

### Quality Assurance

The skeleton includes comprehensive quality assurance tools:

```bash
# Run complete quality check suite (php-cs-fixer, psalm, codecept Unit, composer audit)
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
# Run all Codeception suites (Unit, Functional, Api, Console)
composer test            # alias for `codecept run`
vendor/bin/codecept run

# Run a specific suite
vendor/bin/codecept run Unit
vendor/bin/codecept run Functional
vendor/bin/codecept run Api
vendor/bin/codecept run Console

# Or through the quality script
php quality test:run --unit
php quality test:run --integration   # runs the Functional suite
php quality test:run --coverage
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
    use Identifiable;
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'Example';

    public static function create(
        string $name,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
    ): self {
        self::guardInitialStatus(status: $status, resource: self::RESOURCE);

        return new self(null, $name, $status, $detailInfo, $syncMdb, LockVersion::create());
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
        $detailInfo = $this->detailInfoFactory->create(detailInfo: [])->build();

        $data = Example::create(
            name: $command->name,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo
        );

        return ExampleResponse::fromEntity(
            entity: $this->repository->insert(entity: $data)
        );
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
        // Database operations with MongoDB sync / optimistic locking
    }
}
```

### API Layer

Actions handle HTTP requests:

```php
// src/Api/V1/Example/Action/ExampleCreateAction.php
final class ExampleCreateAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $payload = $request->getAttribute('payload');
        $params  = $payload->getRawParams()->onlyAllowed(self::ALLOWED_KEYS)->sanitize();

        $this->inputValidator->validate(data: $params, context: ValidationContext::CREATE);

        $command  = CreateExampleCommand::create(name: (string) $params->get('name'), ...);
        $response = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(data: $response->toArray(), ...);
    }
}
```

---

## 🔒 Security Features

### Authentication & Authorization

The middleware stack is assembled in `config/web/di/application.php`:

```php
// JWT authentication and RBAC access control run as middleware
JwtMiddleware::class,      // App\Shared\Core\Middleware\JwtMiddleware
// ...
AccessMiddleware::class,   // App\Shared\Core\Middleware\AccessMiddleware
```

Authorization checks go through `AuthorizerInterface` (bound to `App\Infrastructure\Core\Security\RbacAuthorizer` in `config/common/di/security-di.php`), with the permission map in `config/common/access.php`.

### Input Validation

```php
// src/Api/V1/Example/Validation/ExampleInputValidator.php
$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE,   // App\Shared\Common\Context\ValidationContext
);
```

### Audit Trail

```php
// src/Infrastructure/Core/Audit/DatabaseAuditService.php
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
        ?string $userAgent = null,
    ): void {
        // Writes to the audit_logs table with actor information
    }
}
```

---

## 🚀 API Usage Examples

### Create Resource

```bash
curl -X POST http://localhost:8080/v1/example/create \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Example Resource",
    "status": 1,
    "detail_info": {
      "description": "Example description"
    }
  }'
```

### List Resources

```bash
curl -X GET "http://localhost:8080/v1/example?page=1&pageSize=10&sort=name&dir=asc" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

### Update Resource

```bash
curl -X PUT http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_JWT_TOKEN" \
  -d '{
    "name": "Updated Resource",
    "lock_version": 1
  }'
```

### Delete Resource

```bash
curl -X DELETE http://localhost:8080/v1/example/1 \
  -H "Authorization: Bearer YOUR_JWT_TOKEN"
```

Routes are declared in `config/common/routes.php` (group `/v1`, e.g. `GET /v1/example`, `POST /v1/example/create`, `PUT /v1/example/{id}`, `DELETE /v1/example/{id}`, `POST /v1/example/{id}/restore`).

---

## 🐳 Docker Development

The `Makefile` wraps `docker compose` with the files in `docker/` (`compose.yml` plus `dev/`, `test/`, `prod/` overrides).

### Development Environment

```bash
# Start development containers
make up

# Get a shell / run commands in the app container
make shell
make yii migrate:up
make yii seed

# Other helpers
make composer <args>                  # run Composer in the container
make cs-fix                           # run PHP CS Fixer
make psalm                            # run Psalm
make rector                           # run Rector
make test                             # run Codeception in the test stack
make test-coverage                    # tests with coverage
make composer-dependency-analyser     # dependency analysis
```

### Production Environment

```bash
# Build and push the production image, then deploy the stack
make prod-build
make prod-push
make prod-deploy
```

---

## 📚 Documentation

### Available Documentation

- **[Architecture Guide](architecture-guide.md)**: Complete architecture overview
- **[Quality Guide](quality-guide.md)**: Quality assurance procedures
- **[Input Validator Guide](../docs/input-validator-guide.md)**: Request validation guide
- **[Sync Flag Guide](../docs/sync-flag-guide.md)**: `sync_flag` / `sync_mdb` field semantics
- **Postman collection**: `docs/Yii3-API.postman_collection.json`

### Generating Reports

```bash
# Generate coverage reports
php quality quality:check --coverage

# Generate quality reports
php quality quality:check --report
```

---

## 🧪 Testing Strategy

### Test Types

The project uses **Codeception** (`codeception.yml`, `composer test` → `codecept run`) with these suites:

1. **Unit** (`tests/Unit`): Test individual classes and methods
2. **Functional** (`tests/Functional`): Test application workflows
3. **Api** (`tests/Api`): Test API endpoints via the REST module (starts `composer serve` on `http://127.0.0.1:8080`)
4. **Console** (`tests/Console`): Test console commands via the Cli module

### Running Tests

```bash
# Run all tests
composer test

# Run a single suite or test
vendor/bin/codecept run Unit
vendor/bin/codecept run Unit tests/Unit/Domain/Example/ExampleTest.php

# Run with coverage
vendor/bin/codecept run --coverage-html
```

### Test Examples

```php
// Unit Test Example (tests/Unit)
final class ExampleTest extends \Codeception\Test\Unit
{
    public function testCreateExample(): void
    {
        $example = Example::create(
            'Test',
            ResourceStatus::active(),
            DetailInfo::fromArray([])
        );
        
        $this->assertEquals('Test', $example->getName());
        $this->assertTrue($example->getStatus()->isActive());
    }
}

// API Test Example (tests/Api)
final class ExampleApiCest
{
    public function testCreateExample(ApiTester $I): void
    {
        $I->sendPost('/v1/example/create', [
            'name' => 'Test Example',
            'status' => 1,
        ]);
        
        $I->seeResponseCodeIs(200);
        $I->seeResponseContainsJson(['data' => ['name' => 'Test Example']]);
    }
}
```

---

## 📊 Monitoring & Logging

### Application Logging

Logging goes through `Psr\Log\LoggerInterface` (bound to `Yiisoft\Log\Logger` with file/stream/security targets in `config/common/di/logger.php`):

```php
// Structured logging
$this->logger->info('User created example', [
    'user_id' => $userId,
    'example_id' => $exampleId,
    'ip' => $request->getServerParam('REMOTE_ADDR')
]);
```

### Performance Monitoring

```php
// Performance metrics are collected by MetricsMiddleware / CustomMonitoringService
// (see src/Infrastructure/Core/Monitoring and the app/monitoring params group)
$startTime = microtime(true);
$result = $this->complexOperation();
$duration = (microtime(true) - $startTime) * 1000;

$this->logger->info('Operation completed', ['duration' => $duration]);
```

### Error Handling

```php
try {
    $result = $this->riskyOperation();
} catch (\Exception $e) {
    $this->logger->error('Operation failed', [
        'error' => $e->getMessage(),
        'trace' => $e->getTraceAsString(),
    ]);
    throw $e;
}
```

---

## 🚀 Deployment

### Production Deployment

#### 1. Environment Setup

```bash
# Set production environment (valid values: dev, test, prod)
export APP_ENV=prod
export APP_DEBUG=0
```

#### 2. Dependencies

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader
```

#### 3. Database

```bash
# Run migrations without interactive confirmation
./yii migrate:up --force-yes
```

#### 4. Docker Deployment

```bash
# Build the production image
make prod-build

# Push and deploy
make prod-push
make prod-deploy
```

### CI/CD Pipeline

#### GitHub Actions Example

```yaml
name: Deploy to Production
on:
  push:
    branches: [main]

jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
      - run: composer install --no-dev
      - run: php quality quality:check
      - run: php yii migrate:up --force-yes
      - name: Deploy to production
        run: |
          # Deployment commands
```

---

## 🔧 Maintenance

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

### Troubleshooting

#### Common Issues

```bash
# Clear caches
rm -rf runtime/cache/*
vendor/bin/psalm --clear-cache

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Check console configuration / available commands
./yii list

# Verify pending migrations and seeder discovery
./yii migrate:new
```

---

## 📞 Support & Resources

### Documentation

- **[Yii3 Documentation](https://www.yiiframework.com/doc/guide/)**: Official Yii3 guide
- **[Psalm Documentation](https://psalm.dev/)**: Static analysis tool
- **[PHPUnit Documentation](https://phpunit.de/)**: Testing framework

### Community

- **[Yii3 GitHub](https://github.com/yiisoft/app)**: Official repository
- **[Yii3 Discord](https://discord.gg/yiisoft)**: Community chat
- **[Stack Overflow](https://stackoverflow.com/questions/tagged/yii3)**: Q&A

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
