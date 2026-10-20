# Panduan Value Object

## 📋 Gambaran Umum

Value Object menyediakan representasi konsep domain yang immutable dan type-safe dengan validasi bawaan. Pada aplikasi Yii3 API ini, Value Object memastikan integritas data dan mencegah state yang tidak valid.

---

## 🏗️ Arsitektur Value Object

### Struktur Direktori

```
src/Shared/Core/ValueObject/
├── Message.php            # Translation message value object
└── LockVersionConfig.php  # Optimistic-lock configuration object

src/Domain/Shared/Core/ValueObject/
├── DetailInfo.php         # JSON detail_info payload with change_log helpers
├── LockVersion.php        # Optimistic locking version (lock_version column)
├── ResourceStatus.php     # Entity status value object (wraps RecordStatus enum)
├── SyncFlag.php           # Master/origin sync flag (origin_id + sync_flag + direction)
└── SyncMdb.php            # MongoDB sync flag (sync_mdb column)

src/Domain/Shared/Core/Enum/
├── SyncStatus.php         # sync_flag status enum (SYNCED → null, NOT_SYNCED → 1)
└── SyncDirection.php      # Sync direction enum (NONE/MASTER_TO_ORIGIN/ORIGIN_TO_MASTER/BIDIRECTIONAL)
```

### Prinsip Desain

#### **1. **Immutability**
- Properti readonly mencegah modifikasi
- Tidak ada method setter
- Aman dibagikan ke seluruh aplikasi

#### **2. **Type Safety**
- Strong typing dengan fitur PHP 8+
- Validasi di constructor
- Deteksi error pada compile-time

#### **3. **Value Semantics**
- Kesetaraan berdasarkan nilai, bukan identitas
- Tidak ada side effect
- Perilaku yang dapat diprediksi

#### **4. **Validation**
- Logika validasi bawaan
- Konstruksi fail-fast
- Pesan error yang bermakna

---

## 📁 Komponen Value Object

### 1. Message

**Tujuan**: Value object pesan terjemahan dengan dukungan lokalisasi.
Membawa `key` terjemahan, `params`, dan `domain` opsional (file pesan:
`error`, `success`, `validation`, `app`). Translator me-resolve-nya di dalam
`ResponseFactory` dan HTTP exception.

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\ValueObject;

final readonly class Message
{
    public function __construct(
        public string $key,
        public array $params = [],
        public ?string $domain = null
    ) {
    }

    public static function create(
        string $key,
        array $params = [],
        ?string $domain = null,
    ): self {
        return new self(domain: $domain, key: $key, params: $params);
    }

    public function getKey(): string
    {
        return $this->key;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }
}
```

**Contoh Penggunaan**:
```php
// Simple message (domain defaults to 'success' in ResponseFactory::success,
// 'error' in ResponseFactory::fail, or the exception's default)
$message = Message::create(key: 'resource.list_retrieved', params: [
    'resource' => 'Example',
]);

// Message for a specific domain — keys live in resources/messages/{en,id}/
$message = Message::create(
    domain: 'validation',
    key: 'resource.not_deleted',
    params: ['resource' => 'Example', 'id' => $id]
);

// In exception — all HttpException subclasses accept `translate: Message|string|null`
throw new NotFoundException(
    translate: Message::create(
        key: 'resource.not_found',
        params: ['resource' => 'Example', 'field' => 'id', 'value' => $id]
    )
);

// In response factory
return $this->responseFactory->success(
    data: $data,
    translate: Message::create(key: 'resource.created', params: [
        'resource' => 'Example'
    ])
);
```

---

### 2. LockVersion

**Tujuan**: Versi optimistic-locking yang disimpan di kolom `lock_version`.
Entity mengeksposnya melalui `getLockVersion()` (disediakan oleh trait `Identifiable`).

```php
use App\Domain\Shared\Core\ValueObject\LockVersion;

LockVersion::field();            // 'lock_version' (AppConstants::OPTIMISTIC_LOCK)
LockVersion::create();           // new version starting at 1 (DEFAULT_VALUE)
LockVersion::fromInt(3);         // from a database value
LockVersion::fromNullable(null); // defaults to 1 when null

$version->increment();           // new instance with value + 1
$version->value();               // int value (also toInt() / toString())
$version->isInitial();           // value === 1
$version->equals($other);        // value comparison
$version->isGreaterThan($other);
```

Repository memverifikasi/menambahnya melalui trait `ManagesPersistence`
(`verifyLockVersion()`, `upgradeEntityLockVersion()`), yang digerakkan oleh
params `app/optimisticLock` dan `LockVersionConfig` (`src/Shared/Core/ValueObject/`).

---

### 3. SyncMdb

**Tujuan**: Penanda sinkronisasi MongoDB untuk kolom `sync_mdb` —
`null` = tersinkron, `1` = pending.

```php
use App\Domain\Shared\Core\ValueObject\SyncMdb;

SyncMdb::field();          // 'sync_mdb' (AppConstants::SYNC_MONGODB)
SyncMdb::create($value);   // validates: only null or 1 allowed
SyncMdb::fromInt($value);  // alias of create()
SyncMdb::fromString($raw); // parses ''/numeric strings
SyncMdb::pending();        // value = 1 (needs sync)
SyncMdb::synced();         // value = null (synced)

$syncMdb->value();         // ?int raw column value (also toInt())
$syncMdb->isPending();     // value === 1
$syncMdb->isSynced();      // value === null
$syncMdb->isNull();        // same as isSynced()
$syncMdb->equals($other);
```

Trait repository `HasMongoDBSync` mengatur `SyncMdb::pending()` saat penulisan
MongoDB gagal dan `SyncMdb::synced()` saat berhasil.

---

### 4. SyncFlag (+ enum SyncStatus / SyncDirection)

**Tujuan**: State sinkronisasi master/origin untuk entity seperti `AnotherExample`
(kolom `origin_id` + `sync_flag`). Menggantikan value object `SyncSlave` lama.

```php
use App\Domain\Shared\Core\ValueObject\SyncFlag;
use App\Domain\Shared\Core\Enum\SyncDirection;
use App\Domain\Shared\Core\Enum\SyncStatus;

// Column names
SyncFlag::fieldOriginId();  // 'origin_id'  (AppConstants::ORIGIN_ID)
SyncFlag::fieldSyncFlag();  // 'sync_flag'  (AppConstants::SYNC_FLAG)

// Creation
SyncFlag::create(
    originId: 5,
    status: SyncStatus::NOT_SYNCED,          // default; direction auto-resolved
    direction: SyncDirection::ORIGIN_TO_MASTER // optional explicit direction
);
SyncFlag::fromArray($row);        // from a DB row / request array
SyncFlag::fromEntity($entity);    // from an entity with getOriginId()/getSyncFlagValue()
SyncFlag::masterToOrigin(5);      // push master → origin
SyncFlag::originToMaster(5);      // push origin → master (originId required)
SyncFlag::bidirectional(5);       // both directions
SyncFlag::synced();               // status SYNCED, direction NONE

// Reading
$flag->getOriginId();     // ?int
$flag->getSyncStatus();   // SyncStatus enum
$flag->getSyncFlag();     // ?int raw column value (null=synced, 1=not synced)
$flag->getDirection();    // SyncDirection enum
$flag->isPending();       // status === NOT_SYNCED
$flag->isSynced();
$flag->isMasterToOrigin();   // MASTER_TO_ORIGIN or BIDIRECTIONAL
$flag->isOriginToMaster();   // ORIGIN_TO_MASTER or BIDIRECTIONAL
$flag->isBidirectional();
$flag->needsSyncToOrigin();  // pending && master→origin
$flag->needsSyncToMaster();  // pending && origin→master

// Immutable transitions
$flag->markForSync();          // status → NOT_SYNCED
$flag->markSynced();           // status → SYNCED
$flag->withOriginId(7);        // change origin, direction re-resolved
$flag->withDirection(SyncDirection::BIDIRECTIONAL);
$flag->toArray();              // origin_id + sync_flag + direction
$flag->toDbArray();            // origin_id + sync_flag only (for persistence)
$flag->equals($other);
```

Kedua enum (keduanya di `App\Domain\Shared\Core\Enum`):

```php
// Pure enum — DB value for the sync_flag column
SyncStatus::SYNCED->dbValue();      // null
SyncStatus::NOT_SYNCED->dbValue();  // 1
SyncStatus::fromDbValue(null);      // SyncStatus::SYNCED (throws BadRequestException otherwise)
$status->label();                   // 'Synced' | 'Not Synced'
$status->isSynced();  $status->isPending();

// int-backed enum — sync direction
SyncDirection::NONE->value;             // 0
SyncDirection::MASTER_TO_ORIGIN->value; // 1
SyncDirection::ORIGIN_TO_MASTER->value; // 2
SyncDirection::BIDIRECTIONAL->value;    // 3
SyncDirection::fromValue(3);            // SyncDirection::BIDIRECTIONAL (throws otherwise)
$direction->label();                    // 'Master to Origin', ...
```

Dalam kode aplikasi, bangun `SyncFlag` melalui `SyncFlagFactory`
(`src/Application/Shared/Core/Factory/SyncFlagFactory.php`), yang menerima nilai
mentah dan juga menawarkan helper `buildPayload()`, `buildSyncLog()`, `mergeIntoDetailInfo()`,
`shouldPushToOrigin()` / `shouldPushToMaster()`.

---

### 5. ResourceStatus

**Tujuan**: Value object status yang kaya yang membungkus enum `RecordStatus` —
mengenkapsulasi pemeriksaan state dan aturan transisi alih-alih integer mentah.

```php
use App\Domain\Shared\Core\ValueObject\ResourceStatus;

// Named constructors
ResourceStatus::draft();      ResourceStatus::active();
ResourceStatus::inactive();   ResourceStatus::restored();   // → INACTIVE
ResourceStatus::deleted();    ResourceStatus::completed();
ResourceStatus::maintenance(); ResourceStatus::approved();  ResourceStatus::rejected();

// From raw values (delegates to RecordStatus)
ResourceStatus::from($command->status);        // int|string
ResourceStatus::tryFrom($command->status);     // ?self — null-safe

// State checks & rules
$status->value();          // int (RecordStatus value)
$status->name();           // enum name, e.g. 'ACTIVE'
$status->label();          // 'Active', ...
$status->isActive();       // + isDraft/isInactive/isCompleted/isDeleted
$status->canBeUpdated();   // not in RecordStatus::IMMUTABLE_STATUSES
$status->canBeDeleted();   // not active
$status->isLocked();       // ACTIVE/COMPLETED/DELETED/REJECTED
$status->isValidForCreation(); // active or draft
$status->canTransitionTo($newStatus); // STATUS_TRANSITION_MAP
$status->equals($other);
$status->toArray();        // ['value' => ..., 'name' => ..., 'label' => ...]
```

---

### 6. DetailInfo

**Tujuan**: Payload JSON `detail_info` generik dengan field audit `change_log`
bawaan (created/updated/deleted/restored/approved/rejected at+by).

```php
use App\Domain\Shared\Core\ValueObject\DetailInfo;

// Usually built via DetailInfoFactory (injects clock + current user):
$detailInfo = $this->detailInfoFactory->create(detailInfo: [])->build();

// Direct creation
DetailInfo::fromArray(['key' => 'value']);
DetailInfo::fromJson($row['detail_info']); // tolerant — invalid JSON → empty

// change_log helpers (used by DetailInfoFactory)
DetailInfo::createdLog($dateTime, $user, $payload);
DetailInfo::updatedLog($dateTime, $user, $currentLog, $payload);
DetailInfo::deletedLog($dateTime, $user, $currentLog, $payload);
DetailInfo::restoredLog($dateTime, $user, $currentLog, $payload);
DetailInfo::approvedLog($dateTime, $user, $currentLog, $payload);
DetailInfo::rejectedLog($dateTime, $user, $currentLog, $payload);

// Access
$detailInfo->get('change_log.created_by', 'default'); // dot-notation
$detailInfo->has('key');
$detailInfo->with(['approved_at' => null]);           // merge into change_log
$detailInfo->toArray();
$detailInfo->toJson();
```

---

## 🔧 Contoh Value Object Kustom

### 1. Value Object Email

```php
<?php

declare(strict_types=1);

namespace App\Shared\Common\ValueObject;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'format.email',
                    params: ['field' => 'email']
                )
            );
        }

        if (strlen($value) > 255) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'length.max',
                    params: ['field' => 'email', 'max' => 255]
                )
            );
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0];
    }

    public function getDomain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function isFromDomain(string $domain): bool
    {
        return strtolower($this->getDomain()) === strtolower($domain);
    }

    public function equals(Email $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }
}
```

### 2. Value Object Money

```php
<?php

declare(strict_types=1);

namespace App\Shared\Common\ValueObject;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

final readonly class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'USD'
    ) {
        if ($amount < 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'range.min',
                    params: ['field' => 'amount', 'min' => 0]
                )
            );
        }

        if (!in_array($this->currency, ['USD', 'EUR', 'GBP', 'JPY'], true)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'request.invalid_parameter',
                    params: ['param' => 'currency']
                )
            );
        }
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function add(Money $other): Money
    {
        $this->assertSameCurrency($other, 'add');

        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        $this->assertSameCurrency($other, 'subtract');

        $newAmount = $this->amount - $other->amount;
        if ($newAmount < 0) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'business.violation',
                    params: ['reason' => 'Resulting amount cannot be negative']
                )
            );
        }

        return new Money($newAmount, $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        $newAmount = (int) round($this->amount * $multiplier);
        return new Money($newAmount, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        $this->assertSameCurrency($other, 'compare');

        return $this->amount > $other->amount;
    }

    private function assertSameCurrency(Money $other, string $operation): void
    {
        if ($this->currency !== $other->currency) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'business.violation',
                    params: ['reason' => "Cannot {$operation} different currencies"]
                )
            );
        }
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
```

### 3. Value Object Address

```php
<?php

declare(strict_types=1);

namespace App\Shared\Common\ValueObject;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

final readonly class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $postalCode,
        public readonly string $country
    ) {
        foreach (['street', 'city', 'state', 'postalCode', 'country'] as $field) {
            if (empty($this->{$field})) {
                throw new BadRequestException(
                    translate: Message::create(
                        key: 'request.missing_parameter',
                        params: ['param' => $field]
                    )
                );
            }
        }

        if (strlen($this->country) !== 2) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'business.violation',
                    params: ['reason' => 'Country must be 2 characters (ISO 3166-1 alpha-2)']
                )
            );
        }
    }

    public function getFullAddress(): string
    {
        return implode(', ', [
            $this->street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country
        ]);
    }

    public function isInCountry(string $country): bool
    {
        return strtoupper($this->country) === strtoupper($country);
    }

    public function equals(Address $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }
}
```

---

## 🔧 Pola Integrasi

### 1. **Integrasi Entity**
```php
// src/Domain/AnotherExample/Entity/AnotherExample.php (simplified) —
// entities hold value objects, not raw column values
final class AnotherExample
{
    use Identifiable, Stateful, Descriptive; // traits provide id/name/status/detailInfo/
                                           // syncMdb/lockVersion accessors

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $exampleId,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        private ?SyncFlag $syncFlag = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource    = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public function getSyncFlag(): ?SyncFlag
    {
        return $this->syncFlag;
    }

    public function getOriginId(): ?int
    {
        return $this->syncFlag?->getOriginId();
    }

    public function updateSyncFlag(?SyncFlag $syncFlag): void
    {
        $this->syncFlag = $syncFlag;
    }
}
```

### 2. **Integrasi Service**
```php
// src/Application/AnotherExample/AnotherExampleApplicationService.php (simplified)
public function create(CreateAnotherExampleCommand $command): AnotherExampleResponse
{
    $detailInfo = $this->detailInfoFactory
        ->create(detailInfo: $detailInfoPayload)
        ->build();

    // Build the SyncFlag value object from raw command values
    $syncFlag = $this->syncFlagFactory->create(
        originId: $command->originId,
        syncFlag: $command->syncFlag ?? 1,
    );

    $data = AnotherExample::create(
        name: $command->name,
        status: ResourceStatus::from($command->status), // raw int → VO
        detailInfo: $detailInfo,
        exampleId: $command->exampleId,
        syncFlag: $syncFlag,
    );

    return AnotherExampleResponse::fromEntity(
        entity: $this->repository->insert(entity: $data)
    );
}
```

### 3. **Integrasi Action**
```php
// src/Api/V1/AnotherExample/Action/AnotherExampleCreateAction.php (simplified)
public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    /** @var RequestParams $payload */
    $payload = $request->getAttribute('payload');

    $params = $payload->getRawParams()
        ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
        ->with('status', RecordStatus::DRAFT->value)
        ->sanitize();

    $this->inputValidator->validate(data: $params, context: ValidationContext::CREATE);

    $command = CreateAnotherExampleCommand::create(
        name: (string) $params->get('name'),
        status: $params->get('status'),
        exampleId: (int) $params->get('example_id'),
        detailInfo: $params->get('detail_info'),
        originId: $params->get('origin_id'),
        syncFlag: $params->get('sync_flag'),
    );

    $response = $this->applicationService->create(command: $command);

    return $this->responseFactory->success(
        data: $response->toArray(),
        translate: Message::create(key: 'resource.created', params: [
            'resource' => $this->applicationService->getResource(),
        ])
    );
}
```

---

## 🚀 Praktik Terbaik

### 1. **Immutability**
```php
// ✅ Use readonly properties
final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {}
}

// ❌ Use mutable properties
class Email
{
    public string $value;
}
```

### 2. **Validation**
```php
// ✅ Validate in constructor — throw the template's HttpException
//    subclasses with Message::create, never raw \InvalidArgumentException
final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'format.email',
                    params: ['field' => 'email']
                )
            );
        }
    }
}

// ❌ Validate separately
$email = new Email($value);
if (!$email->isValid()) {
    // Validation should be in constructor
}
```

### 3. **Equality**
```php
// ✅ Value-based equality
public function equals(Email $other): bool
{
    return strtolower($this->value) === strtolower($other->value);
}

// ❌ Identity-based equality
public function equals(Email $other): bool
{
    return $this === $other;
}
```

---

## 📊 Pertimbangan Performa

### 1. **Penggunaan Memori**
- Value object ringan
- Properti readonly mencegah penyalinan
- Hindari objek besar dalam value object

### 2. **Overhead Konstruksi**
- Validasi menambah overhead minimal
- Fail-fast mencegah state tidak valid
- Cache objek yang sering digunakan

### 3. **Performa Perbandingan**
- Perbandingan nilai cepat
- Gunakan perbandingan bawaan jika memungkinkan
- Hindari operasi mahal di equals()

---

## 🎯 Ringkasan

Value Object menyediakan representasi konsep domain yang immutable dan type-safe dalam aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🛡️ Type Safety**: Strong typing mencegah state tidak valid
- **🔄 Immutability**: Properti readonly memastikan integritas data
- **✅ Validation**: Validasi bawaan di constructor
- **🧪 Testability**: Mudah di-unit test dengan perilaku yang dapat diprediksi
- **📦 Encapsulation**: Data dan perilaku bersama-sama
- **🚀 Performance**: Penggunaan memori dan perbandingan yang efisien

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun value object yang kuat dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
