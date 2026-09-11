# Value Objects Guide

## 📋 Overview

Value Objects provide immutable, type-safe representations of domain concepts with built-in validation. In this Yii3 API application, Value Objects ensure data integrity and prevent invalid states.

---

## 🏗️ Value Object Architecture

### Directory Structure

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

### Design Principles

#### **1. **Immutability**
- Readonly properties prevent modification
- No setter methods
- Safe sharing across the application

#### **2. **Type Safety**
- Strong typing with PHP 8+ features
- Validation in constructors
- Compile-time error detection

#### **3. **Value Semantics**
- Equality based on values, not identity
- No side effects
- Predictable behavior

#### **4. **Validation**
- Built-in validation logic
- Fail-fast construction
- Meaningful error messages

---

## 📁 Value Object Components

### 1. Message

**Purpose**: Translation message value object with localization support.
Carries a translation `key`, `params`, and an optional `domain` (message file:
`error`, `success`, `validation`, `app`). The translator resolves it inside
`ResponseFactory` and the HTTP exceptions.

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

**Usage Example**:
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

**Purpose**: Optimistic-locking version stored in the `lock_version` column.
Entities expose it via `getLockVersion()` (provided by the `Identifiable` trait).

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

Repositories verify/increment it through the `ManagesPersistence` trait
(`verifyLockVersion()`, `upgradeEntityLockVersion()`), driven by the
`app/optimisticLock` params and `LockVersionConfig` (`src/Shared/Core/ValueObject/`).

---

### 3. SyncMdb

**Purpose**: MongoDB sync marker for the `sync_mdb` column —
`null` = synced, `1` = pending.

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

The `HasMongoDBSync` repository trait sets `SyncMdb::pending()` when the MongoDB
write fails and `SyncMdb::synced()` when it succeeds.

---

### 4. SyncFlag (+ SyncStatus / SyncDirection enums)

**Purpose**: Master/origin sync state for entities such as `AnotherExample`
(columns `origin_id` + `sync_flag`). Replaces the former `SyncSlave` value object.

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

The enums (both in `App\Domain\Shared\Core\Enum`):

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

In application code, build `SyncFlag` through `SyncFlagFactory`
(`src/Application/Shared/Core/Factory/SyncFlagFactory.php`), which accepts raw
values and also offers `buildPayload()`, `buildSyncLog()`, `mergeIntoDetailInfo()`,
`shouldPushToOrigin()` / `shouldPushToMaster()` helpers.

---

### 5. ResourceStatus

**Purpose**: Rich status value object wrapping the `RecordStatus` enum —
encapsulates state checks and transition rules instead of raw integers.

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

**Purpose**: Generic JSON `detail_info` payload with built-in `change_log`
audit fields (created/updated/deleted/restored/approved/rejected at+by).

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

## 🔧 Custom Value Object Examples

### 1. Email Value Object

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\ValueObject;

final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address: ' . $value);
        }
        
        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Email address too long (max 255 characters)');
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

### 2. Money Value Object

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\ValueObject;

final readonly class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'USD'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        
        if (!in_array($this->currency, ['USD', 'EUR', 'GBP', 'JPY'], true)) {
            throw new \InvalidArgumentException('Invalid currency: ' . $this->currency);
        }
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
        }
        
        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies');
        }
        
        $newAmount = $this->amount - $other->amount;
        if ($newAmount < 0) {
            throw new \InvalidArgumentException('Resulting amount cannot be negative');
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
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare different currencies');
        }
        
        return $this->amount > $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
```

### 3. Address Value Object

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\ValueObject;

final readonly class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $postalCode,
        public readonly string $country
    ) {
        if (empty($this->street)) {
            throw new \InvalidArgumentException('Street is required');
        }
        
        if (empty($this->city)) {
            throw new \InvalidArgumentException('City is required');
        }
        
        if (empty($this->state)) {
            throw new \InvalidArgumentException('State is required');
        }
        
        if (empty($this->postalCode)) {
            throw new \InvalidArgumentException('Postal code is required');
        }
        
        if (empty($this->country)) {
            throw new \InvalidArgumentException('Country is required');
        }
        
        if (strlen($this->country) !== 2) {
            throw new \InvalidArgumentException('Country must be 2 characters (ISO 3166-1 alpha-2)');
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

## 🔧 Integration Patterns

### 1. **Entity Integration**
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

### 2. **Service Integration**
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

### 3. **Action Integration**
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

## 🚀 Best Practices

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
// ✅ Validate in constructor
final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email');
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

## 📊 Performance Considerations

### 1. **Memory Usage**
- Value objects are lightweight
- Readonly properties prevent copying
- Avoid large objects in value objects

### 2. **Construction Overhead**
- Validation adds minimal overhead
- Fail-fast prevents invalid states
- Cache frequently used objects

### 3. **Comparison Performance**
- Value comparison is fast
- Use built-in comparison when possible
- Avoid expensive operations in equals()

---

## 🎯 Summary

Value Objects provide immutable, type-safe representations of domain concepts in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: Strong typing prevents invalid states
- **🔄 Immutability**: Readonly properties ensure data integrity
- **✅ Validation**: Built-in validation in constructors
- **🧪 Testability**: Easy to unit test with predictable behavior
- **📦 Encapsulation**: Data and behavior together
- **🚀 Performance**: Efficient memory usage and comparison

By following the patterns and best practices outlined in this guide, you can build robust, maintainable value objects for your Yii3 API application! 🚀
