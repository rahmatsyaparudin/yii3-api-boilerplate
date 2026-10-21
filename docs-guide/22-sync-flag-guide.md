# Panduan Sync Flag (`origin_id` + `sync_flag`)

## Overview

Modul `SyncFlag` adalah fondasi sinkronisasi antar-instance (master ↔ origin/store) dengan topologi **hub-and-spoke**: store tidak pernah sync langsung satu sama lain — semua lewat cloud/master sebagai relay.

Dua kolom yang dibawa setiap record yang ikut sync:

| Kolom | Tipe | Default | Arti |
|-------|------|---------|------|
| `origin_id` | `integer`, nullable | `null` | Id origin/store tujuan pengiriman berikutnya |
| `sync_flag` | `smallint`, nullable | `1` | `null` = synced, `1` = not synced (pending) |

Ditambah `direction` (tidak dipersist sebagai kolom; di-derive atau dibawa di payload queue).

## Komponen

```
src/Domain/Shared/Core/Enum/SyncStatus.php       — SYNCED (null) / NOT_SYNCED (1)
src/Domain/Shared/Core/Enum/SyncDirection.php    — NONE / MASTER_TO_ORIGIN / ORIGIN_TO_MASTER / BIDIRECTIONAL
src/Domain/Shared/Core/ValueObject/SyncFlag.php  — VO immutable
src/Application/Shared/Core/Factory/SyncFlagFactory.php — factory + payload/log builder
```

### `SyncStatus`

```php
use App\Domain\Shared\Core\Enum\SyncStatus;

SyncStatus::SYNCED->dbValue();      // null
SyncStatus::NOT_SYNCED->dbValue();  // 1
SyncStatus::fromDbValue(null);      // SyncStatus::SYNCED
SyncStatus::fromDbValue(7);         // BadRequestException (sync_flag.invalid_value)
```

### `SyncDirection`

```php
use App\Domain\Shared\Core\Enum\SyncDirection;

SyncDirection::MASTER_TO_ORIGIN->value;  // 1
SyncDirection::fromValue(9);             // BadRequestException (sync_flag.invalid_direction)
```

### `SyncFlag` (Value Object)

```php
use App\Domain\Shared\Core\Enum\SyncStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;

// Direction auto-resolve bila tidak diberikan:
//   sync_flag=null           -> SyncDirection::NONE
//   sync_flag=1, no origin   -> SyncDirection::MASTER_TO_ORIGIN
//   sync_flag=1, origin set  -> SyncDirection::ORIGIN_TO_MASTER
$sync = SyncFlag::create(originId: null, status: SyncStatus::NOT_SYNCED);

$sync = SyncFlag::masterToOrigin();     // broadcast ke semua origin
$sync = SyncFlag::masterToOrigin(5);    // ke origin #5
$sync = SyncFlag::originToMaster(5);    // origin #5 -> master
$sync = SyncFlag::bidirectional(5);     // dua arah
$sync = SyncFlag::synced();             // tidak perlu sync

$sync = SyncFlag::fromArray($row);      // dari request/DB array
$sync = SyncFlag::fromEntity($entity);  // dari entity (lihat kontrak di bawah)

$sync->isPending();            // sync_flag = 1
$sync->isSynced();             // sync_flag = null
$sync->needsSyncToOrigin();    // pending && master->origin
$sync->needsSyncToMaster();    // pending && origin->master
$sync->markForSync();          // instance baru, sync_flag=1
$sync->markSynced();           // instance baru, sync_flag=null
$sync->withOriginId(5);        // instance baru, origin_id diganti (direction re-resolve)
$sync->toArray();              // origin_id + sync_flag + direction
$sync->toDbArray();            // origin_id + sync_flag saja
```

### `SyncFlagFactory`

```php
use App\Application\Shared\Core\Factory\SyncFlagFactory;

final class ExampleApplicationService
{
    public function __construct(
        private SyncFlagFactory $syncFlagFactory,
    ) {}

    public function create(CreateExampleCommand $command): void
    {
        // Boundary tetap int mentah — default sync_flag = 1
        $sync = $this->syncFlagFactory->create(
            originId: $command->originId,
            syncFlag: $command->syncFlag ?? 1,
        );

        if ($this->syncFlagFactory->shouldPushToOrigin($sync)) {
            // Payload queue: table, record_id, origin_id, direction,
            // operation, payload, synced_at, created_at, created_by
            $payload = $this->syncFlagFactory->buildMasterToOriginPayload(
                storeIdDestination: $sync->getOriginId(),
                table: 'example',
                recordId: $id,
                operation: 'INSERT',
                data: $command->data,
            );
        }
    }
}
```

## Kontrak `fromEntity()`

Entity yang ingin di-hydrate ke `SyncFlag` cukup expose getter berikut (semua opsional):

| Method | Return | Keterangan |
|--------|--------|------------|
| `getOriginId()` | `?int` | id origin |
| `getSyncFlagValue()` | `?int` | nilai db mentah (`null`/`1`) — **diprioritaskan** |
| `getSyncFlag()` | `SyncFlag\|?int` | alternatif bila `getSyncFlagValue` tidak ada |
| `getSyncDirection()` | `SyncDirection\|?int` | arah eksplisit |

## Implementasi ke Tabel/Entity Baru

Referensi lengkap ada di modul `another_example`. Langkahnya:

**1. Migration**

```php
'origin_id' => $cb::integer()->null()->comment('origin instance id where the record comes from or goes to'),
'sync_flag' => $cb::smallint()->null()->defaultValue(1)->comment('null: synced, 1: not synced'),
```

**2. Entity** — tambah properti `?SyncFlag` + accessor:

```php
private ?SyncFlag $syncFlag = null,   // di constructor, create(), reconstitute()

public function getSyncFlag(): ?SyncFlag       => $this->syncFlag;
public function getSyncFlagValue(): ?int       => $this->syncFlag?->getSyncFlag();
public function getOriginId(): ?int            => $this->syncFlag?->getOriginId();
public function setSyncFlag(SyncFlag $v): self { $this->syncFlag = $v; return $this; }
public function updateSyncFlag(?SyncFlag $v): void { $this->syncFlag = $v; }
```

**3. Repository** — persist & hydrate:

```php
// mapEntityToTable()
SyncFlag::fieldOriginId() => $entity->getOriginId(),
SyncFlag::fieldSyncFlag() => $entity->getSyncFlagValue(),

// reconstitute dari row
syncFlag: SyncFlag::fromArray($row),

// list(): tambah ke ->select() dan allowedColumns filterByExactMatch
SyncFlag::fieldOriginId(),
SyncFlag::fieldSyncFlag(),
```

**4. API** — tambah `origin_id`, `sync_flag` ke `ALLOWED_KEYS` di create action, map ke command, tambah rule di validator:

```php
// CREATE (opsional; kosong = default)
'origin_id' => [new Integer(min: 1, skipOnEmpty: true)],
'sync_flag' => [new Integer(skipOnEmpty: true), new In([1], skipOnEmpty: true)],

// SEARCH (sebagai filter)
'origin_id' => [new Integer(skipOnEmpty: true)],
'sync_flag' => [new Integer(skipOnEmpty: true)],
```

**5. Service** — bangun via factory, oper ke `Entity::create(syncFlag: ...)`.

**6. Response DTO** — expose `origin_id` + `sync_flag`.

## Alur Sync Antar Store (via Cloud)

```
Store A ──(ORIGIN_TO_MASTER)──► Cloud ──(MASTER_TO_ORIGIN)──► Store B
```

1. **Store A** buat record tujuan B: `origin_id = B`, `sync_flag = 1` → push ke cloud.
2. **Cloud** simpan apa adanya (`origin_id = B`, `sync_flag = 1` = pending untuk B).
3. **Store B** pull: filter `origin_id = B AND sync_flag = 1` → apply lokal dengan `sync_flag = null`.
4. **B ack ke cloud** → cloud set `sync_flag = null`.

### Aturan penting

- **`origin_id` ditulis ulang setiap hop** — artinya "tujuan pengiriman berikutnya". Saat B update dan push balik, `origin_id` harus diganti `A`. Kalau tidak, record akan terkirim balik ke B (**echo loop**).
- **Applier selalu tulis `sync_flag = null`** secara eksplisit saat apply — kalau tidak, record hasil sync akan ter-flag `1` lagi dan ter-push balik.
- **Ack oleh penerima**, bukan oleh cloud saat melayani pull — supaya data tidak hilang kalau penerima gagal apply setelah download.

## Batasan & Pengembangan Lanjutan

Skema sekarang cukup untuk **point-to-point A ↔ B**. Untuk kebutuhan lebih kompleks perlu tambahan:

- **Broadcast / multi-hop** → tabel `sync_queue` (satu record → banyak baris antrian per tujuan) atau kolom `store_id_origin` + `store_id_destination`.
- **Korelasi lintas store** (id lokal beda-beda: A=100, B=200, cloud=300) → `uuid` per record, atau registry `(store_id, table, local_id)` di cloud.
- **Provenance/kepemilikan** → field `doc_role` (`LOCAL`/`INTERSTORE`/`COPY`): hanya `LOCAL` yang boleh di-edit user; copy bersifat read-only kecuali oleh sync applier; agregasi dedupe by `uuid`.

## Referensi API

### `SyncFlag` — `src/Domain/Shared/Core/ValueObject/SyncFlag.php`

Value object untuk mengelola atribut `origin_id`, `sync_flag`, dan arah
sinkronisasi.

- `origin_id` : integer, id origin tempat record berasal atau dituju. Default `null`.
- `sync_flag` : smallint, `null` = synced, `1` = not synced. Default `1`.

**Factory method**

- `SyncFlag::create(?int $originId = null, SyncStatus $status = SyncStatus::NOT_SYNCED, ?SyncDirection $direction = null)`
- `SyncFlag::fromArray(array $data)`
- `SyncFlag::fromEntity(object $entity)`
- `SyncFlag::masterToOrigin(?int $originId = null)`
- `SyncFlag::originToMaster(int $originId)`
- `SyncFlag::bidirectional(int $originId)`
- `SyncFlag::synced()`

**Method baca & predikat**

- `getOriginId()`, `getSyncStatus()`, `getSyncFlag()` (nilai db `null`/`1`), `getDirection()` (`SyncDirection`)
- `isPending()`, `isSynced()`
- `isMasterToOrigin()`, `isOriginToMaster()`, `isBidirectional()`
- `needsSyncToOrigin()`, `needsSyncToMaster()`

**Mutasi immutable**

- `markForSync()` — tandai record harus disync (`sync_flag = 1`)
- `markSynced()` — tandai record sudah selesai disync (`sync_flag = null`)
- `withOriginId(?int $originId)` — ganti origin id
- `withDirection(SyncDirection $direction)` — ganti arah

**Serialisasi**

- `toArray()` — output `origin_id`, `sync_flag`, `direction`
- `toDbArray()` — output hanya `origin_id` dan `sync_flag`
- `equals(SyncFlag $other)` — bandingkan dua value object

### `SyncStatus` — `App\Domain\Shared\Core\Enum\SyncStatus` (pure enum)

- `SYNCED` — db value `null`
- `NOT_SYNCED` — db value `1`
- `dbValue()` / `fromDbValue(?int $value)` — konversi nilai kolom `sync_flag`; `fromDbValue` throw `BadRequestException` bila nilai tidak valid

### `SyncDirection` — `App\Domain\Shared\Core\Enum\SyncDirection` (backed int)

- `NONE = 0`
- `MASTER_TO_ORIGIN = 1`
- `ORIGIN_TO_MASTER = 2`
- `BIDIRECTIONAL = 3`
- `SyncDirection::fromValue(int $value)` — throw `BadRequestException` bila nilai tidak valid

### `SyncFlagFactory` — `src/Application/Shared/Core/Factory/SyncFlagFactory.php`

Factory layer untuk membuat dan mengolah `SyncFlag` dari berbagai sumber, plus helper payload & log.

**Factory dari input**

- `create(?int $originId = null, ?int $syncFlag = 1, ?int $direction = null)`
- `fromRequest(array $data)`
- `fromRecord(array $row)`
- `fromEntity(object $entity)`

**Preset arah sinkronisasi**

- `masterToOrigin(?int $originId = null)`
- `originToMaster(int $originId)`
- `bidirectional(int $originId)`
- `synced()`

**Status sync**

- `markForSync(SyncFlag $syncFlag)`
- `markSynced(SyncFlag $syncFlag)`

**Predicate push**

- `shouldPushToOrigin(SyncFlag $syncFlag)`
- `shouldPushToMaster(SyncFlag $syncFlag)`

**Helper payload & log**

- `buildPayload(SyncFlag $syncFlag, string $table, int $recordId, string $operation, array $data = [])`
- `buildMasterToOriginPayload(...)`
- `buildOriginToMasterPayload(...)`
- `mergeIntoDetailInfo(SyncFlag $syncFlag, array $detailInfo = [])`
- `buildSyncLog(SyncFlag $syncFlag, string $table, int $recordId, string $operation)`

### Integrasi Validator

Message key untuk validasi `sync_flag` (dipakai oleh
`SyncStatus::fromDbValue()` dan `SyncDirection::fromValue()` saat
menerima nilai di luar rentang yang diizinkan):

- `validation.sync_flag.invalid_value`
- `validation.sync_flag.invalid_direction`
