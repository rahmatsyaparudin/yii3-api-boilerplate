# Changelog

## [Unreleased] - 2026-09-11

### Added
- `src/Domain/Shared/Core/Enum/SyncStatus.php`: pure enum for `sync_flag` status — `SYNCED` (db `null`), `NOT_SYNCED` (db `1`) — with `dbValue()`/`fromDbValue()`/`label()`/`isSynced()`/`isPending()`; `fromDbValue()` throws `BadRequestException` on invalid values.
- `src/Domain/Shared/Core/Enum/SyncDirection.php`: backed int enum — `NONE` (0), `MASTER_TO_ORIGIN` (1), `ORIGIN_TO_MASTER` (2), `BIDIRECTIONAL` (3) — with `fromValue()`/`label()`; `fromValue()` throws `BadRequestException` on invalid values.
- `src/Domain/Shared/Core/ValueObject/SyncFlag.php`: immutable value object managing `origin_id` / `sync_flag` columns plus sync direction (replaces `SyncSlave`). `origin_id` defaults to `null`, `sync_flag` defaults to `1` (not synced).
- `src/Application/Shared/Core/Factory/SyncFlagFactory.php`: application-layer factory wrapping `SyncFlag` with actor/timestamp-aware queue payload and sync log builders (replaces `SyncSlaveFactory`).
- `SyncFlag::fromEntity()` supports entities exposing `getSyncFlagValue()` (raw `?int`), `getSyncFlag()` returning `SyncFlag` VO or raw int, and `getSyncDirection()` returning `SyncDirection` or int.
- `another_example` table: new `origin_id` (integer, nullable) and `sync_flag` (smallint, nullable, default `1`) columns.
- AnotherExample module: `SyncFlag` wired end-to-end — entity property + accessors, repository persistence/hydration and list filtering, create command/action, input validation (`origin_id` optional int, `sync_flag` optional int in `[1]`), response DTO fields, MongoDB schema fields, seeder fixture arg.
- Validation messages `sync_flag.invalid_value` and `sync_flag.invalid_direction` (en + id).

### Changed
- Renamed `SyncSlave` module to `SyncFlag`: `slave_id` → `origin_id`, `sync_slave` → `sync_flag` (`null` = synced, `1` = not synced).
- `AppConstants`: `SYNC_SLAVE`/`SLAVE_ID` constants replaced by `SYNC_FLAG = 'sync_flag'` and `ORIGIN_ID = 'origin_id'`.
- Direction terminology changed from master/slave to master/origin (`masterToOrigin()`, `originToMaster()`, `needsSyncToOrigin()`, `shouldPushToOrigin()`, etc.).
- `SyncFlagFactory::create()`/`resolveDirection()` now work with `SyncStatus`/`SyncDirection` enums; `resolveDirection()` returns `SyncDirection`.
- `changelog/CHANGELOG_SYNC_SLAVE.md` renamed to `changelog/CHANGELOG_SYNC_FLAG.md` and updated.

### Fixed
- `AnotherExampleRepository::findByName()` no longer chains the void `updateSyncMdb()` on `reconstitute()` (which always returned `null` and mis-hydrated `sync_mdb`); `sync_mdb` and `sync_flag` now hydrate via `reconstitute()`.

### Removed
- `src/Domain/Shared/Core/ValueObject/SyncSlave.php` and `src/Application/Shared/Core/Factory/SyncSlaveFactory.php` (superseded by `SyncFlag`/`SyncFlagFactory`).
- `AppConstants::SYNC_SLAVE` and `AppConstants::SLAVE_ID`.
- Message keys `sync_slave.invalid_value` / `sync_slave.invalid_direction`.

### How to apply this update
1. If `another_example` was already migrated, revert and re-run `M20260910104729CreateAnotherExampleTable` (or add an `ALTER TABLE` migration) to get the `origin_id`/`sync_flag` columns.
2. Run `composer dump-autoload` so the new enum/value-object classes are registered.

## [Unreleased] - 2026-08-27

### Added
- `src/Shared/Utility/FieldMapper.php`: new utility for row field mapping (rename keys, convert zero/'0' values to null).
- `src/Shared/Query/QueryConditionApplier.php`: new `filterByLike()` helper for LIKE/ILIKE filtering with column whitelist and auto-wrapping.
- `public/.htaccess.example`: Apache rewrite rules example.
- `config/common/params.php`: `version` key added to the `app/config` group.
- `.env.example`: `app.config.version` added; `app.config.name` default changed to `"My Project"`.
- `config/common/application.php` & `config/common/di/application.php`: application `name` and `version` now read from `$_ENV` with sensible fallbacks.

### Changed
- `src/Infrastructure/Concerns/ManagesPersistence.php`: `streamRows()` now supports an `$excluded` columns list and only casts `detail_info` when the column is present.
- **Skeleton scripts restructured:**
  - `scripts/skeleton-copy-config.php`:
    - Now owns all config, DI, console, and message files (moved from `skeleton-copy-examples.php`).
    - Added a prominent red-background warning that the script is intended to run only once.
  - `scripts/skeleton-copy-examples.php`:
    - Now only copies `.env.example`, `public/.htaccess.example`, and example `src/*` directories.
    - `public/.htaccess` is generated from `public/.htaccess.example` during example copy.
- `src/Shared/Middleware/JwtMiddleware.php`: updated JWT handling (god-mode support).
- `src/Shared/Request/RequestParams.php`, `src/Shared/Dto/SearchCriteria.php`, `src/Application/Shared/Factory/SearchCriteriaFactory.php`: request/sorting parameter adjustments.
- `config/common/di/db-mongodb.php`: MongoDB extension / configuration updated.
- `composer.json` / `composer.lock`: dependency bumps for `firebase/php-jwt`, `nelmio/alice`, `sentry/sentry`, `symfony/console`, `vlucas/phpdotenv`, `yiisoft/*` packages, `codeception/*`, `friendsofphp/php-cs-fixer`, `phpunit/phpunit`, `rector/rector`, and `vimeo/psalm`.
- `.gitignore`: updated ignore patterns.

### Removed
- `sync-scripts.php` (superseded by `scripts/skeleton-copy-config.php` and `scripts/skeleton-copy-examples.php`).

### How to apply this update
1. Run `php scripts/skeleton-copy-config.php` (first time only) to copy the latest config files.
2. Run `php scripts/skeleton-copy-examples.php` to get `.env.example`, `.htaccess`, and example modules.
3. Compare your local `.env` and configs with the updated `.env.example`, then adjust as needed.
4. Run `composer install` / `composer update` to align dependencies.
