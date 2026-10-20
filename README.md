# Panduan Setup Yii3 API Skeleton

**Yii3 API Skeleton** adalah starter project untuk membangun RESTful API dengan Yii3 dan arsitektur Domain-Driven Design (DDD). Skeleton ini menyediakan struktur siap pakai, script helper, dan contoh konfigurasi untuk mempercepat development API dengan prinsip clean architecture.

---

## 🏗️ Gambaran Arsitektur

Skeleton ini mengikuti prinsip **Domain-Driven Design (DDD)** dengan layer clean architecture:

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

### Fitur Utama

- **🎯 Domain-Driven Design**: Pemisahan business logic yang bersih
- **🔧 Type Safety**: Integrasi static analysis Psalm penuh
- **🧪 Testing Ready**: Setup test suite lengkap
- **🔒 Security**: Authentication, authorization, dan audit trail
- **📊 Quality Assurance**: Code quality check otomatis
- **🐳 Docker Ready**: Setup containerization lengkap
- **📚 Dokumentasi**: Dokumentasi komprehensif tersedia

---

## 🚀 Quick Start

### Prasyarat

- **PHP 8.3+** dengan extension yang dibutuhkan
- **Composer** untuk dependency management
- **PostgreSQL** sebagai database
- **MongoDB** (opsional, untuk audit trail)
- **Docker** (opsional, untuk development terkontainer)

### 1. Buat Project Baru

```bash
composer create-project --prefer-dist yiisoft/app-api ./
```

### 2. Tambahkan repository dan package ke `composer.json`

Buka `composer.json` project kamu dan tambahkan bagian berikut:

#### Tambahkan ini ke `repositories` di `composer.json`
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

#### Tambahkan ini ke `require-dev` di `composer.json`
```json
"rahmatsyaparudin/yii3-api-boilerplate": "dev-main"
```

#### Tambahkan ini ke `scripts` di `composer.json`
```json
"skeleton:scripts": [
            "@php scripts/skeleton-scripts.php"
        ],
        "skeleton:version": [
            "@php scripts/skeleton-version.php"
        ],
        "skeleton:update": [
            "composer update rahmatsyaparudin/yii3-api-boilerplate --ignore-platform-reqs",
            "@php scripts/skeleton-scripts.php",
            "@php scripts/skeleton-update.php"
        ],
        "skeleton:copy-config": [
            "@php scripts/skeleton-copy-config.php"
        ],
        "skeleton:copy-examples": [
            "@php scripts/skeleton-copy-examples.php"
        ],
        "skeleton:generate-module": [
            "@php scripts/skeleton-generate-module.php"
        ]
```

### 3. Update Composer
Update dependency composer
```bash
composer install --ignore-platform-reqs
```

### 4. Salin script skeleton

Buat directory `scripts` lalu salin folder `scripts` dari package ke root project:

```bash
mkdir scripts; cp -r -Force vendor/rahmatsyaparudin/yii3-api-boilerplate/scripts/* ./scripts
```

### 5. Install Skeleton
Install struktur skeleton
```bash
composer skeleton:update
```

Salin file config (hanya pertama kali)

Perintah ini menyalin `.env.example` → `.env`, `.gitignore`, file pesan (`resources/messages/{en,id}/`), dan file konfigurasi skeleton lainnya.

```bash
composer skeleton:copy-config
```

Salin file contoh (hanya pertama kali)
```bash
composer skeleton:copy-examples
```

### Cek Update Skeleton

Bandingkan versi skeleton yang terpasang (`scripts/skeleton.version`) dengan versi yang dibawa package:

```bash
composer skeleton:version
```

Perintah ini melaporkan apakah project sudah up to date atau ada update tersedia (misalnya `1.2.9 → 1.3.0`), yang jika ada jalankan `composer skeleton:update`.

### 6. Generate Modul Baru

Gunakan module generator bawaan untuk membuat modul API baru dengan struktur lengkap:
Generate modul baru (misalnya Product)
```bash
composer skeleton:generate-module -- --module=Product --table=product_management
```

Atau pakai script PHP langsung (alternatif):
```bash
php scripts/skeleton-generate-module.php --module=Product --table=product_management
```

Secara default migrasi modul berjalan di koneksi `default`. Berikan `--db=<nama>` untuk memetakan modul ke koneksi lain (blok env `db.<nama>.*`):
```bash
composer skeleton:generate-module -- --module=AuditLog --db=audit
```

> **Catatan:** Skeleton sudah dilengkapi modul Example yang mendemokan struktur lengkap. Gunakan generator di atas untuk membuat modul tambahan sesuai kebutuhanmu.

### Apa yang Dibuat Generator

Module generator membuat struktur modul lengkap mengikuti arsitektur DDD. Ini yang kamu dapatkan saat generate modul baru (berdasarkan modul Example yang ada):

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
    └── ProductInputValidator.php  # Aturan validasi request
```

#### **📁 Application Layer** (`src/Application/{Module}/`)
```
src/Application/Product/
├── Command/
│   ├── CreateProductCommand.php   # Command DTO create
│   └── UpdateProductCommand.php   # Command DTO update
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
│   └── ProductRepositoryInterface.php # Interface repository
└── Service/
    └── ProductDomainService.php  # Domain service
```

#### **📁 Infrastructure Layer** (`src/Infrastructure/Common/Persistence/{Module}/`)
```
src/Infrastructure/Common/Persistence/Product/
├── ProductRepository.php         # Implementasi repository
└── MdbProductSchema.php          # Schema MongoDB
```

#### **📁 Database & Seeding**
```
src/Migration/
└── Product/
    └── M20240130123457CreateProductTable.php  # Migrasi database (namespace App\Migration\Product)

src/Seeder/
├── SeedProductData.php           # Class seeder
└── Fixtures/
    └── product.yaml              # Fixture Alice untuk data test
```
#### **⚙️ Update Konfigurasi**
Generator otomatis mengupdate file konfigurasi:

- **`config/common/repository.php`** - Menambah DI binding repository
- **`config/common/access.php`** - Menambah aturan access control
- **`config/common/routes.php`** - Menambah route API beserta permission
- **`config/common/migration.php`** - Mendaftarkan koneksi migrasi modul (`moduleConnections`)

#### **🔧 Fitur yang Disertakan**
- **✅ Operasi CRUD Lengkap** - Create, Read, Update, Delete, Restore
- **✅ RESTful API Endpoint** - Mengikuti konvensi REST
- **✅ Request Validation** - Aturan validasi input
- **✅ Sistem Permission** - Access control berbasis role
- **✅ Migrasi Database** - Manajemen schema
- **✅ Data Seeding** - Generate data test dengan fixture Alice
- **✅ Type Safety** - Kompatibel Psalm penuh
- **✅ Error Handling** - Response error terstandardisasi

### Endpoint API yang Dihasilkan

Untuk setiap modul, endpoint berikut otomatis dibuat:

| Method   | Endpoint                    | Action      | Permission        |
|----------|-----------------------------|-------------|-------------------|
| GET      | `/v1/{module}`              | List item   | `{module}.index`  |
| POST     | `/v1/{module}/data`         | Data item   | `{module}.data`   |
| GET      | `/v1/{module}/{id}`         | View item   | `{module}.view`   |
| POST     | `/v1/{module}/create`       | Create item | `{module}.create` |
| PUT      | `/v1/{module}/{id}`         | Update item | `{module}.update` |
| DELETE   | `/v1/{module}/{id}`         | Delete item | `{module}.delete` |
| POST     | `/v1/{module}/{id}/restore` | Restore item| `{module}.restore`|

### 📋 Modul yang Tersedia Saat Ini

Skeleton sudah menyertakan modul berikut:

#### **✅ Modul Example** (Termasuk)
- **Tujuan:** Mendemokan struktur modul standalone lengkap
- **Endpoint:** `/v1/example/*`
- **Pemakaian:** Implementasi referensi untuk belajar dan testing
- **File:** Struktur DDD lengkap di semua layer

#### **✅ Modul AnotherExample** (Termasuk)
- **Tujuan:** Mendemokan modul **child** — entity yang berelasi ke entity lain (`Example`) dan pakai sync master–origin
- **Endpoint:** `/v1/another-example/*`
- **Pemakaian:** Referensi untuk modul dengan relasi parent→child

#### **✅ Modul System — Service Token** (Termasuk)
- **Tujuan:** Bearer token opaque long-lived untuk service eksternal (POS, script, integrasi) — alternatif JWT tanpa akun SSO
- **Endpoint:** `/v1/system/service-token/*`
- **Dokumentasi:** [`docs-guide/23-system_service-token.md`](docs-guide/23-system_service-token.md)

#### **🔧 Modul Custom** (Generate sesuai kebutuhan)
- **Product, Category, Brand, Order, User, dll.**
- **Tujuan:** Modul spesifik bisnismu
- **Generate:** `composer skeleton:generate-module -- --module=NamaModul --table=nama_tabel` atau `php scripts/skeleton-generate-module.php --module=NamaModul --table=nama_tabel`
- **Tabel custom:** Pakai `--table=nama_tabel` (misal `--module=Product --table=product_management`)
- **Koneksi custom:** Pakai `--db=nama_koneksi` untuk migrasi di database non-default (misal `--module=AuditLog --db=audit`)
- **Kustomisasi:** Ubah file hasil generate sesuai business logic-mu

#### Perbedaan Example vs AnotherExample

Kedua modul demo ini sengaja dibuat berbeda untuk mendemokan dua pola:

| Aspek | `Example` | `AnotherExample` |
|-------|-----------|------------------|
| Peran | Modul referensi **standalone** | Modul **child** — mendemokan relasi ke entity lain |
| Field khusus | `name`, `status`, `detail_info` | + `example_id` (FK ke `example`), `origin_id`, `sync_flag` |
| Sinkronisasi | `SyncMdb` (sync ke MongoDB) saja | `SyncMdb` + `SyncFlag` (sync master–origin antar instance) |
| `detail_info` | `DetailInfoFactory` shared | Factory custom `AnotherExampleDetailInfoFactory` + DTO `AnotherExampleDetailInfo` (embed data parent `example`) |
| Migrasi | `src/Migration/Example/` | Juga di `src/Migration/Example/` — satu grup `migrate:module example` |
| Permission | `example.*` | `another-example.*` (aturan role sama: `$isKasir`/`$isSuperAdmin`) |

Gunakan `Example` sebagai titik awal untuk modul standalone biasa, dan `AnotherExample` sebagai referensi kalau modulmu punya relasi ke entity lain atau butuh sync antar-instance.

## 📁 Struktur Project

Setelah instalasi, project kamu akan punya struktur ini:

```
yii3-api/
├── config/                 # Konfigurasi aplikasi
│   ├── common/             # Konfigurasi bersama
│   ├── console/            # Konfigurasi console
│   ├── environments/       # Konfigurasi environment
│   └── web/                # Konfigurasi web
├── docs-guide/             # Dokumentasi
│   ├── documentation.html  # Viewer standalone (buka langsung di browser)
│   ├── 02-architecture-guide.md # Dokumentasi arsitektur
│   ├── 21-quality-guide.md    # Panduan quality assurance
│   └── 01-setup-guide.md      # Panduan setup
├── public/                 # Web root
│   └── index.php           # Entry point aplikasi
├── resources/              # Resource aplikasi
│   └── messages/           # File translasi
├── scripts/                # Script utilitas
│   ├── skeleton-generate-module.php # Module generator
│   ├── skeleton-scripts.php # Installer script skeleton
│   ├── skeleton-update.php # Installer skeleton
│   ├── skeleton-copy-examples.php # Penyalin file contoh
│   ├── skeleton-copy-config.php # Penyalin file config
│   └── skeleton.version    # Penanda versi skeleton terpasang
├── src/                    # Source code
│   ├── Api/                # API layer
│   │   ├── V1/             # API versi 1
│   │   │   ├── Example/    # Endpoint API Example
│   │   │   ├── AnotherExample/ # Endpoint API AnotherExample
│   │   │   ├── System/     # Endpoint API System (Service Token)
│   │   │   └── Shared/     # Komponen API bersama
│   │   └── Shared/         # Komponen API bersama
│   ├── Application/        # Application layer
│   │   ├── Example/        # Application service Example
│   │   ├── AnotherExample/ # Application service AnotherExample
│   │   ├── System/         # Application service System
│   │   └── Shared/         # Service bersama
│   ├── Domain/             # Domain layer
│   │   ├── Example/        # Domain entity Example
│   │   ├── AnotherExample/ # Domain entity AnotherExample
│   │   ├── System/         # Domain entity System
│   │   └── Shared/         # Komponen domain bersama
│   ├── Infrastructure/     # Infrastructure layer
│   │   ├── Core/           # Core infrastructure
│   │   │   ├── Audit/      # Service audit
│   │   │   ├── Database/   # Implementasi database
│   │   │   └── Security/   # Service security
│   │   └── Common/         # Common infrastructure
│   │       └── Persistence/  # Implementasi repository
│   │           ├── Example/    # Repository Example
│   │           ├── AnotherExample/ # Repository AnotherExample
│   │           └── System/       # Repository System
│   ├── Migration/          # Migrasi database (subfolder modul terisolasi)
│   │   ├── Auditable/      # audit_logs + rate_limits (opt-in, via migrate:module)
│   │   ├── Example/        # Migrasi modul Example (via migrate:module)
│   │   └── ServiceToken/   # Migrasi service_tokens (via migrate:module)
│   ├── Seeder/             # Data seeder
│   │   ├── Fixtures/       # Fixture Alice
│   │   │   └── example.yaml
│   │   ├── Faker/          # Provider Faker
│   │   └── SeedExampleData.php
│   └── Shared/             # Utilitas bersama
│       ├── ApplicationParams.php
│       ├── Common/         # Helper bersama project
│       └── Core/           # Komponen core bersama
│           ├── Context/    # Konteks validasi
│           ├── Dto/        # Data Transfer Object
│           ├── Enums/      # Enum bersama
│           ├── ErrorHandler/ # Utilitas error handling
│           ├── Exception/  # Custom exception
│           ├── Query/      # Utilitas query
│           ├── Request/    # Penanganan request
│           ├── Security/   # Utilitas security
│           ├── Utility/    # Utilitas umum
│           ├── Validation/ # Class validasi
│           └── ValueObject/ # Value object
├── tests/                  # Test suite
│   ├── Api/                # API test
│   ├── Functional/         # Functional test
│   ├── Support/            # Class pendukung test
│   └── Unit/               # Unit test
└── vendor/                 # Dependency
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
# Environment Aplikasi
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

# Konfigurasi Optimistic Lock
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=["example","example_1"]

# Konfigurasi SSO (Keycloak eksternal)
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

> **Catatan:** `app.config.language` mengatur bahasa aplikasi (dipakai untuk translasi). Saat `APP_ENV=dev` (atau `development`), `ApplicationParams::$environment` di-set ke `development` dan endpoint root index (`GET /`) menyertakannya di response:
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
> Di production (`APP_ENV` selain `dev`/`development`) field `environment` dihilangkan.

#### 3. Migrasi Database

```bash
# Jalankan migrasi database
./yii migrate:up

# Seed data awal (hanya development)
./yii seed --module=example

# Atau seed dengan opsi custom (hanya development)
./yii seed --module=example --count=10

# Catatan: Command seed hanya jalan di environment development (APP_ENV=dev)
```

##### Migrasi Terisolasi per Modul

Migrasi tinggal di subfolder per-modul `src/Migration/` (namespace `App\Migration\<Modul>`) dan dijalankan dengan `migrate:module`. Setiap modul **wajib di-map ke koneksi** di `config/common/migration.php` → `moduleConnections`; tanpa mapping, command-nya gagal. Riwayat migrasi (tabel `{{%migration}}`) dicatat di database milik modul itu sendiri.

```bash
# Jalankan migrasi dari src/Migration/Example di koneksi yang di-map (default)
./yii migrate:module example

# Jalankan migrasi dari src/Migration/Auditable (tabel audit_logs + rate_limits)
./yii migrate:module auditable

# Jalankan migrasi dari src/Migration/ServiceToken (tabel service_tokens)
./yii migrate:module serviceToken

# Override koneksi yang di-map untuk run ini (pakai key env db.<nama>.*)
./yii migrate:module auditable --db=audit

# Opsi
./yii migrate:module example -y      # skip konfirmasi
./yii migrate:module example -l 1    # batasi jumlah migrasi
```

Peta modul → koneksi ada di `config/common/migration.php`:

```php
'moduleConnections' => [
    'Example'   => 'default',
    'Auditable' => 'audit',   // pakai key env db.audit.*
],
```

Skeleton menyertakan tiga grup terisolasi:

| Folder | Namespace | Tabel | Wajib? |
|--------|-----------|-------|--------|
| `src/Migration/Example/` | `App\Migration\Example` | `example`, `another_example` | Hanya untuk modul demo |
| `src/Migration/ServiceToken/` | `App\Migration\ServiceToken` | `service_tokens` | Untuk fitur Service Token |
| `src/Migration/Auditable/` | `App\Migration\Auditable` | `audit_logs`, `rate_limits` | Opt-in, lihat [Audit Trail](#audit-trail) |

Migrasi modul baru hasil `skeleton-generate-module.php` ditempatkan di `src/Migration/<Modul>/` dan didaftarkan di `moduleConnections` otomatis (koneksi `default`, atau nilai `--db`).

#### 4. Konfigurasi Optimistic Lock

Skeleton menyertakan optimistic locking yang bisa dikonfigurasi untuk mencegah konflik update bersamaan:

```bash
# Enable/disable optimistic locking (global)
app.optimistic_lock.enabled=true    # Default: true

# Disable optimistic locking untuk validator tertentu (JSON array)
app.optimistic_lock.disabled.values=["example","example_1"]
```

**🔧 Fitur Optimistic Lock:**

- **✅ Manajemen Versi Otomatis** - Setiap entity punya field `lock_version`
- **✅ Pencegahan Update Bersamaan** - Throw exception saat versi tidak cocok
- **✅ Bisa Dikonfigurasi** - Bisa di-enable/disable global atau per validator
- **✅ Dioptimasi untuk Performa** - Skip verifikasi saat disabled
- **✅ Kontrol per-Validator** - Kontrol granular per tipe validator
- **✅ Normalisasi Cerdas** - Normalisasi nama validator otomatis

**📋 Opsi Konfigurasi:**

| Setting                               | Tipe       | Default | Deskripsi                                       |
|---------------------------------------|------------|---------|-------------------------------------------------|
| `app.optimistic_lock.enabled`         | boolean    | `true`  | Enable/disable optimistic locking secara global |
| `app.optimistic_lock.disabled.values` | JSON array | `[]`    | Daftar validator yang di-disable (dinormalisasi)|

**🚀 Contoh Pemakaian:**

```bash
# Disable global untuk testing entity
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=["example","test"]

# Enable untuk semua entity di production
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=[]

# Disable semua optimistic locking saat maintenance
app.optimistic_lock.enabled=false
```

**🔧 Normalisasi Nama Validator:**

Sistem otomatis menormalisasi nama validator untuk konfigurasi:

```php
// Class Validator → Nama Ternormalisasi → Key Environment
ExampleInputValidator → "example" → app.optimistic_lock.disabled.values=["example"]
UserInputValidator → "user" → app.optimistic_lock.disabled.values=["user"]
ProductInputValidator → "product" → app.optimistic_lock.disabled.values=["product"]
```

**🔧 Implementasi di Validator:**

Validasi optimistic lock otomatis terintegrasi ke validator:

```php
// Di class InputValidator kamu
final class ExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::UPDATE => [
                'id' => [new Required(), new Integer(min: 1)],
                'name' => [new StringValue(skipOnEmpty: true)],
                // Validasi unique sadar optimistic lock
                'name' => [
                    new Required(),
                    new StringValue(),
                    new UniqueValue(
                        targetClass: ExampleRepository::class,
                        targetAttribute: 'name',
                        filter: fn() => $this->getFilterForUnique(),
                        // Otomatis hormati konfigurasi optimistic lock
                        skipOnEmpty: fn() => !$this->isOptimisticLockEnabled()
                    ),
                ],
                // lock_version otomatis ditambah/dihapus sesuai konfigurasi
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
            // ... konteks lain
        };
    }
}
```

**🔧 Fitur Validasi Lanjutan:**

Sistem menyertakan validation rule lanjutan yang terintegrasi dengan optimistic locking:

```php
// Rule UniqueValue - Mencegah nama duplikat dengan dukungan optimistic lock
new UniqueValue(
    targetClass: ExampleRepository::class,
    targetAttribute: 'name',
    filter: fn() => $this->getFilterForUnique(),
    message: 'Name must be unique',
    skipOnEmpty: true
)

// Rule HasNoDependencies - Validasi entity tidak punya dependency sebelum delete
new HasNoDependencies(
    dependencyChecker: $this->dependencyChecker,
    errorMessage: 'Cannot delete entity with existing dependencies',
    skipOnEmpty: false
)
```

**🔧 Implementasi di Entity:**

Entity memakai trait `OptimisticLock` untuk manajemen versi otomatis:

```php
// Di class Entity kamu
use App\Domain\Shared\Core\Concerns\Entity\OptimisticLock;

final class Example extends Entity
{
    use OptimisticLock;

    // Manajemen lock_version otomatis
    // - verifyLockVersion() untuk validasi
    // - upgradeLockVersion() untuk increment
    // - getLockVersion() untuk versi saat ini
}
```

**📝 Contoh Konfigurasi:**

```bash
# Development: disable untuk entity test
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=["example","test"]

# Production: enable untuk semua entity
app.optimistic_lock.enabled=true
app.optimistic_lock.disabled.values=[]

# Maintenance: disable semua optimistic locking
app.optimistic_lock.enabled=false
```

**🔧 Pemakaian API:**

Saat optimistic locking aktif, sertakan `lock_version` di request UPDATE/DELETE:

```bash
# Update dengan optimistic lock
curl -X PUT http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Updated Name",
    "lock_version": 5
  }'

# Delete dengan optimistic lock
curl -X DELETE http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -d '{"lock_version": 5}'
```

Saat disabled untuk suatu validator, `lock_version` jadi opsional:

```bash
# Update tanpa lock_version (saat disabled)
curl -X PUT http://localhost:8080/v1/example/1 \
  -H "Content-Type: application/json" \
  -d '{"name": "Updated Name"}'
```

#### 5. File Pesan Translasi

File pesan di `resources/messages/{en,id}/` dibagi menjadi **milik skeleton** dan **milik project**:

| File | Pemilik | Catatan |
|------|---------|---------|
| `app.php` | **Project** | Tambahkan pesan custom di sini. Tidak pernah ditimpa `composer skeleton:update`. |
| `error.php` | Skeleton | Jangan edit atau tambah key — ditimpa `composer skeleton:update`. |
| `success.php` | Skeleton | Jangan edit atau tambah key — ditimpa `composer skeleton:update`. |
| `validation.php` | Skeleton | Jangan edit atau tambah key — ditimpa `composer skeleton:update`. |

Taruh pesan error, success, atau validasi khusus project di `app.php` untuk tiap locale:

```php
// resources/messages/en/app.php
return [
    'success' => 'Success',
    'validation.custom_rule' => 'The {field} is invalid.',
];
```

Pesan direferensikan di kode lewat `Message::create()`:

```php
use App\Shared\Core\ValueObject\Message;

throw new BadRequestException(
    translate: Message::create(
        domain: 'validation',          // file pesan: validation.php
        key: 'resource.not_deleted',
        params: ['resource' => 'example', 'id' => $id]
    )
);
```

> **Catatan:** `domain` merujuk ke file pesan — `error` → `error.php` (default),
> `success` → `success.php`, `validation` → `validation.php`, `app` → `app.php`.
> Key milik skeleton (misal `service_token.duplicate`) tinggal di `error.php`
> supaya ikut tersinkron ke project konsumen.

---

## 🎯 Workflow Development

### Quality Assurance

Skeleton menyertakan tool quality assurance lengkap:

```bash
# Jalankan suite quality check lengkap
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
# Jalankan semua test
vendor/bin/phpunit

# Jalankan test suite tertentu
vendor/bin/phpunit tests/Unit/
vendor/bin/phpunit tests/Api/
vendor/bin/phpunit tests/Functional/

# Jalankan test dengan coverage
vendor/bin/phpunit --coverage-html tests/coverage/html
```

### Static Analysis

```bash
# Jalankan static analysis Psalm
vendor/bin/psalm

# Clear cache lalu jalankan ulang
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
    use Identifiable, Stateful, OptimisticLock;

    public static function create(string $name, Status $status, DetailInfo $detailInfo): self
    {
        self::guardInitialStatus($status, null, self::RESOURCE);
        return new self(null, $name, $status, $detailInfo, null, LockVersion::create());
    }
}
```

### Application Layer

Application service mengoordinasikan use case:

```php
// src/Application/Example/ExampleApplicationService.php
final class ExampleApplicationService
{
    public function create(CreateExampleCommand $command): ExampleResponse
    {
        // Validasi business logic
        $this->domainService->ensureUnique(...);

        // Pembuatan entity
        $example = Example::create(...);

        // Persistensi
        return ExampleResponse::fromEntity($this->repository->insert($example));
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
        return $this->db->transaction(function() use ($example) {
            // Operasi database dengan sinkronisasi MongoDB
        });
    }
}
```

### API Layer

Controller menangani HTTP request:

```php
// src/Api/V1/Action/Example/ExampleCreateAction.php
final class ExampleCreateAction
{
    public function run(ServerRequestInterface $request): ResponseInterface
    {
        // Validasi request
        $command = new CreateExampleCommand(...);

        // Business logic
        $response = $this->applicationService->create($command);

        // Format response
        return $this->responseFactory->success($response->toArray());
    }
}
```

---

## 🔒 Fitur Security

### Authentication & Authorization

```php
// Autentikasi JWT
$app->addMiddleware(new AuthenticationMiddleware($jwtAuthenticator));

// Authorization RBAC
$app->addMiddleware(new AuthorizationMiddleware($rbacAuthorizer));
```

### Service Token — Bearer Opaque untuk Service Eksternal

Selain JWT, skeleton menyediakan **Service Token** — bearer token opaque long-lived untuk sistem yang tidak punya akun SSO (POS, script cron, integrasi pihak ketiga). `ServiceTokenAuthMiddleware` berjalan sebagai entry terpisah *sebelum* `JwtMiddleware` di middleware stack: token opaque dicocokkan ke tabel `service_tokens` (yang disimpan hanya hash SHA-256-nya — plaintext tampil sekali saat issuance/rotasi), sedangkan token berbentuk JWT tetap diproses `JwtMiddleware` seperti biasa.

Fitur ini **opt-in** lewat `app.service_token.enabled` di `.env` (default `false`) — saat off, middleware langsung skip ke `JwtMiddleware` tanpa query DB, jadi tidak ada overhead kalau tidak dipakai.

Token yang valid berjalan **atas nama user penerbit** (SSO-style): permission, audit, dan changelog `detail_info` semua tercatat sebagai user itu. Mendukung `whitelist_host` (exact / `*` / prefix `x.*` / suffix `*.x`), `expires_at`, dan revoke instan via soft delete. Detail lengkap: [`docs-guide/23-system_service-token.md`](docs-guide/23-system_service-token.md).

```bash
# Issue token baru (plaintext hanya muncul sekali di data.token)
curl -X POST http://localhost:8090/v1/system/service-token/create \
  -H "Content-Type: application/json" \
  -d '{"username":"pos-01","name":"POS 01","whitelist_host":["10.0.0.5"]}'

# Pakai sebagai bearer — berjalan atas nama user penerbit
curl -X POST http://localhost:8090/v1/system/service-token/data \
  -H "Authorization: Bearer <plaintext-token>" -d '{}'
```

### Audit Trail

Audit logging sifatnya **opt-in** — tabel `audit_logs` hanya dibuat saat kamu menjalankan grup migrasi terisolasi:

```bash
./yii migrate:module auditable   # membuat audit_logs + rate_limits
```

`AuditServiceInterface` sudah di-bind ke `DatabaseAuditService` di `config/common/di/audit.php`. Inject di mana pun kamu butuh logging:

```php
use App\Domain\Shared\Core\Audit\AuditServiceInterface;

final class ExampleApplicationService
{
    public function __construct(
        private AuditServiceInterface $audit,
    ) {}

    public function update(int $id, array $oldValues, array $newValues): void
    {
        // ... logika update ...

        $this->audit->log('example', $id, 'UPDATE', $oldValues, $newValues);
    }
}
```

```php
// Baca riwayat audit
$this->audit->getHistory('example', $recordId);        // riwayat per record
$this->audit->getUserActivity($userId, $from, $to);    // aktivitas per user
```

> **Catatan:** Trait `App\Infrastructure\Core\Concerns\Auditable` didesain untuk class bergaya Active Record (`beforeSave`/`afterSave`/`getIsNewRecord`). Project ini memakai Query Builder di repository, jadi gunakan `AuditServiceInterface` langsung.

### Database Rate Limiter (opt-in)

`RateLimitMiddleware` yang aktif memakai storage **in-memory** dan tidak butuh tabel. Limiter persistent berbasis DB (`App\Infrastructure\Core\RateLimit\DatabaseRateLimiter`) tersedia tapi belum di-wire di mana pun — dia memakai tabel `rate_limits` yang dibuat oleh `migrate:module auditable`:

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

Untuk menegakkannya lewat middleware, ubah `RateLimitMiddleware` agar memakai `DatabaseRateLimiter`, atau panggil limiter secara manual di action tertentu (misalnya login).

### Current Actor

`CurrentUser::getActor()` selalu mengembalikan `ActorInterface` — tidak pernah `null`. Konteks unauthenticated/system mendapat actor default (`id: 0`, `username: 'system'`). Audit logging dan `DetailInfoFactory` bergantung pada ini, jadi kamu bisa memanggil `$actor->getUsername()` langsung tanpa null-safe operator.

---

## 🔄 Sinkronisasi Data

Skeleton menyertakan value object dan factory untuk melacak sinkronisasi record — baik ke MongoDB maupun antar instance master/origin.

### Flag Sync MongoDB — `SyncMdb`

`App\Domain\Shared\Core\ValueObject\SyncMdb` membungkus kolom `sync_mdb` (`null` = sudah sync, `1` = pending):

```php
use App\Domain\Shared\Core\ValueObject\SyncMdb;

$sync = SyncMdb::pending();          // tandai record perlu sync MongoDB
$sync = SyncMdb::synced();           // tandai record sudah sync
$sync = SyncMdb::fromInt($row['sync_mdb']);
$sync = SyncMdb::fromString($input); // menerima input string, throw kalau non-numerik

$sync->isPending();  // true saat sync_mdb = 1
$sync->isSynced();   // true saat sync_mdb = null
$sync->toInt();      // null | 1 — untuk tulis ke DB
```

### Sync Master–Origin — `SyncFlag`

`App\Domain\Shared\Core\ValueObject\SyncFlag` mengelola kolom `origin_id` / `sync_flag` plus arah sync. `origin_id` integer (default `null`); `sync_flag` smallint (`null` = synced, `1` = not synced, default `1`). Status dan arah adalah enum bertipe di `App\Domain\Shared\Core\Enum`:

| Enum | Case | Nilai DB | Arti |
|------|------|----------|------|
| `SyncStatus` | `SYNCED` | `null` | Record sudah tersinkron |
| `SyncStatus` | `NOT_SYNCED` | `1` | Record perlu disinkronkan |
| `SyncDirection` | `NONE` | `0` | Tidak ada arah |
| `SyncDirection` | `MASTER_TO_ORIGIN` | `1` | Push dari master ke origin |
| `SyncDirection` | `ORIGIN_TO_MASTER` | `2` | Push dari origin ke master |
| `SyncDirection` | `BIDIRECTIONAL` | `3` | Sync dua arah |

```php
use App\Domain\Shared\Core\Enum\SyncStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;

// Arah auto-resolve kalau tidak diberikan:
//   sync_flag=null           -> SyncDirection::NONE
//   sync_flag=1, tanpa origin-> SyncDirection::MASTER_TO_ORIGIN
//   sync_flag=1, origin ada  -> SyncDirection::ORIGIN_TO_MASTER
$sync = SyncFlag::create(originId: null, status: SyncStatus::NOT_SYNCED);

$sync = SyncFlag::masterToOrigin();        // ke semua origin
$sync = SyncFlag::masterToOrigin(5);       // ke origin #5
$sync = SyncFlag::originToMaster(5);       // origin #5 -> master
$sync = SyncFlag::bidirectional(5);        // dua arah
$sync = SyncFlag::synced();

$sync = SyncFlag::fromArray($row);         // dari array request/DB
$sync = SyncFlag::fromEntity($entity);     // baca getOriginId()/getSyncFlag()/getSyncDirection()

$sync->needsSyncToOrigin();  // pending && arah master->origin
$sync->needsSyncToMaster();  // pending && arah origin->master
$sync->markForSync();        // instance baru dengan sync_flag=1
$sync->markSynced();         // instance baru dengan sync_flag=null
$sync->toArray();            // origin_id + sync_flag + direction
$sync->toDbArray();          // origin_id + sync_flag saja
```

Nilai `sync_flag` tidak valid (bukan `null`/`1`) atau arah tidak valid (bukan `0`–`3`) melempar `BadRequestException` dengan pesan tertranslasi (`sync_flag.invalid_value`, `sync_flag.invalid_direction`).

### `SyncFlagFactory`

`App\Application\Shared\Core\Factory\SyncFlagFactory` adalah helper application-layer yang membungkus `SyncFlag` dan menambah pembangunan payload sadar actor/timestamp:

```php
use App\Application\Shared\Core\Factory\SyncFlagFactory;

final class ExampleApplicationService
{
    public function __construct(
        private SyncFlagFactory $syncFlagFactory,
    ) {}

    public function create(CreateExampleCommand $command): void
    {
        // Bangun dari data request / entity / record
        $sync = $this->syncFlagFactory->fromRequest($command->data);

        if ($this->syncFlagFactory->shouldPushToOrigin($sync)) {
            // Payload queue berisi table, record_id, origin_id, direction,
            // operation, payload, created_at dan created_by (actor saat ini)
            $payload = $this->syncFlagFactory->buildMasterToOriginPayload(
                originId: $sync->getOriginId(),
                table: 'example',
                recordId: $id,
                operation: 'INSERT',
                data: $command->data,
            );
        }

        // Merge status sync ke detail_info
        $detailInfo = $this->syncFlagFactory->mergeIntoDetailInfo($sync, $detailInfo);

        // Entri log sync bergaya audit (timestamp + actor saat ini)
        $log = $this->syncFlagFactory->buildSyncLog($sync, 'example', $id, 'INSERT');
    }
}
```

---

## 🚀 Contoh Pemakaian API

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

### List Resource

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

## 🐳 Development dengan Docker

### Environment Development

```bash
# Jalankan container development
docker-compose -f docker/dev/compose.yml up -d

# Jalankan command di dalam container
docker-compose -f docker/dev/compose.yml exec app php yii migrate
docker-compose -f docker/dev/compose.yml exec app php quality
```

### Environment Production

```bash
# Build dan jalankan container production
docker-compose -f docker/prod/compose.yml up -d --build

# Lihat log
docker-compose -f docker/prod/compose.yml logs -f
```

---

## 📚 Dokumentasi

### Dokumentasi Tersedia

- **[Architecture Guide](02-architecture-guide.md)**: Gambaran arsitektur lengkap
- **[Quality Guide](21-quality-guide.md)**: Prosedur quality assurance
- **[Dokumentasi API](docs-guide/Yii3-API.postman_collection.json)**: Postman collection endpoint API
- **[Panduan Sync Flag](docs-guide/22-sync-flag-guide.md)**: Sinkronisasi record master–origin (`origin_id`/`sync_flag`)
- **[Panduan Service Token](docs-guide/23-system_service-token.md)**: Bearer token opaque untuk service eksternal
- **[Viewer Dokumentasi](docs-guide/documentation.html)**: Semua guide dalam satu file — buka langsung di browser

### Generate Dokumentasi

```bash
# Jalankan quality check dengan coverage
php quality quality:check --coverage

# Jalankan quality check dengan laporan detail
php quality quality:check --report

# Jalankan quality check dengan coverage dan laporan
php quality quality:check --coverage --report

# Perbaiki masalah code style otomatis
php quality quality:check --fix
```

---

## 🧪 Strategi Testing

### Tipe Test

1. **Unit Test**: Test class dan method individual
2. **Functional Test**: Test workflow aplikasi
3. **API Test**: Test endpoint API
4. **Integration Test**: Test database dan integrasi service eksternal

### Menjalankan Test

```bash
# Jalankan semua test lewat script quality
php quality test:run

# Hanya unit test
php quality test:run --unit

# Hanya integration test
php quality test:run --integration

# Test dengan coverage
php quality test:run --coverage

# Test tertentu dengan filter
php quality test:run --filter=ExampleTest

# Alternatif: Command PHPUnit langsung
vendor/bin/phpunit
vendor/bin/phpunit --coverage-html tests/coverage/html
vendor/bin/phpunit tests/Unit/Domain/Example/ExampleTest.php
```
---

## 🔧 Maintenance

### Tugas Rutin

#### Mingguan
- Update dependency: `composer update`
- Cek update skeleton: `composer skeleton:version`
- Jalankan quality check: `php quality`
- Review tren coverage test
- Cek security advisory

#### Bulanan
- Review dan update konfigurasi quality
- Update coding standard
- Tambah quality check baru
- Review optimasi performa

#### Kuartalan
- Update dependency mayor
- Review threshold quality gate
- Upgrade versi tool
- Meeting review arsitektur

### Troubleshooting

#### Masalah Umum

```bash
# Clear semua cache
vendor/bin/psalm --clear-cache

# Install ulang dependency
composer install --no-dev --optimize-autoloader
```

---

## 📞 Support & Resource

### Dokumentasi

- **[Dokumentasi Yii3](https://yiisoft.github.io/docs/guide/intro/what-is-yii.html)**: Panduan resmi Yii3
- **[Panduan Yii3 Validator](https://github.com/yiisoft/validator/blob/master/docs/guide/en/README.md)**: Panduan resmi Yii3 Validator
- **[Dokumentasi Psalm](https://psalm.dev/)**: Tool static analysis
- **[Dokumentasi PHPUnit](https://phpunit.de/)**: Framework testing

### Komunitas

- **[Yii3 API GitHub](https://github.com/yiisoft/app-api)**: Repository resmi
- **[Yii3 Discord](https://discord.gg/yiisoft)**: Chat komunitas

### Tool Kualitas

- **[PHP CS Fixer](https://cs.symfony.com/)**: Perbaikan code style
- **[Composer Audit](https://github.com/composer/composer/blob/main/src/Composer/Command/AuditCommand.php)**: Security audit
- **[Codeception](https://codeception.com/)**: Framework testing

---

## 🎯 Best Practice

### Kualitas Kode

- **Type Safety**: Selalu pakai strict types dan type annotation
- **Error Handling**: Implementasikan exception handling yang benar
- **Testing**: Jaga coverage test tinggi (>80%)
- **Dokumentasi**: Jaga dokumentasi tetap up-to-date

### Security

- **Input Validation**: Validasi semua input user
- **Authentication**: Pakai JWT token untuk autentikasi API (atau Service Token untuk service eksternal)
- **Authorization**: Implementasikan RBAC untuk access control
- **Audit Trail**: Log semua operasi penting

### Performa

- **Optimasi Database**: Pakai index dan optimasi query yang tepat
- **Caching**: Implementasikan strategi caching multi-level
- **Async Processing**: Pakai queue untuk operasi lama
- **Monitoring**: Monitor performa aplikasi

---

## 🎉 Penutup

Yii3 API Skeleton menyediakan fondasi yang solid untuk membangun RESTful API modern, scalable, dan maintainable dengan prinsip Domain-Driven Design. Tool quality assurance, dokumentasi komprehensif, dan pattern clean architecture yang disertakan memastikan development API-mu mengikuti best practice sejak hari pertama.

Manfaat utama:

- **🏗️ Clean Architecture**: Prinsip DDD untuk kode yang maintainable
- **🔒 Type Safety**: Static analysis penuh dengan Psalm
- **🧪 Testing Ready**: Setup test suite lengkap
- **📊 Quality Assurance**: Quality check otomatis
- **🐳 Docker Ready**: Dukungan containerization
- **📚 Dokumentasi Lengkap**: Dokumentasi komprehensif tersedia

Mulai bangun project API-mu berikutnya dengan percaya diri memakai Yii3 API Skeleton! 🚀
