# Changelog

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
