# Architecture Overview

## Layers

- **API layer**: `src/Api/*`
- **Domain layer**: `src/Domain/*`
- **Infrastructure layer**: `src/Infrastructure/*`
- **Shared utilities**: `src/Shared/*`

## Configuration

- Params: `config/common/params.php`
- Routes: `config/common/routes.php`
- Common DI: `config/common/di/*.php`
- Web DI / middleware pipeline: `config/web/di/application.php`

## Data access

Repositories live in `src/Infrastructure/Persistence/*` and implement interfaces from `src/Domain/*`.
