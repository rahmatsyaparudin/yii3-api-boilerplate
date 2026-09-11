# Changelog — Sync Flag

Catatan perubahan khusus fitur sinkronisasi master/origin (`sync_flag`) pada Enter-POS.

## Domain Layer

### `src/Domain/Shared/Core/ValueObject/SyncFlag.php`

Value object untuk mengelola atribut `origin_id`, `sync_flag`, dan arah sinkronisasi.

- `origin_id` : integer, id origin tempat record berasal atau dituju. Default `null`.
- `sync_flag` : smallint, `null` = synced, `1` = not synced. Default `1`.

**Konstanta arah sinkronisasi**
- `DIR_NONE = 0`
- `DIR_MASTER_TO_ORIGIN = 1`
- `DIR_ORIGIN_TO_MASTER = 2`
- `DIR_BIDIRECTIONAL = 3`

**Konstanta flag sync**
- `SYNCED = null`
- `NOT_SYNCED = 1`

**Factory method utama**
- `SyncFlag::create(?int $originId = null, ?int $syncFlag = SyncFlag::NOT_SYNCED, ?int $direction = null)`
- `SyncFlag::fromArray(array $data)`
- `SyncFlag::fromEntity(object $entity)`
- `SyncFlag::masterToOrigin(?int $originId = null)`
- `SyncFlag::originToMaster(int $originId)`
- `SyncFlag::bidirectional(int $originId)`
- `SyncFlag::synced()`

**Method baca & predikat**
- `getOriginId()`, `getSyncFlag()`, `getDirection()`
- `isPending()`, `isSynced()`
- `isMasterToOrigin()`, `isOriginToMaster()`, `isBidirectional()`
- `needsSyncToOrigin()`, `needsSyncToMaster()`

**Mutasi immutable**
- `markForSync()` — tandai record harus disync (`sync_flag = 1`)
- `markSynced()` — tandai record sudah selesai disync (`sync_flag = null`)
- `withOriginId(?int $originId)` — ganti origin id
- `withDirection(int $direction)` — ganti arah

**Serialisasi**
- `toArray()` — output `origin_id`, `sync_flag`, `direction`
- `toDbArray()` — output hanya `origin_id` dan `sync_flag`
- `equals(SyncFlag $other)` — bandingkan dua value object

## Application Layer

### `src/Application/Shared/Core/Factory/SyncFlagFactory.php`

Factory layer untuk membuat dan mengolah `SyncFlag` dari berbagai sumber, plus helper payload & log.

**Factory dari input**
- `create(?int $originId = null, ?int $syncFlag = SyncFlag::NOT_SYNCED, ?int $direction = null)`
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

## Validator Integration

Message key untuk validasi `sync_flag` sudah ditambahkan ke message files:

- `validation.sync_flag.invalid_value`
- `validation.sync_flag.invalid_direction`

Kedua key dipakai oleh `SyncFlag` ketika menerima nilai `sync_flag` atau `direction` di luar rentang yang diizinkan.

## Tujuan Fitur

SyncFlag dibuat untuk kebutuhan **push data master/origin**:
- Beberapa tabel akan dipush dari master ke origin.
- Beberapa tabel akan dipush dari origin ke master.
- Tabel tertentu membutuhkan sinkronisasi dua arah (bidirectional).
- Value object dan factory ini menjadi fondasi agar setiap entity dapat membawa metadata sinkronisasi tanpa harus menulis validasi manual di tiap domain.
