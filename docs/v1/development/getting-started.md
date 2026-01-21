# Getting Started

## 🎯 Overview

This guide will help you set up and run the Yii3 API project locally for development and testing.

## 📋 Prerequisites

### 🐧 System Requirements
- **PHP**: 8.1 or higher
- **Composer**: Latest stable version
- **Database**: MySQL 8.0+ or PostgreSQL 13+
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **Node.js**: 16+ (for frontend assets, optional)

### 🔧 Optional Tools
- **Docker**: 20.10+ (for containerized development)
- **Git**: Latest version
- **Postman** or similar API testing tool

---

## 🚀 Quick Installation

### 1. 📥 Clone Repository
```bash
git clone https://github.com/your-org/yii3-api.git
cd yii3-api
```

### 2. 📦 Install Dependencies
```bash
composer install
```

### 3. ⚙️ Environment Configuration
```bash
cp .env.example .env
```

Edit `.env` file:
```env
# Database
DB_DSN=mysql:host=localhost;dbname=yii3_api
DB_USERNAME=your_username
DB_PASSWORD=your_password

# Application
APP_ENV=development
APP_DEBUG=true
APP_URL=http://localhost:8080

# Security
JWT_SECRET=your-super-secret-jwt-key-here
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080

# Cache
REDIS_HOST=localhost
REDIS_PORT=6379
```

### 4. 🗄️ Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE yii3_api CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Run migrations
php yii migrate

# Load sample data (optional)
php yii fixture/load
```

### 5. 🌐 Start Development Server
```bash
# Built-in PHP server
php yii serve

# Or with specific host/port
php yii serve --host=0.0.0.0 --port=8080
```

### 6. ✅ Verify Installation
```bash
# Check API health
curl http://localhost:8080/api/health

# Should return:
{"status":"ok","timestamp":"2024-01-21T12:00:00Z"}
```

---

## 🐳 Docker Setup (Alternative)

### 1. 📋 Docker Compose
```yaml
# docker-compose.yml
version: '3.8'

services:
  app:
    build: .
    ports:
      - "8080:8080"
    environment:
      - DB_DSN=mysql:host=db;dbname=yii3_api
      - DB_USERNAME=root
      - DB_PASSWORD=secret
    depends_on:
      - db
      - redis

  db:
    image: mysql:8.0
    environment:
      MYSQL_ROOT_PASSWORD: secret
      MYSQL_DATABASE: yii3_api
    volumes:
      - mysql_data:/var/lib/mysql

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

volumes:
  mysql_data:
```

### 2. 🚀 Start Containers
```bash
docker-compose up -d

# Run migrations
docker-compose exec app php yii migrate

# Access logs
docker-compose logs -f app
```

---

## 🔧 Development Workflow

### 📝 Code Structure
```
src/
├── Api/              # API Layer (Controllers, Actions)
├── Application/      # Application Layer (Use Cases, Services)
├── Domain/           # Domain Layer (Entities, Value Objects)
├── Infrastructure/   # Infrastructure Layer (Repositories, External)
└── Shared/           # Shared Layer (Common utilities)
```

### 🔄 Typical Development Flow

#### 1. 🎯 Define Domain Model
```php
// src/Domain/Brand/Entity/Brand.php
final class Brand
{
    private function __construct(
        private ?int $id,
        private string $name,
        private Status $status
    ) {}
    
    public static function create(string $name, Status $status): self
    {
        // Business logic
        return new self(null, $name, $status);
    }
}
```

#### 2. 🏗️ Create Application Service
```php
// src/Application/Brand/BrandApplicationService.php
final class BrandApplicationService
{
    public function create(array $data): Brand
    {
        // Use case implementation
        $brand = Brand::create($data['name'], Status::from($data['status']));
        return $this->repository->save($brand);
    }
}
```

#### 3. 🌐 Implement API Endpoint
```php
// src/Api/V1/Brand/Action/BrandCreateAction.php
final class BrandCreateAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $data = $request->getParsedBody();
        $brand = $this->brandService->create($data);
        return $this->responseFactory->success($brand->toArray());
    }
}
```

#### 4. 🗄️ Add Repository Implementation
```php
// src/Infrastructure/Persistence/Brand/BrandRepository.php
final class BrandRepository implements BrandRepositoryInterface
{
    public function save(Brand $brand): Brand
    {
        // Database operations
        return $this->persist($brand);
    }
}
```

---

## 🧪 Testing

### 📋 Test Types
- **Unit Tests**: Domain logic and value objects
- **Integration Tests**: Repository and external services
- **API Tests**: HTTP endpoints and responses
- **Feature Tests**: Complete use cases

### 🧪 Run Tests
```bash
# All tests
composer test

# Unit tests only
composer test:unit

# Integration tests only
composer test:integration

# API tests only
composer test:api

# With coverage
composer test:coverage
```

### 📝 Test Example
```php
// tests/Unit/Domain/Brand/BrandTest.php
final class BrandTest extends TestCase
{
    public function test_can_create_brand(): void
    {
        $brand = Brand::create('Test Brand', Status::draft());
        
        $this->assertSame('Test Brand', $brand->getName());
        $this->assertTrue($brand->getStatus()->isDraft());
    }
    
    public function test_cannot_create_brand_with_empty_name(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Brand::create('', Status::draft());
    }
}
```

---

## 🐛 Debugging

### 🔍 Debug Tools
```bash
# Enable debug mode
export APP_DEBUG=true

# Check configuration
php yii config

# View routes
php yii route/list

# Database info
php yii db/info
```

### 📝 Logging
```php
// Log messages
Yii::info('Brand created: ' . $brand->getId());
Yii::error('Database connection failed');
Yii::warning('Rate limit approaching');
```

### 🐛 Common Issues

#### Database Connection
```bash
# Check database status
php yii db/info

# Test connection
php yii db/test
```

#### Permission Issues
```bash
# Fix permissions
chmod -R 755 runtime/
chmod -R 755 web/
```

#### CORS Issues
```env
# Add allowed origins
CORS_ALLOWED_ORIGINS=http://localhost:3000,http://localhost:8080
```

---

## 📊 Performance

### ⚡ Optimization Tips

#### Database
```php
// Use query builder efficiently
$query = (new Query($db))
    ->select(['id', 'name'])
    ->from('brand')
    ->limit(20);

// Use generators for large datasets
foreach ($this->listAllGenerator($query) as $row) {
    // Process row
}
```

#### Caching
```php
// Cache expensive operations
$brands = Yii::$app->cache->getOrSet(
    'brands:active',
    fn() => $this->brandRepository->findActive(),
    3600
);
```

#### Pagination
```php
// Efficient pagination
$criteria = SearchCriteria::fromPayload($payload, $allowedSort);
$result = $this->repository->list($criteria);
```

---

## 🔒 Security

### 🛡️ Security Best Practices

#### Input Validation
```php
// Always validate input
$validator = new BrandInputValidator();
$validator->validate(ValidationContext::CREATE, $params);
```

#### Authentication
```php
// Use JWT tokens
$token = $this->jwtService->encode(['user_id' => $userId]);
```

#### Authorization
```php
// Check permissions
if (!$this->accessChecker->userHasPermission($userId, 'brand.create')) {
    throw new ForbiddenException();
}
```

---

## 📚 API Documentation

### 🌐 Local Documentation
Start the server and visit:
- **API Docs**: http://localhost:8080/docs
- **Swagger UI**: http://localhost:8080/api/swagger
- **Health Check**: http://localhost:8080/api/health

### 📝 API Testing
```bash
# Health check
curl http://localhost:8080/api/health

# Create brand (with auth)
curl -X POST http://localhost:8080/api/v1/brands \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -d '{"name":"Test Brand","status":"draft"}'
```

---

## 🚀 Deployment

### 📦 Production Checklist
- [ ] Set `APP_ENV=production`
- [ ] Disable debug mode
- [ ] Configure proper database
- [ ] Set up SSL certificates
- [ ] Configure caching
- [ ] Set up monitoring
- [ ] Run database migrations
- [ ] Test all endpoints

### 🐳 Docker Deployment
```bash
# Build production image
docker build -t yii3-api:latest .

# Run with environment file
docker run -d --env-file .env.production -p 80:8080 yii3-api:latest
```

---

## 🤝 Contributing

### 📋 Development Guidelines
1. Follow PSR-12 coding standards
2. Write tests for new features
3. Update documentation
4. Use meaningful commit messages
5. Create pull requests for review

### 🔄 Git Workflow
```bash
# Create feature branch
git checkout -b feature/brand-validation

# Make changes
git add .
git commit -m "Add brand validation rules"

# Push and create PR
git push origin feature/brand-validation
```

---

## 📞 Support

### 🆘 Troubleshooting
1. Check logs in `runtime/logs/`
2. Verify environment configuration
3. Test database connection
4. Check dependencies with `composer diagnose`

### 📚 Resources
- [Official Documentation](../README.md)
- [API Reference](../api/brand-api.md)
- [Architecture Guide](../architecture/ddd.md)
- [Community Forum](https://github.com/your-org/yii3-api/discussions)

---

## 🔄 Next Steps

After completing setup:

1. 📖 Read [Architecture Overview](../architecture/ddd.md)
2. 🎯 Explore [Brand Domain](../domain/brand.md)
3. 🌐 Try [API Endpoints](../api/brand-endpoints.md)
4. 🧪 Run [Tests](testing.md)
5. 📚 Review [Coding Standards](coding-standards.md)

Happy coding! 🚀
