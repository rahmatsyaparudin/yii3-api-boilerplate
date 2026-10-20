# Panduan Enum

## 📋 Gambaran Umum

Enumeration (Enum) menyediakan cara untuk mendefinisikan sekumpulan konstanta bernama yang merepresentasikan sekumpulan nilai tetap. Di aplikasi Yii3 API ini, enum digunakan untuk mendefinisikan nilai status record, state sinkronisasi, dan konstanta field-name/pattern bersama yang dipakai lintas layer.

---

## 🏗️ Arsitektur Enum

### Struktur Direktori

```
src/Shared/Core/Enums/
├── AppConstants.php    # Application-wide constants (field names, patterns, filters)
└── RecordStatus.php     # Record status enumeration

src/Domain/Shared/Core/Enum/
├── SyncStatus.php       # sync_flag status: SYNCED (db null) / NOT_SYNCED (db 1)
└── SyncDirection.php    # sync direction: NONE / MASTER_TO_ORIGIN / ORIGIN_TO_MASTER / BIDIRECTIONAL
```

> Lihat `docs/22-sync-flag-guide.md` untuk detail lengkap modul sinkronisasi (`origin_id`/`sync_flag`).

### Prinsip Desain

#### **1. **Type Safety**
- Fitur enum PHP 8.1+ untuk pengetikan kuat
- Mencegah nilai yang tidak valid
- Memungkinkan auto-completion di IDE

#### **2. **Immutability**
- Enum bersifat immutable secara alami
- Mencegah perubahan state yang tidak disengaja
- Menjamin konsistensi

#### **3. **Self-Documentation**
- Konstanta bernama sudah menjelaskan dirinya sendiri
- Mengurangi magic number dan magic string
- Meningkatkan keterbacaan kode

#### **4. **Extensibility**
- Mudah menambahkan nilai baru
- Perubahan yang backward compatible
- Pengelolaan terpusat

---

## 📁 Komponen Enum

### 1. AppConstants

**Tujuan**: Konstanta terpusat lingkup aplikasi — field name untuk sinkronisasi/optimistic locking, pattern validasi desimal, dan kondisi query "not deleted" yang dapat dipakai ulang.

**Lokasi**: `src/Shared/Core/Enums/AppConstants.php`

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\Enums;

final class AppConstants
{
    // Synchronization / Locking field names
    public const OPTIMISTIC_LOCK = 'lock_version'; // optimistic locking version field
    public const SYNC_MONGODB    = 'sync_mdb';     // MongoDB sync identifier
    public const SYNC_MASTER     = 'sync_master';  // master sync flag
    public const SYNC_FLAG       = 'sync_flag';    // sync flag field (null: synced, 1: not synced)
    public const ORIGIN_ID       = 'origin_id';    // origin instance id
    public const MASTER_ID       = 'master_id';    // master record id

    // Validation patterns
    public const DECIMAL_PATTERN = '/^\d+(\.\d{1,2})?$/';

    /**
     * Query condition for "status is not deleted".
     * Generates: ['<>', 'status', RecordStatus::DELETED->value]
     */
    public static function statusNotDeleted(): array
    {
        return ['<>', 'status', RecordStatus::DELETED->value];
    }
}
```

**Contoh Penggunaan**:
```php
use App\Shared\Core\Enums\AppConstants;

// Optimistic locking — read the version field by its constant name
$lockVersion = $params->get(AppConstants::OPTIMISTIC_LOCK);

// Decimal validation pattern
if (!preg_match(AppConstants::DECIMAL_PATTERN, $amount)) {
    throw new ValidationException(errors: ['amount' => ['Invalid decimal format']]);
}

// Exclude soft-deleted rows in repository queries
$query->andWhere(AppConstants::statusNotDeleted());
// Equivalent to: ['<>', 'status', 4]
```

---

### 2. RecordStatus

**Tujuan**: Enum int-backed untuk nilai status record. Disimpan di kolom `status` (smallint) dan dibungkus oleh value object `ResourceStatus` di domain entity.

**Lokasi**: `src/Shared/Core/Enums/RecordStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\Enums;

enum RecordStatus: int
{
    case INACTIVE    = 0;
    case ACTIVE      = 1;
    case DRAFT       = 2;
    case COMPLETED   = 3;
    case DELETED     = 4;
    case MAINTENANCE = 5;
    case APPROVED    = 6;
    case REJECTED    = 7;

    /**
     * Immutable statuses that cannot be changed once set.
     */
    public const IMMUTABLE_STATUSES = [
        self::ACTIVE->value,
        self::COMPLETED->value,
        self::DELETED->value,
    ];

    /**
     * Allowed status transitions.
     * Key: current status value. Value: allowed target status values.
     */
    public const STATUS_TRANSITION_MAP = [
        self::DRAFT->value => [
            self::DRAFT->value,
            self::INACTIVE->value,
            self::ACTIVE->value,
            self::DELETED->value,
            self::MAINTENANCE->value,
        ],
        self::ACTIVE->value => [
            self::COMPLETED->value,
            self::APPROVED->value,
            self::REJECTED->value,
        ],
        self::INACTIVE->value => [
            self::INACTIVE->value,
            self::ACTIVE->value,
            self::DRAFT->value,
            self::DELETED->value,
        ],
        self::MAINTENANCE->value => [
            self::MAINTENANCE->value,
            self::INACTIVE->value,
            self::ACTIVE->value,
            self::DRAFT->value,
            self::DELETED->value,
        ],
        self::APPROVED->value => [
            self::APPROVED->value,
            self::COMPLETED->value,
            self::REJECTED->value,
        ],
        self::DELETED->value => [
            self::INACTIVE->value,
        ],
    ];

    /**
     * Human-readable label: 'Inactive', 'Active', 'Draft', 'Completed',
     * 'Deleted', 'Maintenance', 'Approved', 'Rejected'
     */
    public function label(): string
    {
        return match ($this) {
            self::INACTIVE    => 'Inactive',
            self::ACTIVE      => 'Active',
            self::DRAFT       => 'Draft',
            self::COMPLETED   => 'Completed',
            self::DELETED     => 'Deleted',
            self::MAINTENANCE => 'Maintenance',
            self::APPROVED    => 'Approved',
            self::REJECTED    => 'Rejected',
        };
    }

    /** Only the ACTIVE value — for filtering active-only records. */
    public static function activeOnlyStates(): array
    {
        return [self::ACTIVE->value];
    }

    /** Only the DRAFT value — for filtering draft records. */
    public static function draftOnlyStates(): array
    {
        return [self::DRAFT->value];
    }

    /** Map of status value => label (e.g. for dropdown options). */
    public static function list(): array
    {
        return \array_reduce(
            self::cases(),
            static function (array $carry, self $status) {
                $carry[$status->value] = $status->label();

                return $carry;
            },
            []
        );
    }

    /** All status values except DELETED — safe for public search/list endpoints. */
    public static function searchableStates(): array
    {
        $states = [];
        foreach (self::cases() as $status) {
            if ($status !== self::DELETED) {
                $states[] = $status->value;
            }
        }

        return $states;
    }
}
```

**Contoh Penggunaan**:
```php
use App\Shared\Core\Enums\RecordStatus;

// Creating status (int-backed)
$status = RecordStatus::ACTIVE;
$status = RecordStatus::from(1);        // RecordStatus::ACTIVE
$status = RecordStatus::tryFrom(2);     // RecordStatus::DRAFT (null if invalid)

// Status information
$label = $status->label();              // "Active"

// Status groups for queries and validation rules
RecordStatus::activeOnlyStates();       // [1]
RecordStatus::draftOnlyStates();        // [2]
RecordStatus::searchableStates();       // [0, 1, 2, 3, 5, 6, 7] — no DELETED
RecordStatus::list();                   // [0 => 'Inactive', 1 => 'Active', ...]

// In Yiisoft Validator rules (see ExampleInputValidator)
new In(RecordStatus::draftOnlyStates())    // CREATE: only draft allowed
new In(RecordStatus::searchableStates())   // UPDATE: any non-deleted status

// Transition validation
$allowed = RecordStatus::STATUS_TRANSITION_MAP[$current->value] ?? [];
if (!in_array($newStatus->value, $allowed, true)) {
    throw new BadRequestException(translate: Message::create(key: 'status.invalid_transition'));
}

// Immutability check
$isFinal = in_array($status->value, RecordStatus::IMMUTABLE_STATUSES, true);
```

> **Catatan**: Domain entity tidak mengekspos `RecordStatus` secara langsung. Entity membungkusnya dalam
> `App\Domain\Shared\Core\ValueObject\ResourceStatus`, yang menambahkan perilaku bisnis
> (`canTransitionTo()`, `canBeDeleted()`, `isLocked()`, `isActive()`, `isDeleted()`,
> `restored()`, dll.) di atas enum. Gunakan `ResourceStatus` di dalam domain layer
> dan `RecordStatus` untuk nilai DB, rule validasi, dan daftar filter.

---

### 3. SyncStatus

**Tujuan**: Enum murni (non-backed) yang merepresentasikan state kolom `sync_flag` untuk sinkronisasi master–origin. Representasi DB: `null` = synced, `1` = not synced (default `1`).

**Lokasi**: `src/Domain/Shared/Core/Enum/SyncStatus.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Enum;

enum SyncStatus
{
    case SYNCED;
    case NOT_SYNCED;

    /** Value stored in the sync_flag column: SYNCED => null, NOT_SYNCED => 1. */
    public function dbValue(): ?int
    {
        return match ($this) {
            self::SYNCED     => null,
            self::NOT_SYNCED => 1,
        };
    }

    /** Build SyncStatus from the raw sync_flag value. Throws BadRequestException otherwise. */
    public static function fromDbValue(?int $value): self
    {
        return match ($value) {
            null    => self::SYNCED,
            1       => self::NOT_SYNCED,
            default => throw new BadRequestException(/* ... */),
        };
    }

    public function label(): string     // 'Synced' / 'Not Synced'
    public function isSynced(): bool    // $this === self::SYNCED
    public function isPending(): bool   // $this === self::NOT_SYNCED
}
```

**Contoh Penggunaan**:
```php
use App\Domain\Shared\Core\Enum\SyncStatus;

// From a DB row
$status = SyncStatus::fromDbValue($row['sync_flag']);

if ($status->isPending()) {
    // record still needs to be synchronized
}

// Writing to DB
$syncFlag = SyncStatus::NOT_SYNCED->dbValue(); // 1
$syncFlag = SyncStatus::SYNCED->dbValue();     // null
```

---

### 4. SyncDirection

**Tujuan**: Enum int-backed yang mendeskripsikan arah sinkronisasi record antara instance master dan origin.

**Lokasi**: `src/Domain/Shared/Core/Enum/SyncDirection.php`

```php
<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Enum;

enum SyncDirection: int
{
    case NONE             = 0;
    case MASTER_TO_ORIGIN = 1;
    case ORIGIN_TO_MASTER = 2;
    case BIDIRECTIONAL    = 3;

    /** Build from raw value. Throws BadRequestException for values outside 0–3. */
    public static function fromValue(int $value): self
    {
        return self::tryFrom($value) ?? throw new BadRequestException(/* ... */);
    }

    public function label(): string
    {
        return match ($this) {
            self::NONE             => 'None',
            self::MASTER_TO_ORIGIN => 'Master to Origin',
            self::ORIGIN_TO_MASTER => 'Origin to Master',
            self::BIDIRECTIONAL    => 'Bidirectional',
        };
    }
}
```

**Contoh Penggunaan**:
```php
use App\Domain\Shared\Core\Enum\SyncDirection;

$direction = SyncDirection::fromValue((int) $row['sync_direction']);

if ($direction === SyncDirection::ORIGIN_TO_MASTER) {
    // push this record's changes up to the master
}
```

---

## 🔧 Pola Integrasi

### 1. **Penggunaan di Entity**
```php
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Shared\Core\Enums\RecordStatus;

final class Example
{
    private function __construct(
        private readonly ?int $id,
        private string $name,
        private ResourceStatus $status, // VO wrapping RecordStatus
    ) {}

    public static function create(string $name, ResourceStatus $status, /* ... */): self
    {
        // guardInitialStatus() restricts which statuses are valid at creation
        return new self(id: null, name: $name, status: $status /* ... */);
    }

    public function changeStatus(ResourceStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new BadRequestException(translate: Message::create(key: 'status.invalid_transition'));
        }

        $this->status = $newStatus;
    }
}
```

### 2. **Penggunaan di Repository**
```php
use App\Shared\Core\Enums\RecordStatus;

final class ExampleRepository
{
    // HasCoreFeatures trait provides scopeWhereNotDeleted():
    // ['<>', 'status', RecordStatus::DELETED->value]
    public function findById(int $id): ?Example
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereNotDeleted())
            ->one();

        return $row ? Example::reconstitute(/* ... */) : null;
    }

    // Filter by a group of status values
    public function findByStatuses(array $statuses): array
    {
        return (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['status' => $statuses])
            ->all();
    }

    public function findPublic(): array
    {
        return $this->findByStatuses(RecordStatus::searchableStates());
    }
}
```

### 3. **Penggunaan di Validasi**
```php
// src/Api/V1/Example/Validation/ExampleInputValidator.php
protected function rules(string $context): array
{
    return match ($context) {
        ValidationContext::CREATE => [
            'status' => [
                new Required(),
                new Integer(),
                new In(RecordStatus::draftOnlyStates()), // new records start as DRAFT
            ],
        ],
        ValidationContext::UPDATE => [
            'status' => [
                new Integer(skipOnEmpty: true),
                new In(RecordStatus::searchableStates()), // any non-deleted status
            ],
        ],
        default => [],
    };
}
```

### 4. **Penggunaan di Action**
```php
// src/Api/V1/Example/Action/ExampleCreateAction.php
$params = $payload->getRawParams()
    ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
    ->with('status', RecordStatus::DRAFT->value) // force DRAFT on create
    ->sanitize();

$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE,
);
```

---

## 🚀 Best Practices

### 1. **Type Safety**
```php
// ✅ Use the enum / value object types
public function __construct(
    private ResourceStatus $status
) {}

// ❌ Avoid raw ints
public function __construct(
    private int $status
) {}
```

### 2. **Validation**
```php
// ✅ Validate against enum-provided state lists
new In(RecordStatus::searchableStates())

// ❌ Avoid hardcoded lists
new In([0, 1, 2, 3, 5, 6, 7])
```

### 3. **Penggunaan Konstanta**
```php
// ✅ Use constants for shared field names
$version = $params->get(AppConstants::OPTIMISTIC_LOCK);
$query->andWhere(AppConstants::statusNotDeleted());

// ❌ Avoid magic strings
$version = $params->get('lock_version');
$query->andWhere(['<>', 'status', 4]);
```

### 4. **Transisi Status**
```php
// ✅ Use the transition map / ResourceStatus
$allowed = RecordStatus::STATUS_TRANSITION_MAP[$current->value] ?? [];
if ($status->canTransitionTo($newStatus)) {
    // allow transition
}

// ❌ Avoid manual transition logic
if ($current === 1 && $new === 0) {
    // allow transition
}
```

---

## 📊 Pertimbangan Performa

### 1. **Penggunaan Memori**
- Enum hemat memori (singleton per case)
- Konstanta dimuat sekali
- Hindari pembuatan objek yang tidak perlu

### 2. **Query Database**
- Gunakan `->value` enum di query database (`status` adalah kolom smallint)
- Beri index pada kolom `status` untuk performa
- Gunakan `searchableStates()` / `statusNotDeleted()` untuk memfilter berdasarkan grup status

### 3. **Caching**
- `RecordStatus::list()` murah untuk dihitung; cache hanya jika sering dirender
- Simpan nilai enum (int) di DB, bukan labelnya

---

## 🎯 Ringkasan

Enum menyediakan cara yang type-safe dan self-documenting untuk mendefinisikan konstanta dan nilai status di aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🛡️ Type Safety**: Pengetikan kuat mencegah nilai yang tidak valid
- **📖 Self-Documentation**: Konstanta bernama sudah menjelaskan dirinya sendiri
- **🔄 Immutability**: Enum bersifat immutable secara alami
- **🧪 Testability**: Mudah di-unit test dengan perilaku yang dapat diprediksi
- **📦 Centralized Management**: Semua konstanta di satu tempat
- **🚀 Performance**: Penggunaan memori efisien dan akses cepat

Dengan mengikuti pola dan best practice yang diuraikan dalam panduan ini, Anda dapat membangun enum yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
