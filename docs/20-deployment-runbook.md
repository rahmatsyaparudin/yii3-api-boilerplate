# Deployment Runbook

## Build / install

- `composer install --no-dev` for production.

## Config rebuild

After changing any config files under `config/`:

- `composer yii-config-rebuild`

## Migrations

- Run DB migrations before switching traffic.

## Post-deploy checks

- Call `GET /health`
- Call `GET /ready`

## Rollback

- Keep previous release artifacts.
- Roll back code and apply DB rollback strategy if needed.
