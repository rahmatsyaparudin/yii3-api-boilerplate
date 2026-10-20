# Panduan Quality Assurance

## 📋 Gambaran Umum

Script `quality` (aplikasi Symfony Console kecil di root project) adalah tool quality assurance yang komprehensif dan dirancang untuk project Yii3 API. Tool ini menyediakan pemeriksaan otomatis untuk code style, static analysis, testing, dan security audit guna memastikan kualitas kode yang tinggi dan kemudahan pemeliharaan.

## 🚀 Quick Start

### Menjalankan Semua Quality Check
```bash
# Jalankan seluruh rangkaian quality check
php quality quality:check

# Jalankan dengan opsi tertentu
php quality quality:check --fix          # Perbaiki otomatis masalah code style
php quality quality:check --coverage      # Generate laporan test coverage
php quality quality:check --report        # Generate laporan analisis detail
```

### Menjalankan Test
```bash
# Jalankan semua suite Codeception
php quality test:run

# Jalankan jenis test tertentu
php quality test:run --unit                    # Jalankan hanya suite Unit
php quality test:run --integration             # Jalankan suite Functional
php quality test:run --coverage                # Generate laporan coverage
php quality test:run --filter=testSomething    # Filter test berdasarkan nama
```

### Command yang Tersedia
```bash
# Daftar semua command yang tersedia
php quality list

# Bantuan untuk command tertentu
php quality quality:check --help
php quality test:run --help
```

### Pemeriksaan Individual
```bash
# Pemeriksaan code style
vendor/bin/php-cs-fixer check --diff --verbose --allow-risky=yes

# Static analysis
vendor/bin/psalm

# Test (Codeception; suite Unit yang dijalankan quality:check)
vendor/bin/codecept run Unit

# Security audit
composer audit
```

Di Windows, awali command `vendor/bin/*` dengan `php` (mis. `php vendor\bin\psalm`).

## 🔧 Konfigurasi

### Opsi yang Tersedia

Opsi `quality:check`:

| Opsi | Singkat | Deskripsi |
|--------|-------|-------------|
| `--fix` | `-f` | Perbaiki otomatis masalah code style |
| `--coverage` | `-c` | Generate laporan test coverage |
| `--report` | `-r` | Generate laporan analisis detail |

Opsi `test:run`:

| Opsi | Singkat | Deskripsi |
|--------|-------|-------------|
| `--unit` | `-u` | Jalankan hanya suite Unit |
| `--integration` | `-i` | Jalankan hanya suite Functional |
| `--coverage` | `-c` | Generate laporan coverage |
| `--filter` | — | Filter test berdasarkan nama |

### Apa yang Dijalankan `quality:check`

1. `vendor/bin/php-cs-fixer check|fix --diff --verbose --allow-risky=yes`
2. `vendor/bin/psalm`
3. `vendor/bin/codecept run Unit` (dengan `APP_ENV=test`; menambahkan `--coverage-html`/`--coverage-text` dengan `--coverage`, plus `--coverage-xml` dengan `--report`)
4. `composer audit`

## 📊 Quality Check

### 1. Pemeriksaan Code Style (PHP CS Fixer)

**Tujuan**: Memastikan format kode dan kepatuhan gaya yang konsisten.

**Apa yang diperiksa**:
- Standar coding PSR-12
- Konvensi Yii3 framework
- Pemformatan kode (spasi, line ending, dll.)
- Organisasi import
- Urutan method/property

**Konfigurasi**: `.php-cs-fixer.php`

```php
// Rule sets enabled in .php-cs-fixer.php
'@PER-CS2x0' => true,
'@PSR12' => true,
'@Symfony' => true,
'@PHP8x0Migration' => true,
'@PHP8x0Migration:risky' => true,
'@PHP8x1Migration' => true,
'strict_comparison' => true,
'declare_strict_types' => true,
'no_unused_imports' => true,
// ...see .php-cs-fixer.php for the full list
```

Finder memindai `config/`, `src/`, `tests/` plus `public/index.php`; file cache-nya adalah `runtime/cache/.php-cs-fixer.cache`.

### 2. Static Analysis (Psalm)

**Tujuan**: Pemeriksaan tipe dan deteksi error sebelum runtime.

**Apa yang diperiksa**:
- Type safety dan kebenaran tipe
- Pelanggaran null safety
- Kepatuhan interface
- Validasi signature method
- Potensi runtime error

**Konfigurasi**: `psalm.xml`

```xml
<psalm
  errorLevel="1"
  errorBaseline="psalm-baseline.xml"
  findUnusedBaselineEntry="true"
  findUnusedCode="false"
  cacheDirectory="/app/runtime/cache/psalm"
>
  <projectFiles>
    <directory name="src" />
    <file name="public/index.php"/>
    <file name="yii"/>
  </projectFiles>
</psalm>
```

Baseline berisi issue yang sudah diketahui disimpan di `psalm-baseline.xml`; sejumlah issue bertipe mixed disuppress via `<issueHandlers>` di `psalm.xml`.

### 3. Unit Test (Codeception)

**Tujuan**: Memvalidasi fungsionalitas aplikasi dan mencegah regresi.

`quality:check` menjalankan `vendor/bin/codecept run Unit` dengan `APP_ENV=test`. Setup test lengkap didefinisikan di `codeception.yml` (namespace `App\Tests`) dengan suite di bawah `tests/`: `Unit`, `Functional`, `Api`, `Console`.

**Apa yang diuji**:
- Logika aplikasi
- Operasi repository
- Fungsionalitas service layer
- Endpoint API
- Logika domain

**Laporan Coverage**:
- Laporan coverage HTML: `tests/coverage/html/`
- Ringkasan coverage teks: `tests/coverage/coverage.txt`
- Clover XML: `tests/coverage/clover.xml`

### 4. Security Audit (Composer)

**Tujuan**: Mengidentifikasi kerentanan keamanan pada dependency.

**Apa yang diperiksa**:
- Kerentanan CVE yang diketahui
- Dependency yang usang
- Security advisory
- Kepatuhan lisensi

### Tool QA Tambahan

Tidak termasuk dalam `quality:check`, tetapi sudah dikonfigurasi di project (juga tersedia sebagai target `make` saat menggunakan Docker):

```bash
# Rector — refactoring otomatis (rector.php: src/ + tests/, PHP 8.2 sets)
vendor/bin/rector --dry-run  # pratinjau perubahan
vendor/bin/rector            # terapkan perubahan

# Infection — mutation testing (infection.json.dist)
vendor/bin/infection

# Composer Dependency Analyser (composer-dependency-analyser.php)
vendor/bin/composer-dependency-analyser --config=composer-dependency-analyser.php
```

## 📈 Laporan dan Output

### Format Output Standar

```
Running Quality Assurance Checks...

1. Checking code style...
Running: php vendor\bin\php-cs-fixer check --diff --verbose --allow-risky=yes
...

2. Running static analysis...
Running: php vendor\bin\psalm
...

3. Running unit tests...
Running: php vendor\bin\codecept run Unit
...

4. Running security audit...
Running: composer audit
...

✅ All quality checks passed!
```

(Atau `❌ Some quality checks failed!` beserta exit code non-zero ketika ada langkah yang gagal.)

### Laporan Detail

#### Laporan Coverage (`--coverage`)
```bash
php quality quality:check --coverage
```
Menghasilkan:
- **Laporan HTML**: `tests/coverage/html/` — visualisasi coverage interaktif
- **Ringkasan Teks**: `tests/coverage/coverage.txt` — statistik coverage

#### Laporan Analisis (`--report`)
```bash
php quality quality:check --report
```
Menghasilkan:
- **Clover XML**: `tests/coverage/clover.xml` — data integrasi CI/CD

## 🛠️ Penanganan Error

### Masalah Umum dan Solusinya

#### Error Code Style
```bash
# Perbaiki otomatis sebagian besar masalah style
php quality quality:check --fix

# Perbaikan manual untuk masalah yang kompleks
vendor/bin/php-cs-fixer fix --diff --verbose --allow-risky=yes
```

#### Error Psalm
```bash
# Bersihkan cache dan jalankan ulang
vendor/bin/psalm --clear-cache
vendor/bin/psalm

# Periksa file tertentu
vendor/bin/psalm src/Infrastructure/Common/Persistence/Example/ExampleRepository.php
```

#### Kegagalan Test
```bash
# Jalankan suite atau test tertentu
vendor/bin/codecept run Unit
vendor/bin/codecept run Unit --filter=ExampleTest

# Jalankan dengan coverage
vendor/bin/codecept run --coverage-html
```

#### Masalah Security
```bash
# Update dependency
composer update

# Periksa kerentanan
composer audit
```

## 🏗️ Dampak pada Struktur Project

### Quality Gate

Script quality menegakkan quality gate untuk:

#### Kualitas Kode
- **Type Safety**: Semua kode harus lulus analisis Psalm
- **Kepatuhan Style**: Kode harus mengikuti standar PSR-12
- **Test Coverage**: Persyaratan coverage minimum

#### Security
- **Keamanan Dependency**: Tidak ada kerentanan yang diketahui
- **Kepatuhan Lisensi**: Hanya lisensi yang kompatibel

#### Performa
- **Performa Test**: Test harus berjalan dalam batas waktu
- **Penggunaan Memori**: Tidak ada memory leak di test

### Integrasi CI/CD

#### Contoh GitHub Actions
```yaml
name: Quality Check
on: [push, pull_request]
jobs:
  quality:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - uses: shivammathur/setup-php@v2
      - run: composer install
      - run: php quality quality:check --report
      - name: Upload coverage reports
        uses: actions/upload-artifact@v3
        with:
          name: coverage-reports
          path: tests/coverage/
```

## 📚 Best Practices

### Alur Kerja Development

#### 1. Sebelum Commit
```bash
# Selalu jalankan quality check sebelum commit
php quality quality:check
```

#### 2. Pengembangan Fitur
```bash
# Jalankan pemeriksaan tertentu selama development
vendor/bin/psalm --no-cache
vendor/bin/php-cs-fixer check
```

#### 3. Sebelum Release
```bash
# Rangkaian quality lengkap dengan laporan
php quality quality:check --coverage --report
```

### Standar Kualitas Kode

#### Type Safety
```php
// ✅ Use strict types
declare(strict_types=1);

// ✅ Type annotations
public function process(array $data): array<string, mixed>
{
    return array_map(fn($item) => (string) $item, $data);
}

// ✅ Null safety
public function find(int $id): ?Example
{
    return $this->repository->findById($id);
}
```

#### Error Handling
```php
// ✅ Proper exception handling
try {
    $result = $this->service->process($data);
} catch (ValidationException $e) {
    $this->logger->error('Validation failed', ['error' => $e->getMessage()]);
    throw $e;
}
```

#### Dokumentasi
```php
/**
 * Example service for processing data
 *
 * @param array<string, mixed> $data Input data to process
 * @return array<string, mixed> Processed data
 * @throws ValidationException When data is invalid
 */
public function process(array $data): array<string, mixed>
{
    // Implementation
}
```

## 🔧 Kustomisasi

### Menambahkan Pemeriksaan Baru

#### 1. Rule Psalm Kustom
```xml
<!-- psalm.xml -->
<issueHandlers>
    <CustomRule errorLevel="error" />
</issueHandlers>
```

#### 2. Test Codeception Tambahan
```php
// tests/Unit/CustomTest.php
final class CustomTest extends \Codeception\Test\Unit
{
    public function testCustomLogic(): void
    {
        $this->assertTrue(true);
    }
}
```

#### 3. Security Scanner
```bash
# Tambahkan tool security tambahan
composer require --dev enshrined/security-scanner
```

### File Konfigurasi

#### `.php-cs-fixer.php`
```php
<?php
$finder = (new PhpCsFixer\Finder())
    ->in([__DIR__ . '/config', __DIR__ . '/src', __DIR__ . '/tests'])
    ->append([__DIR__ . '/public/index.php']);

return (new PhpCsFixer\Config())
    ->setCacheFile(__DIR__ . '/runtime/cache/.php-cs-fixer.cache')
    ->setRules([
        '@PER-CS2x0' => true,
        '@PSR12' => true,
        '@Symfony' => true,
    ])
    ->setFinder($finder);
```

#### `psalm.xml`
```xml
<?xml version="1.0"?>
<psalm errorLevel="1" errorBaseline="psalm-baseline.xml" findUnusedBaselineEntry="true">
    <projectFiles>
        <directory name="src"/>
        <file name="public/index.php"/>
        <file name="yii"/>
    </projectFiles>
    <issueHandlers>
        <MixedAssignment errorLevel="suppress"/>
        <InvalidArrayOffset errorLevel="suppress"/>
    </issueHandlers>
</psalm>
```

## 🎯 Metrik Kualitas

### Target Metrik

| Metrik | Target | Cara memeriksa |
|--------|---------|--------|
| Psalm Errors | 0 | `vendor/bin/psalm` |
| Test Coverage | setinggi yang praktis | `php quality quality:check --coverage` |
| Security Issues | 0 | `composer audit` |
| Style Issues | 0 | `vendor/bin/php-cs-fixer check` |

### Memantau Tren Kualitas

```bash
# Lacak dari waktu ke waktu
php quality quality:check --report > quality-report-$(date +%Y-%m-%d).txt
```

## 🚨 Troubleshooting

### Masalah Umum

#### Masalah Memori
```bash
# Naikkan PHP memory limit
php -d memory_limit=1G quality quality:check
```

#### Masalah Performa
```bash
# Jalankan pemeriksaan secara paralel
vendor/bin/psalm --threads=4
```

#### Masalah Cache
```bash
# Bersihkan cache tool
rm -rf runtime/cache/*
vendor/bin/psalm --clear-cache
composer install
```

### Mendapatkan Bantuan

```bash
# Tampilkan command dan opsi yang tersedia
php quality list
php quality quality:check --help

# Periksa versi
php --version
composer show yiisoft/yii-console
composer show vimeo/psalm
```

## 📞 Sumber Daya

### Dokumentasi
- [Psalm Documentation](https://psalm.dev/)
- [PHP CS Fixer](https://cs.symfony.com/)
- [Codeception](https://codeception.com/) (test runner; PHPUnit di baliknya)
- [Rector](https://github.com/rectorphp/rector), [Infection](https://infection.github.io/), [Composer Dependency Analyser](https://github.com/shipmonk-rnd/composer-dependency-analyser)
- [Composer Audit](https://github.com/composer/composer/blob/main/src/Composer/Command/AuditCommand.php)

### Khusus Yii3
- [Yii3 Documentation](https://www.yiiframework.com/doc/guide/)
- [Yii3 Best Practices](https://www.yiiframework.com/doc/guide/)

### Standar Kualitas
- [PSR-12](https://www.php-fig.org/psr/psr-12/)
- [PHP Standards](https://www.php-fig.org/standards/)
- [Clean Code](https://clean-code-developer.com/)

---

## 🎉 Kesimpulan

Script quality adalah tool esensial untuk menjaga kualitas kode, keamanan, dan reliabilitas di project Yii3 API. Penggunaan rutin memastikan:

- **Kualitas Kode yang Konsisten**: Semua kode mengikuti standar yang ditetapkan
- **Type Safety**: Error tertangkap sebelum runtime
- **Security**: Dependency diaudit secara berkala
- **Test Coverage**: Kode diuji secara menyeluruh
- **Maintainability**: Kode tetap bersih dan terorganisir

Jadikan quality check sebagai bagian rutin dari workflow development Anda demi codebase yang robust dan mudah dipelihara! 🚀
