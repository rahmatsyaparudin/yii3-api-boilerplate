# Middleware Pipeline

The API middleware pipeline is defined in:

- `config/web/di/application.php`

Current order (high level):

- `FormatDataResponseAsJson`
- `ContentNegotiator`
- `ErrorCatcher`
- `ExceptionResponder`
- `TrustedHostMiddleware`
- `CorsMiddleware`
- `JwtMiddleware`
- `RequestBodyParser`
- `Router`
- `NotFoundMiddleware`

Notes:

- Put exception-producing middlewares **after** `ErrorCatcher` + `ExceptionResponder`.
- Put `CorsMiddleware` **before** `JwtMiddleware` so preflight `OPTIONS` is not blocked.
