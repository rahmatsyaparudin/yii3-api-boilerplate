# CORS

## Configuration

- `config/common/params.php` key `app/cors`
- `.env` key `app.cors.allowedOrigins` must be a JSON array string.

Example:

```text
app.cors.allowedOrigins=["http://example.com:3000"]
```

## Middleware

`App\Shared\Middleware\CorsMiddleware`

Behavior:

- If `Origin` is missing: request is treated as non-CORS.
- If origin is not allowed: throws `ForbiddenException`.
- `OPTIONS` returns `204` with CORS headers.

Notes:

- If `allowCredentials=true`, do not use `allowedOrigins=["*"]`.
