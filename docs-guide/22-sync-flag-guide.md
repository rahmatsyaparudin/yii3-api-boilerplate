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

## Injector Kolom Sync (`SyncColumns` + `sync:columns`)

`src/Shared/Core/Enums/SyncColumns.php` adalah kontrak kolom yang bisa
di-**inject per tabel** (opt-in — tabel yang tidak ikut sync tidak
menulis apa-apa). Definisi canonical (tipe, default, comment) hidup di
satu tempat sehingga tabel yang ikut skema tidak menyimpang.

Grup kolom:

| Method | Kolom | Fungsi |
|--------|-------|--------|
| `SyncColumns::identity($cb)` | `uuid`, `source_store_id` | Identitas global + provenance penulis asli (anti echo-loop) |
| `SyncColumns::routing($cb)` | `sync_flag`, `origin_id` | Routing point-to-point (skema SyncFlag yang ada) |
| `SyncColumns::locking($cb)` | `lock_version`, `doc_role` | Optimistic locking + aturan edit (`LOCAL`/`INTERSTORE`/`COPY`) |
| `SyncColumns::mongodb($cb)` | `sync_mdb` | Flag mirror MongoDB |
| `SyncColumns::all($cb)` | gabungan semuanya | Kasus umum: tabel ikut sync penuh |
| `SyncColumns::names()` | daftar nama kolom | Untuk `down()` / whitelist select |

**Urutan canonical tail** — `all()` selalu mengembalikan kolom dalam
urutan baku (tulis `detail_info` manual sebelum spread):

```
detail_info → lock_version → sync_mdb → sync_flag → origin_id
            → source_store_id → uuid → doc_role
```

**Create table** — spread di dalam `createTable()`:

```php
$b->createTable(self::TABLE_NAME, [
    'id'          => $cb::integer()->notNull()->extra(ColumnContract::PG_PRIMARY_KEY_IDENTITY),
    'name'        => $cb::string(255)->notNull(),
    'status'      => $cb::smallint()->notNull()->defaultValue(ColumnContract::statusDefault())->comment(ColumnContract::statusComment()),
    'detail_info' => $cb::json()->notNull()->defaultValue(ColumnContract::DETAILINFO_DEFAULT)->comment(ColumnContract::DETAILINFO_COMMENT),
    ...SyncColumns::all($cb),
]);
```

**Tabel lama (lupa/retrofit)** — generate migration ALTER:

```shell
./yii sync:columns add orders Example
```

menghasilkan `src/Migration/Example/M…AddSyncColumnsToOrdersTable.php`
yang me-loop `SyncColumns::all($cb)` lewat `addColumn()` (dan
`dropColumn()` di `down()` via `SyncColumns::names()`).

> ⚠️ `uuid` didefinisikan `NOT NULL` + `UNIQUE`. Pada tabel yang sudah
> berisi data, backfill satu uuid per row dulu (pgsql:
> `gen_random_uuid()`, mysql: `UUID()`) — tambahkan kolom nullable,
> isi, baru `SET NOT NULL`.

**Inject ke create-table migration yang sudah ada**:

```shell
./yii sync:columns inject src/Migration/Example/M…CreateOrdersTable.php
```

menyisipkan `...SyncColumns::all($cb),` sebagai entry terakhir array
kolom + `use` statement-nya, lalu mem-verifikasi `php -l` (otomatis
revert bila hasil patch invalid). Idempotent — file yang sudah berisi
`SyncColumns::` di-skip.

Catatan: `inject` menaruh `all()` di akhir array; bila tabel sudah punya
sebagian kolom (mis. `origin_id` lama), key duplikat legal di PHP
(last-wins, definisinya identik) tapi bisa dirapikan manual.

### `DocRole` (`src/Domain/Shared/Core/Enum/DocRole.php`)

Enum string-backed — `LOCAL` / `INTERSTORE` / `COPY` — dengan
`fromValue()` (throw `BadRequestException`, key
`doc_role.invalid_value`) dan `label()`. Default kolom `doc_role`
adalah `LOCAL`.

## Identity & Provenance (`SyncIdentity` + `SyncIdentityFactory`)

`SyncIdentity` (`src/Domain/Shared/Core/ValueObject/SyncIdentity.php`)
adalah VO immutable untuk kolom identitas sync — `uuid`,
`source_store_id`, `doc_role`:

```php
use App\Domain\Shared\Core\ValueObject\SyncIdentity;

$identity = SyncIdentity::local(sourceStoreId: 5);   // uuid auto-generate, doc_role=LOCAL
$identity = SyncIdentity::copy($uuid, 5);            // hasil apply dari store #5
$identity = SyncIdentity::fromArray($row);
$identity = SyncIdentity::fromEntity($entity);       // getUuid/getSourceStoreId/getDocRole(Value)

$identity->isEditableByUser();   // hanya LOCAL
$identity->isOwnedBy(5);         // anti echo-loop di applier
$identity->toDbArray();          // uuid + source_store_id + doc_role
SyncIdentity::newUuid();         // uuid v4 (random_bytes, tanpa library)
SyncIdentity::isValidUuid($str);
```

`SyncIdentityFactory`
(`src/Application/Shared/Core/Factory/SyncIdentityFactory.php`)
mengambil id store instance ini dari params `app/sync`
(env `app.sync.store_id`; `0` = cloud/master, `>0` = id store):

```php
$identity = $this->syncIdentityFactory->create();      // uuid + source_store_id = store ini
$identity = $this->syncIdentityFactory->local();
$identity = $this->syncIdentityFactory->incomingCopy($uuid, $sourceStoreId);
$this->syncIdentityFactory->isOwnRecord($identity);    // skip di applier
$this->syncIdentityFactory->assertEditableByUser($identity); // throw doc_role.not_editable
```

## Outbox (`sync_queue` + `SyncOutboxFactory`)

Migration `src/Migration/Sync/M20261126000000CreateSyncQueueTable.php`
membuat tabel `sync_queue` — satu record bisnis → satu baris per
tujuan (`destination_store_id = null` = broadcast). Baris ditulis oleh
repository/service **di transaksi yang sama** dengan record bisnisnya
(outbox pattern), lewat `SyncOutboxFactory`:

```php
$rows = $this->syncOutboxFactory->buildRows(
    table: 'example',
    identity: $identity,
    syncFlag: $sync,
    operation: 'INSERT',
    destinationStoreIds: [5, 7],   // atau [] + broadcast:true
    payload: $command->data,
);
// $repo->insertRows('sync_queue', $rows) dalam transaksi yang sama
```

Status baris dikelola `SyncQueueStatus` (`PENDING=1`, `SENT=2`,
`ACKED=3`, `FAILED=4`); `attempts`/`last_error` untuk retry.

Relasi dengan skema lama: `sync_flag`/`origin_id` di record tetap jalan
untuk point-to-point murah dan sebagai cache denormalized "pending?";
`sync_queue` yang authoritative untuk multi-tujuan, retry, dan audit.

## Modul Referensi: `SyncExample`

Modul `sync_example` adalah referensi implementasi end-to-end skema baru
(pasangan `another_example` yang mereferensikan skema lama):

- Migration `src/Migration/Sync/M20261126000001CreateSyncExampleTable.php`
  memakai `...SyncColumns::all($cb)` (urutan canonical).
- Entity membawa dua VO: `SyncFlag` (skema lama) + `SyncIdentity` (baru).
- `create()` men-stamp `SyncIdentityFactory->create()` (uuid +
  `source_store_id` dari `app.sync.store_id`) lalu menulis record +
  baris `sync_queue` dalam **satu transaksi** via
  `repository->insertWithOutbox()`.
- `update()`/`delete()` memanggil
  `syncIdentityFactory->assertEditableByUser()` — record `COPY`/
  `INTERSTORE` ditolak dengan `doc_role.not_editable`.
- Repository menyediakan `findByUuid()` — pola lookup applier.
- Routes `/v1/sync-example/*`, permission `sync-example.*`.

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
