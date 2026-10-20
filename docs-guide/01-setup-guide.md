# Panduan Setup Yii3 API Skeleton

**Yii3 API Boilerplate** (`rahmatsyaparudin/yii3-api-boilerplate`) adalah starter project untuk membangun RESTful API menggunakan Yii3 dengan arsitektur Domain-Driven Design (DDD). Menyediakan struktur siap pakai, script helper (`scripts/skeleton-*.php`), dan contoh konfigurasi untuk mempercepat development API dengan prinsip clean architecture.

---

## 🏗️ Gambaran Arsitektur

Skeleton ini mengikuti prinsip **Domain-Driven Design (DDD)** dengan layer clean architecture:

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

### Fitur Utama

- **🎯 Domain-Driven Design**: Pemisahan business logic yang bersih
- **🔧 Type Safety**: Integrasi static analysis Psalm penuh
- **🧪 Testing Ready**: Setup test suite lengkap
- **🔒 Security**: Authentication, authorization, dan audit trail
- **📊 Quality Assurance**: Pengecekan kualitas kode otomatis
- **🐳 Docker Ready**: Setup containerization lengkap
- **📚 Dokumentasi**: Dokumentasi komprehensif sudah termasuk

---

## 🚀 Quick Start

### Prasyarat

- **PHP 8.2 - 8.5** dengan extension yang dibutuhkan
- **Composer** untuk dependency management
- **PostgreSQL** (atau **MySQL/MariaDB** via `db.default.driver`)
- **MongoDB** (opsional, untuk audit trail)
- **Redis** (opsional, cache/side service)
- **Docker** (opsional, untuk development tercontainer)

### 1. Buat Project Baru

```bash
# Buat project baru dari boilerplate
composer create-project rahmatsyaparudin/yii3-api-boilerplate yii3-api

# Masuk ke direktori project
cd yii3-api
```

### 2. Tambahkan Repository Skeleton

Untuk memakai boilerplate sebagai package yang bisa di-update, tambahkan ini ke `composer.json` project kamu:

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
        "skeleton:scripts": [
            "php scripts/skeleton-scripts.php"
        ],
        "skeleton:update": [
            "composer update rahmatsyaparudin/yii3-api-boilerplate --ignore-platform-reqs",
            "php scripts/skeleton-update.php"
        ],
        "skeleton:copy-config": [
            "php scripts/skeleton-copy-config.php"
        ],
        "skeleton:copy-examples": [
            "php scripts/skeleton-copy-examples.php"
        ]
    }
}
```

### 3. Install Skeleton

```bash
# Update dependencies
composer update --ignore-platform-reqs

# Install struktur skeleton (shared classes, config, console commands, quality script)
composer skeleton:update

# Salin file config (hanya pertama kali)
composer skeleton:copy-config

# Salin file contoh (hanya pertama kali)
composer skeleton:copy-examples
```

Versi skeleton saat ini dicatat di `scripts/skeleton.version` (saat ini `1.1.0`). Modul baru juga bisa digenerate dengan `php scripts/skeleton-generate-module.php --module=Product` (opsional `--table=<name>` dan `--db=<connection>` untuk migrasi di database non-default).

---

## 📁 Struktur Project

Setelah instalasi, project kamu akan punya struktur ini:

```
yii3-api/
├── config/                 # Konfigurasi aplikasi
│   ├── common/             # Konfigurasi bersama (params, routes, access, ...)
│   │   └── di/             # Definisi DI (db-*, jwt, middleware-di, ...)
│   ├── console/            # Konfigurasi console (commands.php)
│   ├── environments/       # Konfigurasi environment (dev, test, prod)
│   ├── web/                # Konfigurasi web
│   │   └── di/             # DI web (application.php, psr17.php)
│   └── configuration.php   # Peta plugin yiisoft/config
├── docs-guide/             # Dokumentasi
├── public/                 # Web root
│   └── index.php           # Entry point aplikasi
├── resources/              # Resource aplikasi
│   └── messages/           # File translasi (en, id)
├── scripts/                # Script skeleton & generator
│   ├── skeleton-update.php
│   ├── skeleton-copy-config.php
│   ├── skeleton-copy-examples.php
│   ├── skeleton-generate-module.php
│   └── skeleton.version    # Versi skeleton saat ini
├── src/                    # Source code
│   ├── Api/                # Layer API
│   │   ├── IndexAction.php # Action GET /
│   │   ├── Shared/         # Komponen API bersama (ResponseFactory, presenters)
│   │   └── V1/             # API versi 1 (Example, AnotherExample)
│   ├── Application/        # Layer Application
│   │   ├── Example/        # Application service, command, DTO
│   │   └── Shared/         # Komponen application bersama (factory)
│   ├── Console/            # Console command (hello, migrate:module, seed)
│   ├── Domain/             # Layer Domain
│   │   ├── Example/        # Entity domain, kontrak repository, service
│   │   └── Shared/         # Komponen domain bersama
│   ├── Infrastructure/     # Layer Infrastructure
│   │   ├── Common/Persistence/  # Implementasi repository
│   │   └── Core/           # Audit, Clock, Database, Monitoring, Security, Seeder, Time
│   ├── Migration/          # Migration, dikelompokkan per namespace modul
│   ├── Seeder/             # Seeder, fixture YAML, provider Faker
│   ├── Shared/             # Utility bersama (middleware, exception, value object)
│   ├── Environment.php     # Penanganan APP_ENV (dev/test/prod)
│   └── bootstrap.php       # Bootstrap: load vendor autoload + .env
├── tests/                  # Test suite Codeception
│   ├── Api/                # Test API (REST module)
│   ├── Console/            # Test console
│   ├── Functional/         # Test functional
│   ├── Support/            # Kelas pendukung test
│   └── Unit/               # Unit test
├── quality                 # QA runner (quality:check, test:run)
├── yii                     # Entry point console
└── vendor/                 # Dependencies
```

---

## 🔧 Konfigurasi

### Setup Environment

#### 1. Salin File Environment

```bash
# Salin konfigurasi environment
cp .env.example .env
```

#### 2. Konfigurasi Environment

Edit file `.env`:

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

# Service Token auth (opt-in): opaque bearer via tabel service_tokens
app.service_token.enabled=false

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
# Jalankan migration
./yii migrate:up

# Atau per modul (mis. hanya App\Migration\Example)
./yii migrate:module example

# Seed data awal (hanya development)
./yii seed --module=example

# Atau seed semua modul dengan jumlah custom (hanya development)
./yii seed --count=10

# Catatan: command seed hanya jalan di environment development (APP_ENV=dev)
```

---

## 🎯 Workflow Development

### Quality Assurance

Skeleton menyertakan tool quality assurance lengkap:

```bash
# Jalankan quality check lengkap (php-cs-fixer, psalm, codecept Unit, composer audit)
php quality quality:check

# Auto-fix masalah code style
php quality quality:check --fix

# Generate laporan coverage test
php quality quality:check --coverage

# Generate laporan analisis detail
php quality quality:check --report
```

### Testing

```bash
# Jalankan semua suite Codeception (Unit, Functional, Api, Console)
composer test            # alias untuk `codecept run`
vendor/bin/codecept run

# Jalankan suite tertentu
vendor/bin/codecept run Unit
vendor/bin/codecept run Functional
vendor/bin/codecept run Api
vendor/bin/codecept run Console

# Atau lewat quality script
php quality test:run --unit
php quality test:run --integration   # menjalankan suite Functional
php quality test:run --coverage
```

### Static Analysis

```bash
# Jalankan static analysis Psalm
vendor/bin/psalm

# Bersihkan cache lalu jalankan ulang
vendor/bin/psalm --clear-cache

# Cek file tertentu
vendor/bin/psalm src/Domain/Example/Entity/Example.php
```

---

## 🏗️ Komponen Arsitektur

### Domain Layer

Domain layer berisi business logic dan entity:

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

Application service mengoordinasi use case:

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

Implementasi repository menangani persistensi data:

```php
// src/Infrastructure/Common/Persistence/Example/ExampleRepository.php
final class ExampleRepository implements ExampleRepositoryInterface
{
    public function insert(Example $example): Example
    {
        // Operasi database dengan MongoDB sync / optimistic locking
    }
}
```

### API Layer

Action menangani HTTP request:

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

## 🔒 Fitur Security

### Authentication & Authorization

Middleware stack dirangkai di `config/web/di/application.php`:

```php
// Authentication JWT dan access control RBAC berjalan sebagai middleware
JwtMiddleware::class,      // App\Presentation\Core\Http\Middleware\JwtMiddleware
// ...
AccessMiddleware::class,   // App\Presentation\Core\Http\Middleware\AccessMiddleware
```

Pengecekan authorization lewat `AuthorizerInterface` (di-bind ke `App\Infrastructure\Core\Security\RbacAuthorizer` di `config/common/di/security-di.php`), dengan permission map di `config/common/access.php`.

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
        // Menulis ke tabel audit_logs dengan info actor
    }
}
```

---

## 🚀 Contoh Penggunaan API

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

### List Resource

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

Route dideklarasikan di `config/common/routes.php` (group `/v1`, mis. `GET /v1/example`, `POST /v1/example/create`, `PUT /v1/example/{id}`, `DELETE /v1/example/{id}`, `POST /v1/example/{id}/restore`).

---

## 🐳 Development dengan Docker

`Makefile` membungkus `docker compose` dengan file di `docker/` (`compose.yml` plus override `dev/`, `test/`, `prod/`).

### Environment Development

```bash
# Jalankan container development
make up

# Dapatkan shell / jalankan command di container app
make shell
make yii migrate:up
make yii seed

# Helper lain
make composer <args>                  # jalankan Composer di container
make cs-fix                           # jalankan PHP CS Fixer
make psalm                            # jalankan Psalm
make rector                           # jalankan Rector
make test                             # jalankan Codeception di stack test
make test-coverage                    # test dengan coverage
make composer-dependency-analyser     # analisis dependency
```

### Environment Production

```bash
# Build dan push image production, lalu deploy stack
make prod-build
make prod-push
make prod-deploy
```

---

## 📚 Dokumentasi

### Dokumentasi yang Tersedia

- **[Architecture Guide](02-architecture-guide.md)**: Gambaran arsitektur lengkap
- **[Quality Guide](21-quality-guide.md)**: Prosedur quality assurance
- **[Input Validator Guide](08-input-validator-guide.md)**: Panduan validasi request
- **[Sync Flag Guide](22-sync-flag-guide.md)**: Semantik field `sync_flag` / `sync_mdb`
- **Postman collection**: `docs-guide/Yii3-API.postman_collection.json`

### Generate Laporan

```bash
# Generate laporan coverage
php quality quality:check --coverage

# Generate laporan quality
php quality quality:check --report
```

---

## 🧪 Strategi Testing

### Tipe Test

Project memakai **Codeception** (`codeception.yml`, `composer test` → `codecept run`) dengan suite:

1. **Unit** (`tests/Unit`): Test class dan method individual
2. **Functional** (`tests/Functional`): Test workflow aplikasi
3. **Api** (`tests/Api`): Test endpoint API via REST module (menjalankan `composer serve` di `http://127.0.0.1:8080`)
4. **Console** (`tests/Console`): Test console command via Cli module

### Menjalankan Test

```bash
# Jalankan semua test
composer test

# Jalankan satu suite atau test
vendor/bin/codecept run Unit
vendor/bin/codecept run Unit tests/Unit/Domain/Example/ExampleTest.php

# Jalankan dengan coverage
vendor/bin/codecept run --coverage-html
```

### Contoh Test

```php
// Contoh Unit Test (tests/Unit)
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

// Contoh API Test (tests/Api)
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

### Logging Aplikasi

Logging lewat `Psr\Log\LoggerInterface` (di-bind ke `Yiisoft\Log\Logger` dengan target file/stream/security di `config/common/di/logger.php`):

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
// Metrik performa dikumpulkan oleh MetricsMiddleware / CustomMonitoringService
// (lihat src/Infrastructure/Core/Monitoring dan params group app/monitoring)
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

### Deployment Production

#### 1. Setup Environment

```bash
# Set environment production (nilai valid: dev, test, prod)
export APP_ENV=prod
export APP_DEBUG=0
```

#### 2. Dependencies

```bash
# Install dependency production
composer install --no-dev --optimize-autoloader
```

#### 3. Database

```bash
# Jalankan migration tanpa konfirmasi interaktif
./yii migrate:up --force-yes
```

#### 4. Deployment Docker

```bash
# Build image production
make prod-build

# Push dan deploy
make prod-push
make prod-deploy
```

### CI/CD Pipeline

#### Contoh GitHub Actions

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
          # Command deployment
```

---

## 🔧 Maintenance

### Tugas Rutin

#### Mingguan
- Update dependency: `composer update`
- Jalankan quality check: `php quality quality:check`
- Review tren coverage test

#### Bulanan
- Review dan update konfigurasi quality
- Update coding standard
- Tambahkan quality check baru bila perlu
- Review optimasi performa

#### Kuartalan
- Update dependency mayor
- Review threshold quality gate
- Upgrade versi tool
- Meeting review arsitektur

### Troubleshooting

#### Masalah Umum

```bash
# Bersihkan cache
rm -rf runtime/cache/*
vendor/bin/psalm --clear-cache

# Install ulang dependency
composer install --no-dev --optimize-autoloader

# Cek konfigurasi console / command yang tersedia
./yii list

# Verifikasi migration pending dan discovery seeder
./yii migrate:new
```

---

## 📞 Support & Resource

### Dokumentasi

- **[Dokumentasi Yii3](https://www.yiiframework.com/doc/guide/)**: Guide resmi Yii3
- **[Dokumentasi Psalm](https://psalm.dev/)**: Tool static analysis
- **[Dokumentasi PHPUnit](https://phpunit.de/)**: Testing framework

### Komunitas

- **[Yii3 GitHub](https://github.com/yiisoft/app)**: Repository resmi
- **[Yii3 Discord](https://discord.gg/yiisoft)**: Chat komunitas
- **[Stack Overflow](https://stackoverflow.com/questions/tagged/yii3)**: Q&A

### Quality Tool

- **[PHP CS Fixer](https://cs.symfony.com/)**: Fixer code style
- **[Composer Audit](https://github.com/composer/composer/blob/main/src/Composer/Command/AuditCommand.php)**: Security audit
- **[Codeception](https://codeception.com/)**: Testing framework

---

## 🎯 Best Practice

### Kualitas Kode

- **Type Safety**: Selalu pakai strict types dan type annotation
- **Error Handling**: Implementasikan exception handling yang benar
- **Testing**: Jaga coverage test tinggi (>80%)
- **Dokumentasi**: Jaga dokumentasi tetap up-to-date

### Security

- **Input Validation**: Validasi semua input user
- **Authentication**: Pakai JWT token untuk autentikasi API
- **Authorization**: Implementasikan RBAC untuk access control
- **Audit Trail**: Log semua operasi penting

### Performa

- **Optimasi Database**: Pakai index dan optimasi query yang tepat
- **Caching**: Implementasikan strategi caching multi-level
- **Async Processing**: Pakai queue untuk operasi long-running
- **Monitoring**: Monitor performa aplikasi

---

## 🎉 Kesimpulan

Yii3 API Skeleton menyediakan fondasi solid untuk membangun RESTful API modern, scalable, dan maintainable dengan prinsip Domain-Driven Design. Tool quality assurance, dokumentasi komprehensif, dan pola clean architecture yang disertakan memastikan development API kamu mengikuti best practice sejak hari pertama.

Manfaat utama:

- **🏗️ Clean Architecture**: Prinsip DDD untuk kode yang maintainable
- **🔒 Type Safety**: Static analysis penuh dengan Psalm
- **🧪 Testing Ready**: Setup test suite lengkap
- **📊 Quality Assurance**: Quality check otomatis
- **🐳 Docker Ready**: Dukungan containerization
- **📚 Dokumentasi Lengkap**: Dokumentasi komplit sudah termasuk

Mulai bangun project API kamu berikutnya dengan percaya diri memakai Yii3 API Skeleton! 🚀
