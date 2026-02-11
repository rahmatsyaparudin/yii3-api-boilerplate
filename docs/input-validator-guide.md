# Panduan Input Validator

## Overview

Input Validator adalah komponen yang bertanggung jawab untuk memvalidasi data input yang masuk ke sistem. Validator menggunakan pattern `AbstractValidator` dengan `ValidationContext` untuk menerapkan aturan validasi yang berbeda sesuai dengan operasi yang sedang dilakukan.

## Struktur Dasar

### File Location
```
src/Api/V1/{Module}/Validation/{Module}InputValidator.php
```

### Class Structure
```php
<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Validation;

// Domain Layer
use App\Domain\Example\Entity\Example;

// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\Validation\AbstractValidator;
use App\Shared\Validation\Rules\HasNoDependencies;
use App\Shared\Validation\Rules\UniqueValue;

/**
 * Example Input Validator
 * 
 * Menggunakan pattern AbstractValidator dengan ValidationContext
 * untuk validasi input yang berbeda per operation
 */
final class ExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            // Aturan untuk operasi CREATE
            ValidationContext::CREATE => [
                // Field validation rules
            ],
            
            // Aturan untuk operasi UPDATE
            ValidationContext::UPDATE => [
                // Field validation rules
            ],
            
            // Aturan untuk operasi DELETE
            ValidationContext::DELETE => [
                // Field validation rules
            ],
            
            // Aturan untuk operasi SEARCH
            ValidationContext::SEARCH => [
                // Field validation rules
            ],
            
            default => [],
        };
    }
}
```

## Cara Penggunaan

### 1. Menggunakan Validator

Cara dasar menggunakan validator dengan context yang sesuai:

```php
// Validasi input dengan context CREATE
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE
);

// Validasi input dengan context UPDATE
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::UPDATE
);

// Validasi input dengan context DELETE
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::DELETE
);

// Validasi input dengan context SEARCH
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::SEARCH
);

// Cek hasil validasi
if ($validator->fails()) {
    // Handle validation errors
    $errors = $validator->errors();
    // ... error handling logic
}

// Lanjutkan dengan data yang valid
$validatedData = $validator->validated();
```

### 2. Context yang Tersedia

#### CREATE Context
Untuk validasi saat membuat data baru:
```php
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE
);
```

#### UPDATE Context  
Untuk validasi saat mengupdate data yang sudah ada:
```php
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::UPDATE
);
```

#### DELETE Context
Untuk validasi saat menghapus data:
```php
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::DELETE
);
```

#### SEARCH Context
Untuk validasi parameter pencarian:
```php
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::SEARCH
);
```

### 3. Error Handling

Cek hasil validasi dan handle errors:
```php
$validator = $this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE
);

if ($validator->fails()) {
    // Ambil semua error messages
    $errors = $validator->errors();
    
    // Ambil error untuk field tertentu
    $nameError = $validator->error('name');
    
    // Cek apakah field tertentu memiliki error
    $hasNameError = $validator->hasError('name');
    
    // ... handle validation errors
}

// Data yang sudah valid
$validatedData = $validator->validated();
```

### 4. Validation Contexts

#### CREATE Context
Digunakan saat membuat entitas baru:
```php
ValidationContext::CREATE => [
    'name' => [
        new Required(),                          // Wajib diisi
        new StringLength(max: 255),             // Maksimal 255 karakter
        new UniqueValue(                        // Harus unik
            table: 'examples',
            column: 'name',
            ignoreId: null,                      // Tidak ada exclusion untuk create
        ),
    ],
    'status' => [
        new Required(),                          // Wajib diisi
        new Integer(min: 1, max: 10),           // Harus integer 1-10
    ],
    'detail_info' => [
        new ArrayType(),                         // Harus array
        skipOnEmpty: true,                       // Boleh kosong
    ],
],
```

#### UPDATE Context
Digunakan saat mengupdate entitas yang sudah ada:
```php
ValidationContext::UPDATE => [
    'id' => [
        new Required(),                          // Wajib diisi
        new Integer(min: 1),                   // Harus integer positif
    ],
    'name' => [
        new StringLength(max: 255),             // Maksimal 255 karakter
        new UniqueValue(                        // Harus unik
            table: 'examples',
            column: 'name',
            ignoreId: $this->data['id'] ?? null, // Exclude current record
        ),
        skipOnEmpty: true,                       // Boleh kosong (optional)
    ],
    'status' => [
        new Integer(min: 1, max: 10),           // Harus integer 1-10
        skipOnEmpty: true,                       // Boleh kosong (optional)
    ],
],
```

#### DELETE Context
Digunakan saat menghapus entitas:
```php
ValidationContext::DELETE => [
    'id' => [
        new Required(),                          // Wajib diisi
        new Integer(min: 1),                   // Harus integer positif
        new HasNoDependencies(                  // Tidak boleh ada dependencies
            map: [
                'other_table' => ['example_id'], // Cek di tabel other_table
                'another_table' => ['example_id'], // Cek di tabel another_table
            ],
            message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
        ),
    ],
],
```

#### SEARCH Context
Digunakan untuk pencarian dan filtering:
```php
ValidationContext::SEARCH => [
    'page' => [
        new Integer(min: 1),                   // Minimal 1
        default: 1,                            // Default value
    ],
    'per_page' => [
        new Integer(min: 1, max: 100),         // 1-100 items per page
        default: 20,                           // Default value
    ],
    'search' => [
        new StringLength(max: 100),            // Maksimal 100 karakter
        skipOnEmpty: true,                      // Boleh kosong
    ],
    'status' => [
        new In([1, 2, 3]),                     // Harus salah satu dari nilai ini
        skipOnEmpty: true,                      // Boleh kosong
    ],
    'sort_by' => [
        new In(['name', 'status', 'created_at']), // Boleh sort by field ini
        skipOnEmpty: true,                      // Boleh kosong
    ],
    'sort_dir' => [
        new In(['asc', 'desc']),                // asc atau desc
        skipOnEmpty: true,                      // Boleh kosong
    ],
],
```

## Validation Rules yang Tersedia

### 1. Basic Rules
- `Required()` - Field wajib diisi
- `Integer(min?, max?)` - Harus integer dengan batasan min/max
- `StringLength(max?)` - String dengan panjang maksimal
- `In(array $values)` - Harus salah satu dari nilai yang diberikan
- `ArrayType()` - Harus bertipe array
- `StringValue()` - Harus bertipe string

### 2. Advanced Rules
- `UniqueValue()` - Cek keunikan nilai di database
- `HasNoDependencies()` - Cek apakah record memiliki dependencies
- `StopOnError()` - Stop validation jika error ditemukan

### 3. Rule Options
- `skipOnEmpty: true` - Lewati validasi jika field kosong
- `default: value` - Nilai default jika tidak diisi
- `message: string` - Custom error message

## Custom Error Messages

### Bahasa Indonesia
```php
new UniqueValue(
    table: 'examples',
    column: 'name',
    message: 'Nama example sudah digunakan.'
),

new HasNoDependencies(
    map: [
        'other_table' => ['example_id'],
    ],
    message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
),

new Required(
    message: 'Field {field} wajib diisi.'
),

new StringLength(
    max: 255,
    message: 'Field {field} maksimal {max} karakter.'
),
```

## Best Practices

### 1. Gunakan Validation Context
Selalu gunakan context yang sesuai dengan operasi:
- `CREATE` untuk pembuatan baru
- `UPDATE` untuk perubahan data
- `DELETE` untuk penghapusan
- `SEARCH` untuk pencarian

### 2. Optional Fields
Gunakan `skipOnEmpty: true` untuk field yang tidak wajib:
```php
'name' => [
    new StringLength(max: 255),
    skipOnEmpty: true,  // Boleh kosong untuk update
],
```

### 3. Default Values
Berikan default value untuk field pencarian:
```php
'page' => [
    new Integer(min: 1),
    default: 1,  // Default page 1
],
```

### 4. Error Handling
Selalu cek hasil validasi sebelum melanjutkan:
```php
$validator = $this->inputValidator->validate($data, $context);

if ($validator->fails()) {
    return $this->responseFactory->fail(
        translate: Message::create(
            key: 'validation.failed',
            params: ['errors' => $validator->errors()]
        )
    );
}

// Lanjutkan dengan data yang valid
```

### 5. Custom Validation Rules
Untuk validasi yang kompleks, buat custom rule:
```php
// Di dalam validator class
protected function customRule(string $value): bool
{
    // Logic validasi kustom
    return strlen($value) > 5 && preg_match('/^[a-zA-Z0-9]+$/', $value);
}
```

## Contoh Lengkap

### Create Action dengan Validasi Lengkap
```php
final class ExampleCreateAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $params = $request->getParsedBody();
        
        // Validasi input
        $validator = $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE
        );
        
        // Handle validation errors
        if ($validator->fails()) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'validation.failed',
                    params: [
                        'errors' => $validator->errors(),
                        'message' => 'Data yang dikirim tidak valid. Silakan periksa kembali.'
                    ]
                )
            );
        }
        
        // Buat command dengan data yang valid
        $command = CreateExampleCommand::create(
            name: $params['name'],
            status: (int) $params['status'],
            detailInfo: $params['detail_info'] ?? []
        );
        
        // Eksekusi business logic
        $result = $this->applicationService->create($command);
        
        return $this->responseFactory->success(
            data: $result->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: ['resource' => 'Example']
            )
        );
    }
}
```

Dengan menggunakan Input Validator ini, data input yang masuk ke sistem akan selalu valid dan konsisten sesuai dengan business rules yang didefinisikan.
