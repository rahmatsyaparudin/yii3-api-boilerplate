# Localization and Internationalization Guide

## 📋 Overview

This guide covers the localization and internationalization (i18n) system in the Yii3 API application. The system supports multiple languages and provides structured message files for different types of application messages.

---

## 🏗️ Localization Structure

### Directory Structure

```
resources/messages/
├── en/                    # English messages
│   ├── app.php            # Application messages
│   ├── error.php          # Error messages
│   ├── success.php        # Success messages
│   └── validation.php     # Validation messages
└── id/                    # Indonesian messages
    ├── app.php            # Application messages
    ├── error.php          # Error messages
    ├── success.php        # Success messages
    └── validation.php     # Validation messages
```

### Supported Languages

- **English (en)**: Default language, used for development and international users
- **Indonesian (id)**: Local language for Indonesian users

---

## 📁 Message Files

### 1. Application Messages (app.php)

**Purpose**: General application messages and UI text

#### English Version (`resources/messages/en/app.php`)
```php
<?php

declare(strict_types=1);

/**
 * Application messages (English)
 */
return [
    /*
     * Add your application-specific messages here
     */

    'success' => 'Success',
];
```

#### Indonesian Version (`resources/messages/id/app.php`)
```php
<?php

declare(strict_types=1);

/**
 * Application messages (Indonesian)
 */
return [
    /*
     * Add your application-specific messages here
     */

    'success' => 'Success',
];
```

**Usage Example**:
```php
// In controller or service
$successMessage = $translator->translate('success', [], 'app');
// English: "Success"
// Indonesian: "Success"
```

---

### 2. Error Messages (error.php)

**Purpose**: System error messages and exception handling

#### English Version (`resources/messages/en/error.php`)
```php
<?php

declare(strict_types=1);

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

    // Request Prefix
    'request.invalid_json' => 'The request body contains invalid JSON',
    'request.invalid_body' => 'The request body is invalid or malformed',
    'request.missing_parameter' => 'Required parameter is missing: {param}',
    'request.invalid_parameter' => 'The parameter value is invalid: {param}',
    'request.host_not_allowed' => 'The host is not allowed: {host}',
    'request.origin_not_allowed' => 'The origin is not allowed: {origin}',

    // Filtering
    'filter.invalid_keys' => 'The request contains unsupported filter field(s): {keys}',
    'filter.not_allowed' => 'Filtering by the field "{filter}" is not allowed',
    
    // Sorting
    'sort.invalid_field' => 'The specified sort field "{field}" is invalid or not allowed',
    'sort.invalid_direction' => 'The sort direction "{direction}" is invalid. Use "asc" or "desc"',
    
    // Pagination
    'pagination.invalid_page' => 'The page number must be a valid positive integer',
    'pagination.invalid_limit' => 'The page size value is invalid',
    'pagination.invalid_page_size' => 'The page size value is invalid',
    'pagination.invalid_parameter' => 'Invalid "{parameter}" parameter',
    
    // Route
    'route.field_required' => 'Invalid request. {resource} {field} is required in the URL',
    'route.parameter_missing' => '{resource} {parameter} parameter is required in the URL',

    // Validation
    'validation.failed' => 'Validation failed. Please review the provided data',

    // Data type (Prefix 'type.')
    'type.string' => 'The {field} field must be a string',
    'type.integer' => 'The {field} field must be an integer',
    'type.numeric' => 'The {field} field must be a numeric value',
    'type.boolean' => 'The {field} field must be a boolean value',
    'type.array' => 'The {field} field must be an array',
    'type.object' => 'The {field} field must be an object',

    // Format (Prefix 'format.')
    'format.email' => 'The {field} field must be a valid email address',
    'format.url' => 'The {field} field must be a valid URL',
    'format.uuid' => 'The {field} field must be a valid UUID',
    'format.date' => 'The {field} field must be a valid date',
    'format.datetime' => 'The {field} field must be a valid date and time',
    'format.json' => 'The {field} field must contain valid JSON',

    // Range & Length
    'range.min' => 'The {field} field must be greater than or equal to {min}',
    'range.max' => 'The {field} field must be less than or equal to {max}',
    'length.min' => 'The {field} field must contain at least {min} characters',
    'length.max' => 'The {field} field must not exceed {max} characters',

    // File
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
    'resource.modification_denied_on_deleted' => 'Action prohibited: The requested operation cannot be performed because the {resource} is marked as "{status}"',

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
];
```

#### Indonesian Version (`resources/messages/id/error.php`)
```php
<?php

declare(strict_types=1);

return [
    // General Errors
    'general' => [
        'unknown' => 'Terjadi kesalahan yang tidak diketahui',
        'server_error' => 'Kesalahan server internal',
        'database_error' => 'Kesalahan koneksi database',
        'network_error' => 'Kesalahan koneksi jaringan',
        'timeout_error' => 'Kesalahan timeout permintaan',
        'permission_denied' => 'Izin ditolak',
        'not_found' => 'Sumber daya tidak ditemukan',
        'invalid_request' => 'Permintaan tidak valid',
        'validation_failed' => 'Validasi gagal',
        'authentication_failed' => 'Autentikasi gagal',
        'authorization_failed' => 'Otorisasi gagal',
    ],
    
    // HTTP Status Codes
    'http' => [
        400 => 'Permintaan Tidak Valid',
        401 => 'Tidak Sah',
        403 => 'Dilarang',
        404 => 'Tidak Ditemukan',
        405 => 'Metode Tidak Diizinkan',
        409 => 'Konflik',
        422 => 'Entitas Tidak Dapat Diproses',
        429 => 'Terlalu Banyak Permintaan',
        500 => 'Kesalahan Server Internal',
        502 => 'Gateway Buruk',
        503 => 'Layanan Tidak Tersedia',
        504 => 'Gateway Timeout',
    ],
    
    // Domain-Specific Errors
    'example' => [
        'not_found' => 'Contoh tidak ditemukan',
        'already_exists' => 'Contoh sudah ada',
        'invalid_status' => 'Transisi status tidak valid',
        'cannot_delete' => 'Tidak dapat menghapus contoh',
        'cannot_restore' => 'Tidak dapat memulihkan contoh',
        'lock_version_mismatch' => 'Rekaman telah dimodifikasi oleh pengguna lain',
    ],
    
    // Validation Errors
    'validation' => [
        'required' => 'Field ini wajib diisi',
        'invalid_format' => 'Format tidak valid',
        'too_short' => 'Nilai terlalu pendek',
        'too_long' => 'Nilai terlalu panjang',
        'invalid_email' => 'Format email tidak valid',
        'invalid_url' => 'Format URL tidak valid',
        'invalid_date' => 'Format tanggal tidak valid',
        'invalid_number' => 'Format angka tidak valid',
        'out_of_range' => 'Nilai di luar rentang yang diizinkan',
        'unique_violation' => 'Nilai harus unik',
    ],
];
```

**Usage Example**:
```php
// In error handling
$errorMessage = Yii::t('error', 'general.not_found');
// English: "Resource not found"
// Indonesian: "Sumber daya tidak ditemukan"

// With nested keys
$httpError = Yii::t('error', 'http.404');
// English: "Not Found"
// Indonesian: "Tidak Ditemukan"

// Domain-specific errors
$exampleError = Yii::t('error', 'example.not_found');
// English: "Example not found"
// Indonesian: "Contoh tidak ditemukan"
```

---

### 3. Success Messages (success.php)

**Purpose**: Success messages and positive feedback

#### English Version (`resources/messages/en/success.php`)
```php
<?php

declare(strict_types=1);

/**
 * Application messages (English)
 */
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
    
    // Authentication
    'auth.login_success' => 'Login successful',
    'auth.logout_success' => 'Logout successful',
    'auth.password_reset_sent' => 'Password reset link has been sent to your email',
    'auth.password_reset_success' => 'Your password has been reset successfully',
    'auth.email_verified' => 'Email address has been verified successfully',
    'auth.account_created' => 'Your account has been created successfully',
];
```

#### Indonesian Version (`resources/messages/id/success.php`)
```php
<?php

declare(strict_types=1);

/**
 * Application messages (Indonesian)
 */
return [
    // General Success
    'success' => 'Success',
    'ok' => 'OK',
    'completed' => '{resource} berhasil diselesaikan',
    'processed' => '{resource} berhasil diproses',
    
    // Global CRUD Operations
    'resource.created' => '{resource} telah berhasil dibuat.',
    'resource.updated' => '{resource} telah berhasil diperbarui.',
    'resource.deleted' => '{resource} telah berhasil dihapus.',
    'resource.restored' => '{resource} telah berhasil dipulihkan.',
    'resource.list_retrieved' => 'Daftar {resource} berhasil diambil.',
    'resource.details_retrieved' => 'Detail {resource} berhasil diambil.',
    'resource.no_changes_detected' => 'Tidak ada perubahan yang terdeteksi untuk {resource}. Data yang dikirim identik dengan rekaman saat ini.',
    
    // Authentication
    'auth.login_success' => 'Login berhasil',
    'auth.logout_success' => 'Logout berhasil',
    'auth.password_reset_sent' => 'Link reset kata sandi telah dikirim ke email Anda',
    'auth.password_reset_success' => 'Kata sandi Anda telah berhasil direset',
    'auth.email_verified' => 'Alamat email telah berhasil diverifikasi',
    'auth.account_created' => 'Akun Anda telah berhasil dibuat',
];
```

**Usage Example**:
```php
// In success handling
$successMessage = $translator->translate('success', [], 'success');
// English: "Success"
// Indonesian: "Success"

// With parameters
$createdMessage = $translator->translate('resource.created', ['resource' => 'Example'], 'success');
// English: "Example has been created successfully."
// Indonesian: "Example telah berhasil dibuat."

// Authentication success
$loginMessage = $translator->translate('auth.login_success', [], 'success');
// English: "Login successful"
// Indonesian: "Login berhasil"
```

---

### 4. Validation Messages (validation.php)

**Purpose**: Form validation and data validation messages

#### English Version (`resources/messages/en/validation.php`)
```php
<?php

declare(strict_types=1);

/**
 * Validation messages (English)
 * Complete validation rules for common use cases
 */
return [
    // Required
    'required' => 'The {resource} field is required',
    'required.if' => 'The {resource} field is required when {other} is {value}',
    'required.unless' => 'The {resource} field is required unless {other} is {value}',
    'required.with' => 'The {resource} field is required when {other} is present',
    'required.without' => 'The {resource} field is required when {other} is not present',
    'filled' => 'The {resource} field must have a value',
    'present' => 'The {resource} field must be present',
    
    // String Prefix
    'string.invalid' => 'The {field} field must be a string',
    'string.min' => 'The {field} field must be at least {min} characters',
    'string.max' => 'The {field} field must not exceed {max} characters',
    'string.length' => 'The {field} field must be exactly {length} characters',
    'string.between' => 'The {field} field must be between {min} and {max} characters',
    'string.alpha' => 'The {field} field may only contain letters',
    'string.alpha_num' => 'The {field} field may only contain letters and numbers',
    'string.alpha_dash' => 'The {field} field may only contain letters, numbers, dashes, and underscores',
    'string.starts_with' => 'The {field} field must start with: {values}',
    'string.ends_with' => 'The {field} field must end with: {values}',
    'string.lowercase' => 'The {field} field must be lowercase',
    'string.uppercase' => 'The {field} field must be uppercase',
    
    // Number Prefix
    'number.invalid' => 'The {field} field must be a number',
    'number.integer' => 'The {field} field must be an integer',
    'number.decimal' => 'The {field} field must be a decimal number',
    'number.min' => 'The {field} field must be at least {min}',
    'number.max' => 'The {field} field must not exceed {max}',
    'number.between' => 'The {field} field must be between {min} and {max}',
    'number.positive' => 'The {field} field must be a positive number',
    'number.negative' => 'The {field} field must be a negative number',
    'number.divisible_by' => 'The {field} field must be divisible by {divisor}',
    
    // Format
    'format.email' => 'The {field} field must be a valid email address',
    'format.url' => 'The {field} field must be a valid URL',
    'format.ip' => 'The {field} field must be a valid IP address',
    'format.ipv4' => 'The {field} field must be a valid IPv4 address',
    'format.ipv6' => 'The {field} field must be a valid IPv6 address',
    'format.mac_address' => 'The {field} field must be a valid MAC address',
    'format.uuid' => 'The {field} field must be a valid UUID',
    'format.date' => 'The {field} field must be a valid date',
    'format.datetime' => 'The {field} field must be a valid date and time',
    'format.json' => 'The {field} field must contain valid JSON',
    
    // Range & Length
    'range.min' => 'The {field} field must be greater than or equal to {min}',
    'range.max' => 'The {field} field must be less than or equal to {max}',
    'length.min' => 'The {field} field must contain at least {min} characters',
    'length.max' => 'The {field} field must not exceed {max} characters',
    
    // File
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
];
```

#### Indonesian Version (`resources/messages/id/validation.php`)
```php
<?php

declare(strict_types=1);

/**
 * Validation messages (Indonesian)
 * Complete validation rules for common use cases
 */
return [
    // Required
    'required' => 'Field {resource} wajib diisi',
    'required.if' => 'Field {resource} wajib diisi ketika {other} adalah {value}',
    'required.unless' => 'Field {resource} wajib diisi kecuali {other} adalah {value}',
    'required.with' => 'Field {resource} wajib diisi ketika {other} ada',
    'required.without' => 'Field {resource} wajib diisi ketika {other} tidak ada',
    'filled' => 'Field {resource} harus memiliki nilai',
    'present' => 'Field {resource} harus ada',
    
    // String Prefix
    'string.invalid' => 'Field {field} harus berupa string',
    'string.min' => 'Field {field} harus minimal {min} karakter',
    'string.max' => 'Field {field} tidak boleh melebihi {max} karakter',
    'string.length' => 'Field {field} harus tepat {length} karakter',
    'string.between' => 'Field {field} harus antara {min} dan {max} karakter',
    'string.alpha' => 'Field {field} hanya boleh mengandung huruf',
    'string.alpha_num' => 'Field {field} hanya boleh mengandung huruf dan angka',
    'string.alpha_dash' => 'Field {field} hanya boleh mengandung huruf, angka, dash, dan underscore',
    'string.starts_with' => 'Field {field} harus dimulai dengan: {values}',
    'string.ends_with' => 'Field {field} harus diakhiri dengan: {values}',
    'string.lowercase' => 'Field {field} harus huruf kecil',
    'string.uppercase' => 'Field {field} harus huruf besar',
    
    // Number Prefix
    'number.invalid' => 'Field {field} harus berupa angka',
    'number.integer' => 'Field {field} harus berupa bilangan bulat',
    'number.decimal' => 'Field {field} harus berupa bilangan desimal',
    'number.min' => 'Field {field} harus minimal {min}',
    'number.max' => 'Field {field} tidak boleh melebihi {max}',
    'number.between' => 'Field {field} harus antara {min} dan {max}',
    'number.positive' => 'Field {field} harus berupa angka positif',
    'number.negative' => 'Field {field} harus berupa angka negatif',
    'number.divisible_by' => 'Field {field} harus habis dibagi {divisor}',
    
    // Format
    'format.email' => 'Field {field} harus berupa alamat email yang valid',
    'format.url' => 'Field {field} harus berupa URL yang valid',
    'format.ip' => 'Field {field} harus berupa alamat IP yang valid',
    'format.ipv4' => 'Field {field} harus berupa alamat IPv4 yang valid',
    'format.ipv6' => 'Field {field} harus berupa alamat IPv6 yang valid',
    'format.mac_address' => 'Field {field} harus berupa alamat MAC yang valid',
    'format.uuid' => 'Field {field} harus berupa UUID yang valid',
    'format.date' => 'Field {field} harus berupa tanggal yang valid',
    'format.datetime' => 'Field {field} harus berupa tanggal dan waktu yang valid',
    'format.json' => 'Field {field} harus mengandung JSON yang valid',
    
    // Range & Length
    'range.min' => 'Field {field} harus lebih besar atau sama dengan {min}',
    'range.max' => 'Field {field} harus kurang dari atau sama dengan {max}',
    'length.min' => 'Field {field} harus mengandung minimal {min} karakter',
    'length.max' => 'Field {field} tidak boleh melebihi {max} karakter',
    
    // File
    'file.invalid' => 'File yang diunggah tidak valid',
    'file.too_large' => 'File yang diunggah melebihi ukuran maksimum yang diizinkan',
    'file.invalid_type' => 'Tipe file yang diunggah tidak diizinkan',
    
    // Resource (Business Logic)
    'resource.not_found' => 'Data {resource} dengan {field}: {value} tidak ditemukan',
    'resource.conflict' => 'Terjadi konflik dengan {resource}',
    'resource.already_exists' => '{resource} sudah ada',
    'resource.cannot_update' => 'Tidak dapat memperbarui {resource}. Perubahan status dari "{current_status}" ke "{status}" tidak diizinkan',
    'resource.update_not_allowed_by_status' => 'Perubahan data tidak diizinkan untuk {resource} ketika statusnya adalah "{current_status}"',
    'resource.status_already_set' => 'Tidak dapat memperbarui {resource}. Status sudah "{current_status}"',
    'resource.modification_denied_on_deleted' => 'Aksi dilarang: Operasi yang diminta tidak dapat dilakukan karena {resource} ditandai sebagai "{status}".',
    
    // Access & Rate Limit
    'operation.not_allowed' => 'Operasi ini tidak diizinkan',
    'access.denied' => 'Akses ke sumber daya ini ditolak',
    'access.insufficient_permissions' => 'Anda tidak memiliki izin yang cukup untuk melakukan tindakan ini',
    'access.auth_required' => 'Autentikasi diperlukan untuk mengakses sumber daya ini',
    'rate_limit.exceeded' => 'Terlalu banyak permintaan. Silakan coba lagi setelah {seconds} detik',
    'rate_limit.try_again' => 'Silakan coba lagi dalam {seconds} detik',

    'business.violation' => 'Pelanggaran aturan bisnis: {reason}',
    'business.limit_reached' => 'Batas {resource} tercapai',
    'business.requirement_unmet' => 'Persyaratan tidak terpenuhi: {reason}',

    'service.error' => 'Gagal memproses permintaan: {reason}',
    'service.unavailable' => 'Layanan "{service}" tidak tersedia',
    'service.failed' => 'Proses layanan gagal',

    'factory.detail_info.uninitialized_state' => 'Kesalahan internal: Status factory tidak diinisialisasi. Anda harus memanggil {methods} sebelum melakukan operasi ini.',

    // Optimistic Locking
    'optimistic.lock.failed' => 'Data {resource} telah dimodifikasi oleh pengguna lain. Silakan refresh dan coba lagi.',
    'lock_version.invalid_negative' => 'Versi lock tidak bisa negatif: {value}',
];
```

**Usage Example**:
```php
// In validation
$requiredMessage = $translator->translate('required', ['resource' => 'name'], 'validation');
// English: "The name field is required"
// Indonesian: "Field name wajib diisi"

// String validation
$stringMinMessage = $translator->translate('string.min', ['field' => 'name', 'min' => 3], 'validation');
// English: "The name field must be at least 3 characters"
// Indonesian: "Field name harus minimal 3 karakter"

// Format validation
$emailMessage = $translator->translate('format.email', ['field' => 'email'], 'validation');
// English: "The email field must be a valid email address"
// Indonesian: "Field email harus berupa alamat email yang valid"

// Number validation
$numberMinMessage = $translator->translate('number.min', ['field' => 'age', 'min' => 18], 'validation');
// English: "The age field must be at least 18"
// Indonesian: "Field age harus minimal 18"
```

---

## 🔧 Translator Configuration

### DI Configuration

**File**: `config/common/di/translator.php`

```php
<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Translator\Translator;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Translator\MessageSourceInterface;
use Yiisoft\Translator\Message\Php\MessageSource;

return [
    TranslatorInterface::class => Translator::class,
    MessageSourceInterface::class => [
        'class' => MessageSource::class,
        '__construct()' => [
            'basePath' => '@resources/messages',
            'category' => 'app',
        ],
    ],
];
```

### Message Source Configuration

The translator is configured to:
- **Base Path**: `@resources/messages` - Root directory for message files
- **Category**: `app` - Default category for messages
- **Format**: PHP arrays - Message files are PHP arrays

---

## 🚀 Usage Examples

### Basic Translation

```php
// Simple translation
$message = $translator->translate('success', [], 'app');
// English: "Success"
// Indonesian: "Success"

// Error messages
$error = $translator->translate('http.not_found', [], 'error');
// English: "The requested resource was not found"
// Indonesian: "Sumber daya tidak ditemukan"

// Success messages
$success = $translator->translate('resource.created', ['resource' => 'Example'], 'success');
// English: "Example has been created successfully."
// Indonesian: "Example telah berhasil dibuat."

// Validation messages
$validation = $translator->translate('required', ['resource' => 'name'], 'validation');
// English: "The name field is required"
// Indonesian: "Field name wajib diisi"
```

### Translation with Parameters

```php
// With parameters
$tooShort = $translator->translate('string.min', [
    'field' => 'name',
    'min' => 3
], 'validation');
// English: "The name field must be at least 3 characters"
// Indonesian: "Field name harus minimal 3 karakter"

$tooLong = $translator->translate('string.max', [
    'field' => 'name',
    'max' => 255
], 'validation');
// English: "The name field must not exceed 255 characters"
// Indonesian: "Field name tidak boleh melebihi 255 karakter"

// Resource operations
$resourceCreated = $translator->translate('resource.created', [
    'resource' => 'Example'
], 'success');
// English: "Example has been created successfully."
// Indonesian: "Example telah berhasil dibuat."
```

### Nested Key Translation

```php
// HTTP errors
$httpError = $translator->translate('http.not_found', [], 'error');
// English: "The requested resource was not found"
// Indonesian: "Sumber daya tidak ditemukan"

$unauthorizedError = $translator->translate('http.unauthorized', [], 'error');
// English: "Authentication is required or has failed"
// Indonesian: "Autentikasi diperlukan atau gagal"

// Resource errors
$resourceError = $translator->translate('resource.not_found', [
    'resource' => 'Example',
    'field' => 'id',
    'value' => 123
], 'error');
// English: "Example data with id: 123 was not found"
// Indonesian: "Data Example dengan id: 123 tidak ditemukan"

// Validation errors
$requiredError = $translator->translate('required.if', [
    'resource' => 'password',
    'other' => 'email',
    'value' => 'present'
], 'validation');
// English: "The password field is required when email is present"
// Indonesian: "Field password wajib diisi ketika email ada"
```

### Category Translation

```php
// Different categories
$appMessage = $translator->translate('success', [], 'app');
$errorMessage = $translator->translate('http.not_found', [], 'error');
$successMessage = $translator->translate('resource.created', ['resource' => 'Example'], 'success');
$validationMessage = $translator->translate('required', ['resource' => 'name'], 'validation');

// Authentication messages
$loginMessage = $translator->translate('auth.login_success', [], 'success');
$logoutMessage = $translator->translate('auth.logout_success', [], 'success');

// String validation
$stringMinMessage = $translator->translate('string.min', ['field' => 'name', 'min' => 3], 'validation');
$stringMaxMessage = $translator->translate('string.max', ['field' => 'name', 'max' => 255], 'validation');

// Format validation
$emailMessage = $translator->translate('format.email', ['field' => 'email'], 'validation');
$urlMessage = $translator->translate('format.url', ['field' => 'website'], 'validation');
```

---

## 🌐 Language Detection and Switching

### Language Detection

```php
// Get current language
$currentLanguage = $translator->getLanguage();
// Output: "en" or "id"

// Set language
$translator->setLanguage('en');  // Set to English
$translator->setLanguage('id');  // Set to Indonesian
```

### Language from Request

```php
// Get language from request header
$acceptLanguage = $request->getHeaders()->get('Accept-Language');
// Example: "en-US,en;q=0.9,id;q=0.8"

// Set language based on request
if (str_contains($acceptLanguage, 'id')) {
    $translator->setLanguage('id');
} else {
    $translator->setLanguage('en');
}
```

### Language from User Preference

```php
// Get user preferred language
$user = $this->user->getIdentity();
if ($user && $user->getLanguage()) {
    $translator->setLanguage($user->getLanguage());
}
```

---

## 📝 Adding New Languages

### 1. **Create Language Directory**

```bash
mkdir resources/messages/fr
mkdir resources/messages/es
mkdir resources/messages/ja
```

### 2. **Create Message Files**

```php
<?php
// resources/messages/fr/app.php
return [
    'welcome' => 'Bienvenue dans Yii3 API',
    'goodbye' => 'Merci d\'utiliser Yii3 API',
    'loading' => 'Chargement...',
    // ... other translations
];
```

### 3. **Update Translator Configuration**

```php
// config/common/di/translator.php
return [
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
];
```

### 4. **Update Application Configuration**

```php
// config/web/params.php or config/console/params.php
return [
    'app.language' => 'en',  // Default language
    'app.supportedLanguages' => ['en', 'id', 'fr', 'es', 'ja'],
];
```

---

## 🔍 Best Practices

### 1. **Message Organization**

```php
// ✅ Good: Organized by category and context
'example' => [
    'not_found' => 'Example not found',
    'already_exists' => 'Example already exists',
    'invalid_status' => 'Invalid status transition',
],

// ❌ Avoid: Flat structure
'example_not_found' => 'Example not found',
'example_already_exists' => 'Example already exists',
'example_invalid_status' => 'Invalid status transition',
```

### 2. **Parameter Usage**

```php
// ✅ Good: Use parameters for dynamic values
'too_short' => 'Name must be at least {min} characters',
'too_long' => 'Name must not exceed {max} characters',

// ❌ Avoid: Hardcoded values
'too_short' => 'Name must be at least 3 characters',
'too_long' => 'Name must not exceed 255 characters',
```

### 3. **Consistent Naming**

```php
// ✅ Good: Consistent naming convention
'created' => 'Resource created successfully',
'updated' => 'Resource updated successfully',
'deleted' => 'Resource deleted successfully',

// ❌ Avoid: Inconsistent naming
'create_success' => 'Resource created successfully',
'update_done' => 'Resource updated successfully',
'delete_ok' => 'Resource deleted successfully',
```

### 4. **Context-Aware Messages**

```php
// ✅ Good: Context-specific messages
'validation' => [
    'fields' => [
        'email' => [
            'required' => 'Email is required',
            'invalid' => 'Invalid email address',
        ],
    ],
],

// ❌ Avoid: Generic messages
'email_required' => 'Email is required',
'email_invalid' => 'Invalid email address',
```

---

## 🛠️ Maintenance

### 1. **Message File Validation**

```php
// Validate message file structure
function validateMessageFile(string $filePath): array
{
    $messages = require $filePath;
    $errors = [];
    
    foreach ($messages as $key => $value) {
        if (!is_string($value) && !is_array($value)) {
            $errors[] = "Invalid message type for key: {$key}";
        }
    }
    
    return $errors;
}
```

### 2. **Missing Translation Detection**

```php
// Find missing translations
function findMissingTranslations(string $sourceLang, string $targetLang): array
{
    $sourceMessages = require "resources/messages/{$sourceLang}/app.php";
    $targetMessages = require "resources/messages/{$targetLang}/app.php";
    
    $missing = [];
    
    foreach ($sourceMessages as $key => $value) {
        if (!array_key_exists($key, $targetMessages)) {
            $missing[] = $key;
        }
    }
    
    return $missing;
}
```

### 3. **Translation Consistency Check**

```php
// Check translation consistency
function checkTranslationConsistency(string $lang1, string $lang2): array
{
    $messages1 = require "resources/messages/{$lang1}/app.php";
    $messages2 = require "resources/messages/{$lang2}/app.php";
    
    $inconsistent = [];
    
    foreach ($messages1 as $key => $value) {
        if (array_key_exists($key, $messages2)) {
            // Check for parameter consistency
            $params1 = $this->extractParameters($value);
            $params2 = $this->extractParameters($messages2[$key]);
            
            if ($params1 !== $params2) {
                $inconsistent[] = $key;
            }
        }
    }
    
    return $inconsistent;
}
```

---

## 📊 Performance Considerations

### 1. **Message Caching**

```php
// Enable message caching
return [
    TranslatorInterface::class => [
        'class' => Translator::class,
        '__construct()' => [
            Reference::to(MessageSourceInterface::class),
        ],
        'setCache()' => [
            Reference::to(CacheInterface::class),
        ],
    ],
];
```

### 2. **Lazy Loading**

```php
// Load messages only when needed
class LazyTranslator
{
    private array $messages = [];
    
    public function translate(string $category, string $message, array $params = []): string
    {
        $key = "{$category}.{$message}";
        
        if (!isset($this->messages[$key])) {
            $this->messages[$key] = $this->loadMessage($category, $message);
        }
        
        return strtr($this->messages[$key], $params);
    }
}
```

### 3. **Message Precompilation**

```php
// Precompile messages for better performance
class CompiledTranslator
{
    private array $compiledMessages = [];
    
    public function __construct(string $language)
    {
        $this->compiledMessages = $this->compileMessages($language);
    }
    
    private function compileMessages(string $language): array
    {
        // Compile all messages into optimized format
        return $this->optimizeMessages($language);
    }
}
```

---

## 🎯 Summary

The localization system provides comprehensive internationalization support for the Yii3 API application. Key benefits include:

- **🌐 Multi-Language Support**: English and Indonesian with easy extension for more languages
- **📝 Structured Messages**: Organized by category (app, error, success, validation)
- **🔧 Flexible Configuration**: Easy to add new languages and message categories
- **🚀 Performance Optimized**: Caching and lazy loading support
- **🛡️ Type Safe**: Parameter validation and type checking
- **📊 Maintainable**: Consistent structure and naming conventions

### Message Categories

1. **app.php**: General application messages and UI text
2. **error.php**: System error messages and exception handling
3. **success.php**: Success messages and positive feedback
4. **validation.php**: Form validation and data validation messages

### Usage Patterns

```php
// Basic translation
$translator->translate('message.key', [], 'category');

// With parameters
$translator->translate('message.key', ['param' => 'value'], 'category');

// Using Message Value Object
new Message(
    key: 'resource.not_found', 
    params: ['resource' => 'Example', 'field' => 'id', 'value' => 123],
    domain: 'error'
)
```

By following the patterns and best practices outlined in this guide, you can create a robust, maintainable localization system for your Yii3 API application! 🚀
