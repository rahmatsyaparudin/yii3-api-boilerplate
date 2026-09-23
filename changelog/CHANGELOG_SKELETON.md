# Changelog — Skeleton

Catatan perubahan per versi skeleton (`scripts/skeleton.version`).

## [1.2.9] - 2026-09-23

### Changed
- `src/autoload.php` → `src/bootstrap.php` — file bootstrap di-rename;
  `public/index.php` dan `yii` diperbarui ke path baru.
- `config/common/params-core.php` — params `app/monitoring` sekarang bisa
  dioverride dari `.env`: `app.monitoring.request_id_header`,
  `app.monitoring.logging.{enabled,log_level,include_request_body,include_response_body,max_log_size,exclude_paths}`,
  `app.monitoring.metrics.enabled`, dan
  `app.monitoring.error_monitoring.enabled`. Semua key opsional dengan
  fallback ke default sebelumnya.
- `.env.example` — block baru "Monitoring & Structured Logging" berisi
  key di atas.
- `README-SKELETON.md` — section baru "Environment Configuration".
- `composer.json`/`composer.lock` — bump `firebase/php-jwt`,
  `sentry/sentry`, `symfony/console`, `codeception/module-rest`,
  `friendsofphp/php-cs-fixer`, `rector/rector`,
  `roave/infection-static-analysis-plugin`, `vimeo/psalm`.
- `scripts/skeleton-update.php`, `.dockerignore`,
  `PROJECT_STRUCTURE.md`, docs-code (application-params, architecture,
  setup) — penyesuaian mengikuti rename bootstrap.

## [1.2.8] - 2026-09-18

### Added
- `scripts/skeleton.readme` — changelog release; `composer
  skeleton:update` menampilkan section yang lebih baru dari versi yang
  terinstall.
- `scripts/skeleton-update.php` — tampilan release notes pasca-update
  (`displayReleaseNotes()`, `extractReleaseNotes()`); installer menerima
  versi sebelumnya via `SKELETON_PREV_VERSION` dan `skeleton.readme`
  ikut tersinkron meski versi tidak berubah.

### Changed
- `src/Shared/Core/Query/QueryConditionApplier.php` — `filterByLike()`
  dan `orLike()` menjadi instance method (tidak lagi static); argumen
  `$operator` nullable dan auto-resolve dari koneksi DB (`ilike` untuk
  PostgreSQL, `like` selainnya); argumen opsional `$db` untuk query
  multi-database.
- `src/Infrastructure/Common/Persistence/Example/ExampleRepository.php`,
  `.../AnotherExample/AnotherExampleRepository.php` — contoh repository
  memanggil `QueryConditionApplier` sebagai instance ter-inject.

### Renamed
- `scripts/generate-module.php` → `scripts/skeleton-generate-module.php`
  — penamaan `skeleton-*` yang konsisten; command `composer
  skeleton:generate-module` tidak berubah. Update pemanggilan langsung
  (`php scripts/generate-module.php ...`) ke path baru.

## [1.2.7] - 2026-09-17

### Added
- `src/Console/Core/MigrationGuardCommand.php` — command
  `migration-guard` yang menggantikan `migrate:down` dan `migrate:redo`
  di environment `prod` agar migrasi destruktif tidak pernah berjalan di
  sana.
- Layer `src/Console/` dengan split Core/Common: `Core/` (frozen),
  `Common/` (command project), `AGENTS.md`, `!DONT-EDIT-CORE.txt`,
  `!USE-COMMON-FOR-YOUR-CODE`.
- `scripts/skeleton-version.php` — `composer skeleton:version`
  membandingkan versi skeleton terinstall dengan package di vendor.

### Changed
- `src/Console/{ => Core}/HelloCommand.php`, `MigrateModuleCommand.php`,
  `SeederCommand.php` — dipindah ke `src/Console/Core/` (namespace
  `App\Console\Core`).
- `config/console/commands.php` — register `MigrationGuardCommand` untuk
  `migrate:down`/`migrate:redo` saat `Environment::isProd()`.
- `config/common/di/infrastructure-di.php`, `AGENTS.md`,
  `scripts/skeleton-update.php`, composer scripts.

### Removed
- `.env copy.example` — duplikat stray dari file env example.

## [1.2.6] - 2026-09-16

### Added
- Layer `src/Presentation/` — HTTP middleware dipindah dari `src/Shared`
  ke `src/Presentation/Core/Http/Middleware/`: `AccessMiddleware`,
  `CorsMiddleware`, `JwtMiddleware`, `RateLimitMiddleware`,
  `RequestParamsMiddleware`, `SecureHeadersMiddleware`,
  `TrustedHostMiddleware`. `src/Presentation/Core/` kini protected
  (frozen); installer menghapus direktori legacy
  `src/Shared/Core/Middleware/`.

### Changed
- `config/common/di/{jwt.php,middleware-di.php}`,
  `config/common/{middleware.php,routes.php,security.php,migration.php}`,
  `config/web/di/application.php` — update ke namespace
  `App\Presentation\` yang baru.
- `AGENTS.md` dan rules file per-layer — `src/Presentation/Core/`
  ditambahkan ke protected-paths policy.

## [1.2.5] - 2026-09-15

### Changed
- `src/Infrastructure/Core/Database/ConnectionPool.php` — fix connection
  pool MySQL/PostgreSQL.
- `scripts/skeleton-generate-module.php` — perbaikan module generator.
- `src/Console/MigrateModuleCommand.php` — fix command.
- Refresh dokumentasi `docs-code/` (value object, migration & seeding,
  setup, localization guides).

## [1.2.4] - 2026-09-15

### Added
- `config/common/params-core.php` — params inti dipisah dari
  `params.php` ke file skeleton-owned. File ini frozen: perubahan hanya
  datang via `composer skeleton:update`.

### Changed
- `config/common/params.php` — kini menjadi merge point project:
  `array_replace_recursive(params-core.php, [...])`. Tambah/override
  params project di array kedua.
- `scripts/skeleton-update.php` — menyertakan `params-core.php` dalam
  sinkronisasi.

## [1.2.1] – [1.2.3] - 2026-09-14

Bump versi saja — iterasi republish rilis Frozen Core, tanpa perubahan
kode.

## [1.2.0] - 2026-09-14 — Frozen Core for AI Agents

### Added
- `AGENTS.md` — kebijakan protected paths: semua `Core/` di bawah
  `src/`, `src/Api/Shared/Presenter/`, DI wiring skeleton
  (`config/common/di/`), dan file translasi
  `error`/`success`/`validation` bersifat frozen untuk AI agent &
  kontributor. Kode shared baru ditempatkan di direktori `Common/`
  pendamping.
- `!DONT-EDIT-CORE.txt` + `README.md` di tiap `Core/`
  (`src/Shared/Core/`, `src/Domain/Shared/Core/`,
  `src/Application/Shared/Core/`, `src/Infrastructure/Core/`).
- `scripts/skeleton-update.php` — installer menyertakan file marker
  frozen-core.

### Renamed
- `docs-bak/` → `docs-code/`.

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

## [1.0.6] - 2026-09-11

### Added
- `src/Shared/ApplicationParams.php` — properti baru `language`
  (default `en`) dan `?environment` (diisi `'development'` saat
  `APP_ENV=dev`/`development`).
- `config/common/application.php` — key `language` dibaca dari
  `$_ENV['app.config.language']`.
- `src/Api/IndexAction.php` — response index menyertakan `language`,
  plus `environment` saat environment development.

### Changed
- `config/common/di/application.php` — wiring `ApplicationParams` untuk
  `language` dan `environment`.
- `scripts/skeleton-copy-config.php`, `scripts/skeleton-update.php` —
  penyesuaian sinkronisasi.

## [1.0.5] dan sebelumnya

Belum terdokumentasi — changelog mulai dicatat per 1.0.6.
