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

- **PHP 8.1+** with required extensions
- **Composer** for dependency management
- **PostgreSQL** database
- **MongoDB** (optional, for audit trails)
- **Docker** (optional, for containerized development)

### 1. Create New Project

```bash
# Create new Yii3 project
composer create-project --prefer-dist yiisoft/app yii3-api

# Navigate to project directory
cd yii3-api
```

## 2. Add the repository and package to `composer.json` 

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
"rahmatsyaparudin/yii3-api-skeleton": "dev-main"
```

### Add this to `composer.json` `scripts` 
```json
"skeleton-update": [
    "composer update rahmatsyaparudin/yii3-api-skeleton --ignore-platform-reqs",
    "php scripts/install-skeleton.php"
],
"skeleton-copy-examples": [
    "php scripts/skeleton-copy-examples.php"
]
```

### 3. Install Skeleton

```bash
# Update dependencies
composer update --ignore-platform-reqs

# Install skeleton structure
composer skeleton-update

# Copy example files (first time only)
composer skeleton-copy-examples
```

---

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
├── src/                    # Source code
│   ├── Api/                # API layer
│   │   ├── V1/             # API version 1
│   │   └── Shared/         # Shared API components
│   ├── Application/        # Application layer
│   │   └── Example/        # Application services
│   ├── Domain/             # Domain layer
│   │   ├── Example/        # Domain entities
│   │   └── Shared/         # Shared domain components
│   ├── Infrastructure/      # Infrastructure layer
│   │   ├── Audit/         # Audit services
│   │   ├── Database/      # Database implementations
│   │   ├── Persistence/   # Repository implementations
│   │   └── Security/      # Security services
│   └── Shared/            # Shared utilities
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

app.config.code=enterEDC
app.config.name=enterEDC
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

# SSO Configuration (External Keycloak)
app.jwt.secret=secret-key-harus-panjang-256-bit
app.jwt.algorithm=HS256
app.jwt.issuer=https://sso.dev-enterkomputer.com
app.jwt.audience=https://sso.dev-enterkomputer.com

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

#### 3. Database Migration

```bash
# Run database migrations
./yii migrate:up

# Seed initial data (development only)
./yii seed:example

# Or seed with custom options (development only)
./yii seed:example --count=10 --truncate

# Note: Seed commands only work in development environment (APP_ENV=dev)
```

---

## 🎯 Development Workflow

### Quality Assurance

The skeleton includes comprehensive quality assurance tools:

```bash
# Run complete quality check suite
php quality

# Auto-fix code style issues
php quality --fix

# Generate test coverage reports
php quality --coverage

# Generate detailed analysis reports
php quality --report
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
        $this->domainService->validateUniqueValue(...);
        
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
// src/Infrastructure/Persistence/Example/ExampleRepository.php
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

### Input Validation

```php
// Request validation
final class ExampleValidator
{
    public function validate(array $data, ValidationContext $context): void
    {
        $this->validator->validate($data, $context);
    }
}
```

### Audit Trail

```php
// Automatic audit logging
final class DatabaseAuditService implements AuditServiceInterface
{
    public function log(string $tableName, int $recordId, string $action, ?array $oldValues = null, ?array $newValues = null): void
    {
        // Log to database with actor information
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
# Generate API documentation
php yii docs:generate

# Generate coverage reports
php quality --coverage

# Generate quality reports
php quality --report
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
# Run all tests
vendor/bin/phpunit

# Run with coverage
vendor/bin/phpunit --coverage-html tests/coverage/html

# Run specific test
vendor/bin/phpunit tests/Unit/Domain/Example/ExampleTest.php
```

### Test Examples

```php
// Unit Test Example
class ExampleTest extends TestCase
{
    public function testCreateExample(): void
    {
        $example = Example::create('Test', Status::ACTIVE, DetailInfo::empty());
        
        $this->assertEquals('Test', $example->getName());
        $this->assertEquals(Status::ACTIVE, $example->getStatus());
    }
}

// API Test Example
class ExampleApiCest extends ApiTester
{
    public function testCreateExample(): void
    {
        $this->sendPost('/api/v1/examples', [
            'name' => 'Test Example',
            'status' => 'active'
        ]);
        
        $this->seeResponseCode(201);
        $this->seeJsonContains(['name' => 'Test Example']);
    }
}
```

---

## 📊 Monitoring & Logging

### Application Logging

```php
// Structured logging
Yii::info('User created example', [
    'user_id' => $userId,
    'example_id' => $exampleId,
    'ip' => $request->getServerParam('REMOTE_ADDR')
]);
```

### Performance Monitoring

```php
// Performance metrics
$startTime = microtime(true);
$result = $this->complexOperation();
$duration = (microtime(true) - $startTime) * 1000;

Yii::info('Operation completed', ['duration' => $duration]);
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

---

## 🚀 Deployment

### Production Deployment

#### 1. Environment Setup

```bash
# Set production environment
export YII_ENV=prod
export YII_DEBUG=false
export APP_ENV=production
```

#### 2. Dependencies

```bash
# Install production dependencies
composer install --no-dev --optimize-autoloader
```

#### 3. Database

```bash
# Run migrations
php yii migrate --interactive=0

# Optimize database
php yii db/optimize
```

#### 4. Cache

```bash
# Clear all caches
php yii cache/flush-all

# Warm up caches
php yii cache/warm-up
```

#### 5. Docker Deployment

```bash
# Build production image
docker build -t yii3-api:latest .

# Run with Docker Compose
docker-compose -f docker/prod/compose.yml up -d
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
      - run: php quality
      - run: php yii migrate --interactive=0
      - name: Deploy to production
        run: |
          # Deployment commands
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
php yii cache/flush-all
vendor/bin/psalm --clear-cache

# Reinstall dependencies
composer install --no-dev --optimize-autoloader

# Check configuration
php yii config/test

# Run diagnostics
php yii diagnose
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
