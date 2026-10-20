# Panduan Validasi

## 📋 Gambaran Umum

Validasi input di aplikasi ini dibangun di atas [`yiisoft/validator`](https://github.com/yiisoft/validator). Setiap modul API mendefinisikan sebuah `*InputValidator` yang meng-extend `App\Shared\Core\Validation\AbstractValidator` dan mendeklarasikan kumpulan rule per context (`CREATE`, `UPDATE`, `DELETE`, `SEARCH`, ...) menggunakan rule object Yiisoft. Saat gagal, sebuah `ValidationException` (HTTP 422) dengan daftar error yang ternormalisasi akan dilempar.

---

## 🏗️ Arsitektur Validasi

### Struktur Direktori

```
src/Shared/Core/Validation/
├── AbstractValidator.php              # Base validator wrapping Yiisoft\Validator
├── ValidationContextInterface.php     # Context constants (CREATE/UPDATE/DELETE/SEARCH/APPROVE/REJECT)
└── Rules/
    ├── HasNoDependencies.php          # Custom rule: record must not be referenced elsewhere
    ├── HasNoDependenciesHandler.php   # Rule handler (DB check)
    ├── UniqueValue.php                # Custom rule: value must be unique in a table column
    └── UniqueValueHandler.php         # Rule handler (DB check)

src/Shared/Common/Context/
└── ValidationContext.php              # Concrete context class (implements the interface constants)

src/Api/V1/{Module}/Validation/
└── {Module}InputValidator.php         # Per-module validators, e.g. ExampleInputValidator
```

### Prinsip Desain

#### **1. **Context Awareness**
- Rule validasi dipilih berdasarkan konstanta `ValidationContext`
- Rule berbeda per operasi (create vs update vs search)
- Context kustom dapat ditambahkan per modul

#### **2. **Fail-Fast**
- `validate()` melempar `ValidationException` pada payload invalid pertama
- Rule group `StopOnError` menghentikan rule sebuah field pada error pertama

#### **3. **Error Handling**
- Error dinormalisasi menjadi `[['field' => ..., 'message' => ...], ...]`
- `ValidationException` secara otomatis dipetakan ke HTTP 422
- Value object `Message` menyediakan key lokalisasi

#### **4. **Performance**
- Rule adalah objek biasa — murah untuk dibuat per request
- `skipOnEmpty` melewati rule mahal untuk nilai yang tidak ada
- `StopOnError` menghindari menjalankan rule berat (mis. lookup DB) ketika pengecekan murah sudah gagal

---

## 📁 Komponen Validasi

### 1. AbstractValidator

**Tujuan**: Base class untuk semua input validator. Membungkus `Yiisoft\Validator\ValidatorInterface`, menyediakan entry point `validate()` dan menormalisasi error.

**Lokasi**: `src/Shared/Core/Validation/AbstractValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\Validation;

use App\Shared\Core\Exception\ValidationException;
use App\Shared\Core\Request\RawParams;
use App\Shared\Core\ValueObject\LockVersionConfig;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\ValidatorInterface;

abstract class AbstractValidator
{
    protected array $data = [];
    protected mixed $id   = null;

    public function __construct(
        protected LockVersionConfig $lockVersionConfig,
        protected ValidatorInterface $validator
    ) {
    }

    /**
     * Validate $data against the rule set for $context.
     * Throws ValidationException (HTTP 422) when invalid.
     */
    final public function validate(string $context, RawParams $data): void
    {
        $this->data = $data->toArray();
        $this->id   = $this->data['id'] ?? null;

        $result = $this->validator->validate($this->data, $this->rules($context));

        if (!$result->isValid()) {
            throw new ValidationException($this->formatErrors($result));
        }
    }

    /** Normalizes Yiisoft errors to [['field' => 'a.b', 'message' => '...'], ...] */
    private function formatErrors(Result $result): array { /* ... */ }

    /** Whether optimistic locking is enabled for this validator (via LockVersionConfig). */
    protected function isOptimisticLockEnabled(): bool { /* ... */ }
    protected function shouldValidateOptimisticLock(): bool { /* ... */ }

    abstract protected function rules(string $context): array;
}
```

**Poin penting**:

- Signature `validate()`: `validate(string $context, RawParams $data): void` — perhatikan urutan argumennya: **context dulu, lalu objek `RawParams`** (action meneruskan `RawParams` yang sudah difilter, mis. `$params` dari `RequestParams`).
- `$this->data` menampung array payload; `$this->id` adalah `$data['id'] ?? null` — gunakan di rule seperti `UniqueValue(ignoreId: $this->data['id'] ?? null)` untuk mengecualikan record yang sedang di-update.
- `shouldValidateOptimisticLock()` membaca `LockVersionConfig` (params `app/optimisticLock`: `enabled` + `disabledValues`) — gunakan bersama `new Required(when: fn () => $this->shouldValidateOptimisticLock())` untuk `lock_version`.

---

### 2. ValidationContext

**Tujuan**: Konstanta yang memilih kumpulan rule mana yang diterapkan validator.

**Lokasi**: `src/Shared/Core/Validation/ValidationContextInterface.php` dan `src/Shared/Common/Context/ValidationContext.php`

```php
namespace App\Shared\Core\Validation;

interface ValidationContextInterface
{
    public const SEARCH  = 'search';
    public const CREATE  = 'create';
    public const UPDATE  = 'update';
    public const DELETE  = 'delete';
    public const APPROVE = 'approve';
    public const REJECT  = 'reject';
}
```

```php
namespace App\Shared\Common\Context;

use App\Shared\Core\Validation\ValidationContextInterface;

final class ValidationContext implements ValidationContextInterface
{
    // Constants are inherited from the interface.
    // Add module-specific contexts here when needed, e.g.:
    // public const CREATE_DO = 'create_do';
}
```

Gunakan `App\Shared\Common\Context\ValidationContext` di validator dan action:

```php
$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE,
);
```

---

### 3. Custom Rule

Dua custom rule `Yiisoft\Validator\RuleInterface` tersedia di `src/Shared/Core/Validation/Rules/`:

#### UniqueValue

Memeriksa bahwa nilai sebuah kolom belum ada di tabel.

```php
final class UniqueValue implements RuleInterface
{
    public function __construct(
        public string $table,
        public string $column,
        public mixed $ignoreId = null,     // exclude this id (for updates)
        public string $idColumn = 'id',
        public string $message = 'Data ini sudah ada di database.',
    ) {}

    public function getName(): string    { return 'uniqueValue'; }
    public function getHandler(): string { return UniqueValueHandler::class; }
}
```

#### HasNoDependencies

Memeriksa (saat delete) bahwa tidak ada baris tabel lain yang mereferensikan record tersebut. `map` berbentuk `table => [fk columns]`.

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class HasNoDependencies implements RuleInterface
{
    public function __construct(
        public array $map,
        public string $message = 'Data sedang digunakan di modul lain.',
    ) {}

    public function getName(): string    { return 'hasNoDependencies'; }
    public function getHandler(): string { return HasNoDependenciesHandler::class; }
}
```

**Registrasi handler** (`config/common/di/validator.php`):

```php
return [
    RuleHandlerResolverInterface::class => static fn (ContainerInterface $container) =>
        new SimpleRuleHandlerContainer([
            UniqueValueHandler::class => $container->get(UniqueValueHandler::class),
        ]),

    UniqueValueHandler::class => [
        '__construct()' => [
            'db'         => Reference::to(ConnectionInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],

    ValidatorInterface::class => Validator::class,
];
```

---

### 4. Module Input Validator

**Tujuan**: Validator konkret per modul yang mendeklarasikan rule per context.

**Contoh** (diringkas dari `src/Api/V1/Example/Validation/ExampleInputValidator.php`):

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
                            ignoreId: null
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
                            ignoreId: $this->data['id'] ?? null // exclude current record
                        ),
                    ]),
                ],
                'status' => [
                    new Integer(skipOnEmpty: true),
                    new In(RecordStatus::searchableStates()),
                ],
                'lock_version' => [
                    new Required(when: fn () => $this->shouldValidateOptimisticLock()),
                    new Integer(min: 1, skipOnEmpty: true),
                ],
            ],
            ValidationContext::DELETE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                    // Optionally guard the delete:
                    // new HasNoDependencies(map: ['other_table' => ['example_id']]),
                ],
            ],
            ValidationContext::SEARCH => [
                'id'        => [new Integer(skipOnEmpty: true)],
                'name'      => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 100, skipOnEmpty: true),
                ],
                'status'    => [new Integer(skipOnEmpty: true)],
                'sync_mdb'  => [new Integer(skipOnEmpty: true)],
                'page'      => [new Integer(min: 1, skipOnEmpty: true)],
                'page_size' => [new Integer(min: 1, max: 200, skipOnEmpty: true)],
                'sort_by'   => [new StringValue(skipOnEmpty: true)],
                'sort_dir'  => [new In(['asc', 'desc'], skipOnEmpty: true)],
            ],
            default => [],
        };
    }
}
```

---

## 🔧 Pola Integrasi

### 1. **Validasi di Action**

Action menerima objek `RequestParams` dari atribut request `payload` (diset oleh `RequestParamsMiddleware`), mempersempitnya dengan `onlyAllowed()`, lalu memvalidasinya.

**Create** (`src/Api/V1/Example/Action/ExampleCreateAction.php`):

```php
final class ExampleCreateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        // Throws ValidationException (HTTP 422) on failure
        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateExampleCommand::create(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info')
        );

        $response = $this->applicationService->create(command: $command);
        // ...
    }
}
```

**Update** (`ExampleUpdateAction.php`) — menggabungkan `id` dari route ke dalam payload agar `UniqueValue` dapat mengecualikan record saat ini:

```php
$id      = $currentRoute->getArgument('id');
$payload = $request->getAttribute('payload');

$payload->ensureExists(resource: $resource); // 400 when payload is empty

$params = $payload->getRawParams()
    ->onlyAllowed(allowedKeys: ['name', 'status', 'lock_version'])
    ->with('id', $id)
    ->sanitize();

$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::UPDATE,
);
```

**Search** (`ExampleDataAction.php`) — memvalidasi blok `filter`, bukan raw params:

```php
$filter = $payload->getFilter()
    ->onlyAllowed(allowedKeys: ['id', 'name', 'status'])
    ->with('status', RecordStatus::DRAFT->value);

$this->inputValidator->validate(
    data: $filter,
    context: ValidationContext::SEARCH,
);
```

### 2. **Validasi Optimistic Lock**

`lock_version` hanya wajib diisi ketika optimistic locking diaktifkan untuk validator (`LockVersionConfig`, params `app/optimisticLock`):

```php
'lock_version' => [
    new Required(when: fn () => $this->shouldValidateOptimisticLock()),
    new Integer(min: 1, skipOnEmpty: true),
],
```

### 3. **Proteksi Delete**

Mencegah penghapusan record yang masih direferensikan:

```php
ValidationContext::DELETE => [
    'id' => [
        new Required(),
        new Integer(min: 1),
        new HasNoDependencies(
            map: [
                'other_table'   => ['example_id'],
                'another_table' => ['example_id'],
            ],
            message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
        ),
    ],
],
```

---

## 🚀 Best Practices

### 1. **Desain Validator**
```php
// ✅ Extend AbstractValidator and declare rules per context
final class ProductInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [/* ... */],
            default => [],
        };
    }
}

// ❌ Ad-hoc validation scattered in actions
if (strlen($data['name']) < 3) { /* ... */ }
```

### 2. **Field Opsional**
```php
// ✅ Use skipOnEmpty for optional fields
'name' => [
    new StringValue(skipOnEmpty: true),
    new Length(min: 3, max: 255, skipOnEmpty: true),
],
```

### 3. **Stop On Error**
```php
// ✅ Wrap a field's rules in StopOnError to fail fast
//    (avoids hitting the DB in UniqueValue when Required already failed)
'name' => [
    new StopOnError([
        new Required(),
        new StringValue(),
        new UniqueValue(table: 'example', column: 'name'),
    ]),
],
```

### 4. **Error Handling**
```php
// ✅ Let validate() throw — the exception responder renders HTTP 422
$this->inputValidator->validate(data: $params, context: ValidationContext::CREATE);

// ❌ Catching and re-wrapping validation errors manually
try { /* validate */ } catch (ValidationException $e) { /* wrap */ }
```

---

## 📊 Pertimbangan Performa

### 1. **Overhead Validasi**
- Gunakan `StopOnError` agar pengecekan DB `UniqueValue`/`HasNoDependencies` hanya berjalan ketika rule murah lolos
- Gunakan `skipOnEmpty` untuk melewati rule pada field opsional yang tidak diisi

### 2. **Penggunaan Memori**
- Rule object dibuat per pemanggilan `rules()` — jaga agar tetap ringan
- `validate()` hanya menyimpan array payload di `$this->data`

### 3. **Kecepatan Pemrosesan**
- Letakkan rule murah (`Required`, `Integer`, `Length`) sebelum rule mahal di dalam `StopOnError`
- Batasi filter `SEARCH` dengan `onlyAllowed()` sebelum memvalidasi

---

## 🎯 Ringkasan

Validasi di aplikasi ini digerakkan oleh context dan dibangun di atas `yiisoft/validator`. Poin utamanya:

- **🔍 Extensibility**: Modul baru menambahkan `*InputValidator` yang meng-extend `AbstractValidator`
- **📝 Context Awareness**: Kumpulan rule dikunci oleh konstanta `ValidationContext`
- **🌐 Localization**: Error mengalir melalui key translasi `ValidationException`/`Message`
- **🧪 Testability**: Rule berupa array deklaratif — mudah di-assert per context
- **📦 Composability**: Gabungkan rule bawaan Yiisoft dengan custom `UniqueValue`/`HasNoDependencies`
- **⚡ Performance**: `StopOnError` + `skipOnEmpty` menjaga validasi tetap murah

Dengan mengikuti pola dan best practice yang diuraikan dalam panduan ini, Anda dapat membangun validasi yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
