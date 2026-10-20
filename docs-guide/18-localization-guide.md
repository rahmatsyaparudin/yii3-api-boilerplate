# Panduan Lokalisasi dan Internasionalisasi

## 📋 Gambaran Umum

Panduan ini membahas sistem lokalisasi dan internasionalisasi (i18n) pada aplikasi Yii3 API. Sistem ini menggunakan `yiisoft/translator` dengan message source berbasis array PHP, mendukung multi-bahasa, dan menyediakan file pesan terstruktur untuk berbagai jenis pesan aplikasi.

---

## 🏗️ Struktur Lokalisasi

### Struktur Direktori

```
resources/messages/
├── en/                    # English messages
│   ├── app.php            # Project-owned application messages
│   ├── error.php          # Error messages (skeleton-managed)
│   ├── success.php        # Success messages (skeleton-managed)
│   └── validation.php     # Validation messages (skeleton-managed)
└── id/                    # Indonesian messages
    ├── app.php            # Project-owned application messages
    ├── error.php          # Error messages (skeleton-managed)
    ├── success.php        # Success messages (skeleton-managed)
    └── validation.php     # Validation messages (skeleton-managed)
```

> ⚠️ `error.php`, `success.php`, dan `validation.php` **dihasilkan oleh skeleton** dan akan ditimpa oleh `composer skeleton:update`. Tambahkan pesan khusus proyek hanya ke `app.php`.

### Bahasa yang Didukung

- **Inggris (en)**: Bahasa default, digunakan untuk pengembangan dan pengguna internasional
- **Indonesia (id)**: Bahasa lokal untuk pengguna Indonesia

### Kategori Terjemahan

Setiap nama file adalah kategori yang didaftarkan melalui `CategorySource`:

| Kategori | File | Tujuan |
|---|---|---|
| `app` | `app.php` | Pesan aplikasi milik proyek |
| `error` | `error.php` | Pesan error/exception |
| `success` | `success.php` | Pesan sukses |
| `validation` | `validation.php` | Pesan aturan validasi |

---

## 📁 File Pesan

### 1. Pesan Aplikasi (app.php)

**Tujuan**: Pesan milik proyek — satu-satunya file yang boleh Anda edit.

#### Versi Inggris (`resources/messages/en/app.php`)
```php
<?php

declare(strict_types=1);

/**
 * Application messages (English)
 *
 * THIS IS A PROJECT-OWNED FILE. Add custom messages here.
 * Messages in `error.php`, `success.php`, and `validation.php` are managed by
 * the skeleton and will be overwritten by `composer skeleton:update`.
 */
return [
    /*
     * Add your application-specific error, success, or validation messages here.
     * Example: 'validation.custom_rule' => 'The {field} is invalid.',
     */

    'success' => 'Success',
];
```

**Contoh Penggunaan**:
```php
// In an action or service — TranslatorInterface is injectable
$successMessage = $this->translator->translate('success', [], 'app');
```

---

### 2. Pesan Error (error.php)

**Tujuan**: Pesan error sistem dan penanganan exception. Key datar dengan awalan titik (flat, dot-prefixed).

#### Versi Inggris (`resources/messages/en/error.php`) — key aktual meliputi:

```php
return [
    // HTTP Prefix (Generic API Errors)
    'http.bad_request' => 'The request is invalid or malformed',
    'http.unauthorized' => 'Authentication is required or has failed',
    'http.forbidden' => 'You do not have permission to access this resource',
    'http.not_found' => 'The requested resource was not found',
    'http.method_not_allowed' => 'The HTTP method is not allowed for this endpoint',
    'http.unsupported_media_type' => 'The requested media type is not supported',
    'http.too_many_requests' => 'Too many requests. Please try again later',
    'http.internal_error' => 'An internal server error occurred',
    'http.service_unavailable' => 'The service is temporarily unavailable',
    'http.missing_request_params' => 'Missing request parameters.',

    // Security Prefix
    'security.host_not_allowed' => 'Access denied: Host not allowed',

    // Auth Prefix (Infrastructure Level)
    'auth.header_missing' => 'Authorization header is missing',
    'auth.invalid_token' => 'Access token is invalid or has expired',
    'auth.invalid_issuer' => 'The token issuer is invalid',
    'auth.invalid_audience' => 'The token audience is invalid',
    'auth.missing_claim' => 'The token is missing a required claim: {claim}',
    'auth.invalid_secret' => 'The token secret is invalid',

    // Request Prefix
    'request.invalid_json' => 'The request body contains invalid JSON',
    'request.invalid_body' => 'The request body is invalid or malformed',
    'request.missing_parameter' => 'Required parameter is missing: {param}',
    'request.invalid_parameter' => 'The parameter value is invalid: {param}',
    'request.host_not_allowed' => 'The host is not allowed: {host}',
    'request.origin_not_allowed' => 'The origin is not allowed: {origin}',

    // Filtering / Sorting / Pagination / Route
    'filter.invalid_keys' => 'The request contains unsupported filter field(s): {keys}',
    'filter.not_allowed' => 'Filtering by the field "{filter}" is not allowed',
    'sort.invalid_field' => 'The specified sort field "{field}" is invalid or not allowed',
    'sort.invalid_direction' => 'The sort direction "{direction}" is invalid. Use "asc" or "desc"',
    'pagination.invalid_page' => 'The page number must be a valid positive integer',
    'pagination.invalid_limit' => 'The page size value is invalid',
    'pagination.invalid_page_size' => 'The page size value is invalid',
    'pagination.invalid_parameter' => 'Invalid "{parameter}" parameter',
    'route.field_required' => 'Invalid request. {resource} {field} is required in the URL',
    'route.parameter_missing' => '{resource} {parameter} parameter is required in the URL',

    // Validation
    'validation.failed' => 'Validation failed. Please review the provided data',
    'validation.invalid_payload' => 'The {resource} payload is invalid',

    // Data type / Format / Range & Length / File
    'type.string' => 'The {field} field must be a string',
    'type.integer' => 'The {field} field must be an integer',
    'type.numeric' => 'The {field} field must be a numeric value',
    'type.boolean' => 'The {field} field must be a boolean value',
    'type.array' => 'The {field} field must be an array',
    'type.object' => 'The {field} field must be an object',
    'format.email' => 'The {field} field must be a valid email address',
    'format.url' => 'The {field} field must be a valid URL',
    'format.uuid' => 'The {field} field must be a valid UUID',
    'format.date' => 'The {field} field must be a valid date',
    'format.datetime' => 'The {field} field must be a valid date and time',
    'format.json' => 'The {field} field must contain valid JSON',
    'range.min' => 'The {field} field must be greater than or equal to {min}',
    'range.max' => 'The {field} field must be less than or equal to {max}',
    'length.min' => 'The {field} field must contain at least {min} characters',
    'length.max' => 'The {field} field must not exceed {max} characters',
    'file.invalid' => 'The uploaded file is invalid',
    'file.too_large' => 'The uploaded file exceeds the maximum allowed size',
    'file.invalid_type' => 'The uploaded file type is not allowed',

    // Resource (Business Logic)
    'resource.not_found' => '{resource} data with {field}: {value} was not found',
    'resource.conflict' => 'A conflict occurred with the {resource}',
    'resource.already_exists' => 'The {resource} already exists',
    'resource.cannot_update' => 'Cannot update {resource}. Status change from "{current_status}" to "{status}" is not allowed',
    'resource.update_not_allowed_by_status' => 'Data changes are not allowed for {resource} when its status is "{current_status}"',
    'resource.status_already_set' => 'Cannot update {resource}. The status is already "{current_status}"',
    'resource.modification_denied_on_deleted' => 'Action prohibited: The requested operation cannot be performed because the {resource} is marked as "{status}".',
    'resource.not_deleted' => 'Cannot restore data with id: {id} because it is not deleted',

    // Access & Rate Limit
    'operation.not_allowed' => 'This operation is not allowed',
    'access.denied' => 'Access to this resource is denied',
    'access.insufficient_permissions' => 'You do not have sufficient permissions to perform this action',
    'access.auth_required' => 'Authentication is required to access this resource',
    'rate_limit.exceeded' => 'Too many requests. Please try again after {seconds} seconds',
    'rate_limit.try_again' => 'Please try again in {seconds} seconds',

    'business.violation' => 'Business rule violation: {reason}',
    'business.limit_reached' => 'Limit {resource} reached',
    'business.requirement_unmet' => 'Requirement not met: {reason}',

    'service.error' => 'Failed to process request: {reason}',
    'service.unavailable' => 'Service "{service}" is unavailable',
    'service.failed' => 'The service process failed',

    'factory.detail_info.uninitialized_state' => 'Internal error: The factory state is uninitialized. You must call {methods} before performing this operation.',

    // Optimistic Locking
    'optimistic.lock.failed' => '{resource} data has been modified by another user. Please refresh and try again.',
    'lock_version.invalid_negative' => 'Lock version cannot be negative: {value}',

    // Seeder
    'seeder.missing_entity_class' => 'ENTITY_CLASS constant must be defined in {class}',
    'seeder.missing_yaml_file' => 'YAML_FILE constant must be defined in {class}',
];
```

#### Versi Indonesia (`resources/messages/id/error.php`)

Mencerminkan file Inggris key-per-key dengan teks Bahasa Indonesia, mis.:

```php
return [
    'http.bad_request' => 'Permintaan tidak valid atau formatnya salah',
    'http.not_found' => 'Sumber daya yang diminta tidak ditemukan',
    'auth.header_missing' => 'Header otorisasi tidak ditemukan',
    'auth.missing_claim' => 'Token tidak memiliki klaim yang diperlukan: {claim}',
    // ... same flat dot-prefixed keys as en/error.php
];
```

**Contoh Penggunaan**:
```php
// Via TranslatorInterface
$message = $this->translator->translate('http.not_found', [], 'error');
// en: "The requested resource was not found"
// id: "Sumber daya yang diminta tidak ditemukan"

$message = $this->translator->translate('auth.missing_claim', ['claim' => 'user.username'], 'error');
// en: "The token is missing a required claim: user.username"
```

---

### 3. Pesan Sukses (success.php)

**Tujuan**: Pesan sukses dan umpan balik positif.

#### Versi Inggris (`resources/messages/en/success.php`) — key aktual meliputi:

```php
return [
    // General Success
    'success' => 'Success',
    'ok' => 'OK',
    'completed' => '{resource} completed successfully',
    'processed' => '{resource} processed successfully',

    // Global CRUD Operations
    'resource.created' => '{resource} has been created successfully.',
    'resource.updated' => '{resource} has been updated successfully.',
    'resource.deleted' => '{resource} has been deleted successfully.',
    'resource.restored' => '{resource} has been restored successfully.',
    'resource.list_retrieved' => '{resource} list retrieved successfully.',
    'resource.details_retrieved' => '{resource} details retrieved successfully.',
    'resource.no_changes_detected' => 'No changes detected for {resource}. The submitted data is identical to the current record.',

    // Specific Operations (non-CRUD)
    'user.activated' => 'User {name} has been activated successfully',
    'user.deactivated' => 'User {name} has been deactivated successfully',
    'user.password_changed' => 'Password has been changed successfully',
    'user.profile_updated' => 'Profile has been updated successfully',

    // Authentication
    'auth.login_success' => 'Login successful',
    'auth.logout_success' => 'Logout successful',
    'auth.password_reset_sent' => 'Password reset link has been sent to your email',
    'auth.password_reset_success' => 'Your password has been reset successfully',
    'auth.email_verified' => 'Email address has been verified successfully',
    'auth.account_created' => 'Your account has been created successfully',

    // Data / File / Settings / System / Notification / Report / Bulk / API groups also exist
    'data.imported' => 'Data has been imported successfully',
    'file.uploaded' => 'File has been uploaded successfully',
    'settings.updated' => 'Settings have been updated successfully',
    'system.cache_cleared' => 'Cache has been cleared successfully',
    'notification.sent' => 'Notification has been sent successfully',
    'report.generated' => 'Report has been generated successfully',
    'bulk.created' => '{resource} have been created successfully',
    'api.request_processed' => 'API request has been processed successfully',
];
```

**Contoh Penggunaan**:
```php
$message = $this->translator->translate('resource.created', ['resource' => 'Example'], 'success');
// en: "Example has been created successfully."
// id: "Example telah berhasil dibuat."

$message = $this->translator->translate('auth.login_success', [], 'success');
// en: "Login successful"
// id: "Login berhasil"
```

---

### 4. Pesan Validasi (validation.php)

**Tujuan**: Pesan validasi form dan validasi data yang digunakan oleh validator di `src/Shared/Core/Validation/`.

#### Versi Inggris (`resources/messages/en/validation.php`) — kelompok key aktual:

```php
return [
    // Required
    'required' => 'The {resource} field is required',
    'required.if' => 'The {resource} field is required when {other} is {value}',
    'required.unless' => 'The {resource} field is required unless {other} is {value}',
    'required.with' => 'The {resource} field is required when {other} is present',
    'required.without' => 'The {resource} field is required when {other} is not present',
    'filled' => 'The {resource} field must have a value',
    'present' => 'The {resource} field must be present',
    'not_found' => 'The {resource} data was not found',
    'entity_required' => 'The {resource} entity is required',
    'name_required' => 'The {resource} name is required',

    // String / Number prefixes
    'string.min' => 'The {field} field must be at least {min} characters',
    'string.max' => 'The {field} field must not exceed {max} characters',
    'string.between' => 'The {field} field must be between {min} and {max} characters',
    'number.integer' => 'The {field} field must be an integer',
    'number.min' => 'The {field} field must be at least {min}',
    'number.max' => 'The {field} field must not exceed {max}',

    // Format
    'format.email' => 'The {field} field must be a valid email address',
    'format.url' => 'The {field} field must be a valid URL',
    'format.uuid' => 'The {field} field must be a valid UUID',
    'format.regex' => 'The {field} field format is invalid',

    // Date & Time
    'date.invalid' => 'The {field} field must be a valid date',
    'date.before' => 'The {field} field must be a date before {date}',
    'date.after' => 'The {field} field must be a date after {date}',

    // File / Image
    'file.invalid' => 'The {resource} must be a valid file',
    'file.mimes' => 'The {resource} must be a file of type: {types}',
    'image.invalid' => 'The {resource} must be an image',
    'image.dimensions' => 'The {resource} has invalid image dimensions',

    // Comparison / Boolean / Array
    'compare.same' => 'The {resource} must match {other}',
    'compare.confirmed' => 'The {resource} confirmation does not match',
    'boolean.invalid' => 'The {resource} field must be true or false',
    'array.min' => 'The {resource} must have at least {min} items',
    'array.distinct' => 'The {resource} field has duplicate values',

    // Permission / Existence
    'action_not_allowed' => 'You do not have permission to {action} this {resource}',
    'exists.invalid' => 'The selected {resource} does not exist',
    'exists.unique' => 'The {resource} has already been taken',
    'exists.already_exists' => 'The {resource} with value "{value}" already exists',
    'exists.cannot_delete_active' => 'Cannot delete {resource} while it is still active',

    // Special
    'special.nullable' => 'The {resource} field may be null',
    'special.prohibited' => 'The {resource} field is prohibited',

    // Status
    'status.forbid_update' => '{resource} data with status "{status}" cannot be updated',
    'status.invalid_transition' => 'Cannot update {resource} from status "{from}" to "{to}"',
    'status.cannot_delete' => 'Cannot delete {resource} with status "{status}"',

    // Restore
    'resource.restored' => '{resource} has been restored successfully',
    'resource.restore_failed' => 'Failed to restore {resource}: {error}',
    'resource.not_deleted' => '{resource} data is not in "Deleted" status',

    // Sync (note the post-rename keys: sync_flag, NOT sync_slave)
    'sync_mdb.invalid_value' => 'Invalid sync_mdb value "{value}". Allowed values: {allowed_values}',
    'sync_mdb.invalid_format' => 'Invalid sync_mdb format "{value}". Expected a numeric value or null',
    'sync_flag.invalid_value' => 'Invalid sync_flag value "{value}". Allowed values: {allowed_values}',
    'sync_flag.invalid_direction' => 'Invalid sync direction "{value}". Allowed values: {allowed_values}',

    // Request / Input Sanitizer
    'request.unknown_parameters' => 'Unknown parameters: {unknown_keys}. Allowed parameters: {allowed_keys}',
    'input_sanitizer.input_structure_too_deep' => 'Input structure is too deep. Current depth is {depth}, but the maximum allowed is {max_depth}.',
    'input_sanitizer.input_too_long' => 'Input is too long. Current length is {length}, but the maximum allowed is {max_length}.',
    'input_sanitizer.invalid_encoding' => 'Input contains invalid encoding. Current encoding is {encoding}.',
    'input_sanitizer.array_too_large' => 'The array exceeds the maximum allowed size of {max_size} elements.',
    'input_sanitizer.float_overflow' => 'Value is out of range. It must be between {min_value} and {max_value}.',
    'input_sanitizer.integer_overflow' => 'Value is out of range. It must be between {min_value} and {max_value}.',
    'input_sanitizer.invalid_array_key_type' => 'The array key {key} has an invalid type. Keys must be strings or integers.',
    'input_sanitizer.invalid_array_key_length' => 'The array key is too long. Maximum allowed length for a key is {max_length} characters,',
    'input_sanitizer.invalid_array_key' => 'The array key {key} contains invalid characters or format.',
];
```

**Contoh Penggunaan**:
```php
$message = $this->translator->translate('required', ['resource' => 'name'], 'validation');
// en: "The name field is required"
// id: "Field name wajib diisi"

$message = $this->translator->translate('string.min', ['field' => 'name', 'min' => 3], 'validation');
// en: "The name field must be at least 3 characters"
```

---

## 🔧 Konfigurasi Translator

### Konfigurasi DI

**File**: `config/common/di/translator-di.php` (konten aktual)

```php
<?php

declare(strict_types=1);

use Yiisoft\Translator\CategorySource;
use Yiisoft\Translator\IntlMessageFormatter;
use Yiisoft\Translator\Message\Php\MessageSource;
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;

/** @var array $params */

// Core translator binding. Project-owned bindings may be merged from
// config/common/translator.php and can override these defaults.
$projectBindings = \dirname(__DIR__) . '/translator.php';

return array_merge([
    TranslatorInterface::class => static function () {
        $translator = new Translator('en');

        $messageSource = new MessageSource(__DIR__ . '/../../../resources/messages');
        $formatter     = new IntlMessageFormatter();

        $translator->addCategorySources(
            new CategorySource('app', $messageSource, $formatter),
            new CategorySource('validation', $messageSource, $formatter),
            new CategorySource('error', $messageSource, $formatter),
            new CategorySource('success', $messageSource, $formatter),
        );

        return $translator;
    },
], file_exists($projectBindings) ? require $projectBindings : []);
```

### Override Proyek

**File**: `config/common/translator.php` — mengembalikan array yang di-merge di atas default; dapat me-rebind `TranslatorInterface::class` (mis. `static fn () => new Translator('id')`).

### Params Locale

`config/common/params.php` mengekspos locale yang dikonfigurasi di bawah `yiisoft/translator` (dari env var `app.config.language`):

```php
'yiisoft/translator' => [
    'locale'         => $_ENV['app.config.language'],
    'fallbackLocale' => $_ENV['app.config.language'],
],
```

### API `TranslatorInterface` (yiisoft/translator)

```php
$translator->translate(
    string|\Stringable $id,
    array $parameters = [],
    ?string $category = null,   // 'app' | 'error' | 'success' | 'validation'
    ?string $locale = null,     // 'en' | 'id' | ... (null = default locale)
): string;

$translator->setLocale('id');              // change default locale (mutates)
$translator->getLocale();                  // current default locale
$translator->withLocale('id');             // new instance with locale
$translator->withDefaultCategory('error'); // new instance with default category
```

---

## 🚀 Contoh Penggunaan

### Terjemahan Dasar

```php
// Simple translation
$message = $translator->translate('success', [], 'app');

// Error messages
$error = $translator->translate('http.not_found', [], 'error');

// Success messages
$success = $translator->translate('resource.created', ['resource' => 'Example'], 'success');

// Validation messages
$validation = $translator->translate('required', ['resource' => 'name'], 'validation');
```

### Terjemahan dengan Parameter

```php
$tooShort = $translator->translate('string.min', [
    'field' => 'name',
    'min' => 3
], 'validation');
// en: "The name field must be at least 3 characters"

$resourceCreated = $translator->translate('resource.created', [
    'resource' => 'Example'
], 'success');
// en: "Example has been created successfully."
// id: "Example telah berhasil dibuat."
```

### Value Object Message

Exception dan `ResponseFactory` membawa objek `App\Shared\Core\ValueObject\Message` yang diterjemahkan secara lazy saat render:

```php
use App\Shared\Core\ValueObject\Message;

$message = Message::create(
    key: 'resource.not_found',
    params: ['resource' => 'Example', 'field' => 'id', 'value' => 123],
    domain: 'error', // category; null falls back to the renderer's default ('error')
);

// Rendered as:
$translator->translate($message->getKey(), $message->getParams(), $message->getDomain() ?? 'error');
```

---

## 🌐 Deteksi dan Pergantian Bahasa

### Get/Set Locale

```php
$currentLocale = $translator->getLocale();   // e.g. "en" or "id"
$translator->setLocale('id');                // set to Indonesian
$translator->setLocale('en');                // set to English
```

### Locale per Panggilan

```php
// Translate to a specific locale without changing the default
$message = $translator->translate('http.not_found', [], 'error', 'id');

// Or create a derived translator
$idTranslator = $translator->withLocale('id');
$message = $idTranslator->translate('http.not_found', [], 'error');
```

### Bahasa dari Request

```php
// Get language from Accept-Language header
$acceptLanguage = $request->getHeaderLine('Accept-Language');
// Example: "en-US,en;q=0.9,id;q=0.8"

if (str_contains($acceptLanguage, 'id')) {
    $translator->setLocale('id');
}
```

---

## 📝 Menambahkan Bahasa Baru

### 1. **Buat Direktori Bahasa**

```bash
mkdir resources/messages/fr
```

### 2. **Buat File Pesan**

```php
<?php
// resources/messages/fr/app.php
return [
    'success' => 'Succès',
    // ... other translations mirroring the en/ key structure
];
```

### 3. **Atur Locale Default**

```env
# .env
app.config.language=fr
```

Params `yiisoft/translator` (`locale`/`fallbackLocale`) membaca nilai ini; translator juga dapat diganti saat runtime melalui `setLocale()`/`withLocale()`, atau binding dapat di-override di `config/common/translator.php`.

---

## 🔍 Praktik Terbaik

### 1. **Organisasi Pesan**

```php
// ✅ Good: Dot-prefixed, grouped by context
'resource.not_found' => '{resource} data with {field}: {value} was not found',
'resource.already_exists' => 'The {resource} already exists',
'status.invalid_transition' => 'Cannot update {resource} from status "{from}" to "{to}"',

// ❌ Avoid: Deeply nested arrays (message sources use flat keys)
```

### 2. **Penggunaan Parameter**

```php
// ✅ Good: Use parameters for dynamic values
'string.min' => 'The {field} field must be at least {min} characters',

// ❌ Avoid: Hardcoded values
'string.min' => 'The name field must be at least 3 characters',
```

### 3. **Penamaan Konsisten**

```php
// ✅ Good: Consistent naming convention (prefix.context)
'resource.created' => '{resource} has been created successfully.',
'resource.updated' => '{resource} has been updated successfully.',
'resource.deleted' => '{resource} has been deleted successfully.',

// ❌ Avoid: Inconsistent naming
'create_success' => 'Resource created successfully',
'update_done' => 'Resource updated successfully',
```

### 4. **Hormati Batas Skeleton**

```php
// ✅ Good: Add project keys to resources/messages/{locale}/app.php
// ❌ Avoid: Editing error.php / success.php / validation.php —
//    they are regenerated by `composer skeleton:update`
```

---

## 🛠️ Pemeliharaan

### 1. **Jaga Locale Tetap Sinkron**

Saat menambahkan key ke `en/app.php`, tambahkan key yang sama ke `id/app.php` (dan locale lainnya) — himpunan key harus tetap identik.

### 2. **Konsistensi Placeholder**

Pastikan `{placeholders}` cocok di semua locale — `IntlMessageFormatter` melakukan substitusi berdasarkan nama:

```php
// en/app.php
'greeting' => 'Hello, {name}!',
// id/app.php — same placeholder names
'greeting' => 'Halo, {name}!',
```

### 3. **Key yang Hilang**

`translate()` mengembalikan message ID sumber apa adanya saat terjemahan tidak ditemukan — waspadai key mentah seperti `http.not_found` yang muncul di response, yang menandakan entri pesan yang hilang.

---

## 📊 Pertimbangan Performa

### 1. **Pemuatan Pesan**
- `MessageSource` memuat file pesan array PHP per kategori/locale — pembacaan file dibatasi pada `resources/messages/{locale}/{category}.php`

### 2. **Formatting**
- `IntlMessageFormatter` menangani substitusi `{placeholder}`; jaga array parameter tetap kecil

### 3. **Instance**
- Utamakan instance turunan `withLocale()`/`withDefaultCategory()` dibanding mengubah state bersama di tengah request

---

## 🎯 Ringkasan

Sistem lokalisasi menyediakan dukungan internasionalisasi yang komprehensif untuk aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🌐 Dukungan Multi-Bahasa**: Inggris dan Indonesia dengan ekstensi mudah untuk bahasa lain
- **📝 Pesan Terstruktur**: Terorganisir berdasarkan kategori (`app`, `error`, `success`, `validation`)
- **🔧 Konfigurasi Fleksibel**: Binding DI di `config/common/di/translator-di.php`, override proyek di `config/common/translator.php`
- **🔗 Integrasi Exception**: Value object `Message` membawa `key`/`params`/`domain` hingga ke lapisan response
- **🛡️ Type Safe**: `TranslatorInterface` dengan array parameter
- **📊 Maintainable**: Pesan inti yang dikelola skeleton + `app.php` milik proyek

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membuat sistem lokalisasi yang kuat dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
