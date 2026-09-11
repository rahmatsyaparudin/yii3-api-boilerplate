# Panduan Input Validator

## Overview

Input Validator memvalidasi parameter request sebelum data masuk ke application service. Boilerplate ini memakai `AbstractValidator` bersama `Yiisoft\Validator\ValidatorInterface` dan `ValidationContext` untuk memilih rules berdasarkan operasi.

Lokasi validator per modul:

```text
src/Api/V1/{Module}/Validation/{Module}InputValidator.php
```

## API Utama

`AbstractValidator::validate()` memiliki signature:

```php
final public function validate(string $context, RawParams $data): void
```

Method ini tidak mengembalikan object validator dan tidak menyediakan `fails()`, `errors()`, atau `validated()`. Jika validasi gagal, method ini melempar `ValidationException` berisi daftar error yang sudah diformat.

## Struktur Dasar

```php
<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Validation;

use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\Validation\AbstractValidator;
use App\Shared\Core\Validation\Rules\UniqueValue;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StopOnError;
use Yiisoft\Validator\Rule\StringValue;

final class ExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(min: 3, max: 255),
                        new UniqueValue(
                            table: 'example',
                            column: 'name',
                            ignoreId: null,
                        ),
                    ]),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(RecordStatus::draftOnlyStates()),
                ],
            ],

            ValidationContext::UPDATE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'name' => [
                    new StopOnError([
                        new StringValue(skipOnEmpty: true),
                        new Length(min: 3, max: 255, skipOnEmpty: true),
                        new UniqueValue(
                            table: 'example',
                            column: 'name',
                            ignoreId: $this->data['id'] ?? null,
                        ),
                    ]),
                ],
                'lock_version' => [
                    new Required(
                        when: fn () => $this->shouldValidateOptimisticLock()
                    ),
                    new Integer(min: 1, skipOnEmpty: true),
                ],
            ],

            ValidationContext::DELETE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
            ],

            ValidationContext::SEARCH => [
                'page' => [new Integer(min: 1, skipOnEmpty: true)],
                'page_size' => [new Integer(min: 1, max: 200, skipOnEmpty: true)],
                'sort_dir' => [new In(['asc', 'desc'], skipOnEmpty: true)],
            ],

            default => [],
        };
    }
}
```

## Cara Penggunaan di Action

Validator menerima `RawParams`, bukan array mentah. Pada action, request payload biasanya sudah tersedia lewat attribute `payload`.

```php
/** @var \App\Shared\Core\Request\RequestParams $payload */
$payload = $request->getAttribute('payload');

$params = $payload->getRawParams()
    ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
    ->with('status', RecordStatus::DRAFT->value)
    ->sanitize();

$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE,
);
```

Karena `validate()` mengembalikan `void`, lanjutkan proses setelah method itu berjalan tanpa exception:

```php
$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE,
);

$command = CreateExampleCommand::create(
    name: (string) $params->get('name'),
    status: $params->get('status'),
    detailInfo: $params->get('detail_info'),
);
```

## Context yang Tersedia

`ValidationContext` mewarisi konstanta dari `ValidationContextInterface`:

- `ValidationContext::SEARCH` — `search`
- `ValidationContext::CREATE` — `create`
- `ValidationContext::UPDATE` — `update`
- `ValidationContext::DELETE` — `delete`
- `ValidationContext::APPROVE` — `approve`
- `ValidationContext::REJECT` — `reject`

Context kustom dapat ditambahkan di `ValidationContext` atau langsung memakai string context yang cocok dengan cabang `match` pada validator.

## Error Handling

Tidak perlu mengecek `$validator->fails()`. Jika rules gagal, `AbstractValidator` melempar:

```php
App\Shared\Core\Exception\ValidationException
```

Format error yang dihasilkan berupa array item:

```php
[
    'field' => 'name',
    'message' => 'Name is required.',
]
```

Exception ini dipetakan ke HTTP `422 Unprocessable Entity` dan biasanya ditangani oleh error-handling middleware/application layer. Tangani manual hanya jika action memerlukan respons khusus:

```php
try {
    $this->inputValidator->validate(
        data: $params,
        context: ValidationContext::CREATE,
    );
} catch (ValidationException $e) {
    // Custom handling jika benar-benar diperlukan.
    throw $e;
}
```

## Rules yang Umum Dipakai

### Rules Yii Validator

- `new Required()` — field wajib ada.
- `new Required(when: fn () => ...)` — wajib secara kondisional.
- `new Integer(min: 1, max: 200, skipOnEmpty: true)` — integer dengan batas optional.
- `new StringValue(skipOnEmpty: true)` — nilai harus string.
- `new Length(min: 3, max: 255, skipOnEmpty: true)` — panjang string.
- `new In([...], skipOnEmpty: true)` — nilai harus termasuk daftar yang diizinkan.
- `new StopOnError([...])` — hentikan rules berikutnya setelah error pertama.

### Rules Internal

#### `UniqueValue`

```php
new UniqueValue(
    table: 'example',
    column: 'name',
    ignoreId: $this->data['id'] ?? null,
    idColumn: 'id',
    message: 'Nama sudah digunakan.',
)
```

Rule ini memastikan nilai belum dipakai oleh record lain. Untuk operasi update, isi `ignoreId` dengan ID record yang sedang diupdate.

#### `HasNoDependencies`

```php
new HasNoDependencies(
    map: [
        'another_example' => ['example_id'],
    ],
    message: 'Data tidak bisa dihapus karena masih digunakan.',
)
```

Rule ini cocok untuk validasi `DELETE` ketika record tidak boleh memiliki dependensi pada tabel lain.

## Contoh Context

### CREATE

```php
ValidationContext::CREATE => [
    'name' => [
        new StopOnError([
            new Required(),
            new StringValue(),
            new Length(min: 3, max: 255),
            new UniqueValue(table: 'example', column: 'name'),
        ]),
    ],
    'status' => [
        new Required(),
        new Integer(),
        new In(RecordStatus::draftOnlyStates()),
    ],
],
```

### UPDATE

```php
ValidationContext::UPDATE => [
    'id' => [
        new Required(),
        new Integer(min: 1),
    ],
    'name' => [
        new StopOnError([
            new StringValue(skipOnEmpty: true),
            new Length(min: 3, max: 255, skipOnEmpty: true),
            new UniqueValue(
                table: 'example',
                column: 'name',
                ignoreId: $this->data['id'] ?? null,
            ),
        ]),
    ],
    'status' => [
        new Integer(skipOnEmpty: true),
        new In(RecordStatus::searchableStates()),
    ],
    'lock_version' => [
        new Required(
            when: fn () => $this->shouldValidateOptimisticLock()
        ),
        new Integer(min: 1, skipOnEmpty: true),
    ],
],
```

### SEARCH

```php
ValidationContext::SEARCH => [
    'id' => [new Integer(skipOnEmpty: true)],
    'name' => [
        new StringValue(skipOnEmpty: true),
        new Length(min: 1, max: 100, skipOnEmpty: true),
    ],
    'status' => [new Integer(skipOnEmpty: true)],
    'page' => [new Integer(min: 1, skipOnEmpty: true)],
    'page_size' => [new Integer(min: 1, max: 200, skipOnEmpty: true)],
    'sort_by' => [new StringValue(skipOnEmpty: true)],
    'sort_dir' => [new In(['asc', 'desc'], skipOnEmpty: true)],
],
```

### Field Sinkronisasi

Untuk modul yang memakai `origin_id` dan `sync_flag`, contoh pada `AnotherExampleInputValidator` adalah:

```php
'origin_id' => [
    new Integer(min: 1, skipOnEmpty: true),
],
'sync_flag' => [
    new Integer(skipOnEmpty: true),
    new In([1], skipOnEmpty: true),
],
```

Dalam model saat ini, `sync_flag = null` berarti sudah tersinkron dan `sync_flag = 1` berarti belum tersinkron. Nilai `null` tetap dapat dipakai untuk record yang datang dari master/cloud dan tidak perlu dikirim ulang.

## Contoh Lengkap di Create Action

```php
final class ExampleCreateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __construct(
        private ExampleInputValidator $inputValidator,
        private ExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Core\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateExampleCommand::create(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
        );

        $response = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $this->applicationService->getResource(),
                ],
            ),
        );
    }
}
```

## Best Practices

- Validasi dilakukan setelah `onlyAllowed()` agar parameter tidak dikenal ditolak lebih dulu.
- Gunakan `sanitize()` sebelum validasi untuk input user.
- Gunakan `StopOnError` untuk rangkaian rules yang mahal, misalnya sebelum `UniqueValue`.
- Gunakan `skipOnEmpty: true` untuk field optional pada update/search.
- Gunakan `ignoreId` pada `UniqueValue` saat update.
- Jangan membuat method `fails()`, `errors()`, atau `validated()` kecuali API `AbstractValidator` diubah; exception adalah mekanisme error saat ini.
