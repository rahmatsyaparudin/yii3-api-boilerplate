# Environment Variables

This project reads runtime configuration from `.env` (see `config/common/params.php`).

## Application

- `APP_ENV`

Used for environment selection (example: `dev`, `prod`).

- `app.language`

Locale used by the translator (example: `en`, `id`).

- `app.timezone`

Timezone string.

## Database

- `db.default.driver`
- `db.default.host`
- `db.default.port`
- `db.default.name`
- `db.default.user`
- `db.default.password`

## JWT

- `app.jwt.secret`
- `app.jwt.algorithm`
- `app.jwt.issuer`
- `app.jwt.audience`

## CORS

- `app.cors.allowedOrigins`

Must be a JSON array string.

Example:

```text
app.cors.allowedOrigins=["http://example.com:3000"]
```
