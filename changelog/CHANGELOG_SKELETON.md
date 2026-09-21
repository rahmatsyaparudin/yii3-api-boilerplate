# Changelog — Skeleton

Catatan perubahan per versi skeleton (`scripts/skeleton.version`).

## [1.1.0] - 2026-09-11

### Modul Sync — `SyncFlag` (Shared Core, via vendor package)

Rename modul `SyncSlave` → `SyncFlag` dengan semantik kolom baru:

- `slave_id` → `origin_id` (integer, default `null`) — id origin tempat record berasal atau dituju.
- `sync_slave` → `sync_flag` (smallint, `null` = synced, `1` = not synced, default `1`).

**File baru**
- `src/Domain/Shared/Core/Enum/SyncStatus.php` — pure enum `SYNCED`/`NOT_SYNCED` dengan `dbValue()`/`fromDbValue()` (throw `BadRequestException` untuk nilai invalid).
- `src/Domain/Shared/Core/Enum/SyncDirection.php` — backed int enum `NONE`/`MASTER_TO_ORIGIN`/`ORIGIN_TO_MASTER`/`BIDIRECTIONAL` dengan `fromValue()`/`label()`.
- `src/Domain/Shared/Core/ValueObject/SyncFlag.php` — VO immutable: `create()`, `fromArray()`, `fromEntity()`, `masterToOrigin()`, `originToMaster()`, `bidirectional()`, `synced()`, `markForSync()`, `markSynced()`, `withOriginId()`, `withDirection()`, `toArray()`, `toDbArray()`, `equals()`.
- `src/Application/Shared/Core/Factory/SyncFlagFactory.php` — factory + helper payload queue & sync log (`buildPayload`, `buildMasterToOriginPayload`, `buildOriginToMasterPayload`, `buildSyncLog`, `mergeIntoDetailInfo`).

**File diubah**
- `AppConstants`: `SYNC_SLAVE`/`SLAVE_ID` → `SYNC_FLAG`/`ORIGIN_ID`.
- `resources/messages/{en,id}/validation.php`: key `sync_slave.*` → `sync_flag.*` (didistribusikan via `skeleton-update`).
- `SyncFlag::fromEntity()` mendukung `getSyncFlagValue()` (`?int`), `getSyncFlag()` (VO atau int), dan `getSyncDirection()` (enum atau int).

**File dihapus**
- `src/Domain/Shared/Core/ValueObject/SyncSlave.php`
- `src/Application/Shared/Core/Factory/SyncSlaveFactory.php`

### Contoh AnotherExample (via `skeleton-copy-examples`)

Implementasi `origin_id`/`sync_flag` end-to-end sebagai referensi:

- Migration `M20260910104729CreateAnotherExampleTable`: kolom `origin_id` + `sync_flag` (default `1`).
- Entity: properti `?SyncFlag` + accessor (`getSyncFlag()`, `getSyncFlagValue()`, `getOriginId()`, `setSyncFlag()`, `updateSyncFlag()`).
- Repository: persist & hydrate `SyncFlag`, filter `origin_id`/`sync_flag` di `list()`.
- API: `origin_id`/`sync_flag` diterima di create action + validator (CREATE & SEARCH).
- `AnotherExampleResponse` expose `origin_id` + `sync_flag`; `MdbAnotherExampleSchema` ikut menyertakan keduanya.
- Fixture `anotherexample.yaml` disesuaikan dengan signature `create()` baru.

### Fixed

- `AnotherExampleRepository::findByName()` tidak lagi chaining `updateSyncMdb()` (void) pada `reconstitute()` — sebelumnya selalu return `null` dan `sync_mdb` tidak ter-hydrate.

## [1.0.6] dan sebelumnya

Belum terdokumentasi — changelog mulai dicatat per 1.1.0.
