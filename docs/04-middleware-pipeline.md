# Middleware Pipeline

The API middleware pipeline is defined in:

- `config/web/di/application.php` (web-specific)
- `config/common/di/middleware.php` (common middleware)

## Common Middleware (Global)

Configured in `config/common/di/middleware.php`:

1. **RequestParamsMiddleware**: Parse and normalize request parameters
2. **CorsMiddleware**: Handle CORS headers and preflight requests
3. **RateLimitMiddleware**: Rate limiting (100 requests per 60s default)
4. **SecureHeadersMiddleware**: Security headers (HSTS, CSP, etc.)
5. **HstsMiddleware**: HTTP Strict Transport Security
6. **RequestIdMiddleware**: Add unique request ID for tracing
7. **StructuredLoggingMiddleware**: Structured logging for observability
8. **MetricsMiddleware**: Application metrics collection
9. **ErrorMonitoringMiddleware**: Error tracking and monitoring
10. **AccessMiddleware**: Permission-based access control

## Web Application Pipeline

Current order (high level):

- `FormatDataResponseAsJson`: Format responses as JSON
- `ContentNegotiator`: Content negotiation
- `ErrorCatcher`: Catch PHP errors
- `ExceptionResponder`: Handle exceptions
- `TrustedHostMiddleware`: Validate trusted hosts
- `CorsMiddleware`: CORS handling
- `JwtMiddleware`: JWT authentication (not currently active)
- `RequestBodyParser`: Parse request body
- `Router`: Route matching
- `NotFoundMiddleware`: 404 handling

## Route-Specific Middleware

### Brand Routes
- **Production Routes** (`/v1/*`): Include `RequestParamsMiddleware` + `AccessMiddleware`
- **Testing Routes** (`/test/*`): Include `RequestParamsMiddleware` only (no auth)

## Configuration Examples

### CORS Configuration
```php
'app/cors' => [
    'allowed_origins' => ['*'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
    'allowed_headers' => ['Content-Type', 'Authorization'],
    'max_age' => 86400,
]
```

### Rate Limiting
```php
'app/rateLimit' => [
    'maxRequests' => 100,
    'windowSize' => 60,
]
```

### Security Headers
```php
'app/secureHeaders' => [
    'x_frame_options' => 'DENY',
    'x_content_type_options' => 'nosniff',
    'x_xss_protection' => '1; mode=block',
]
```

## Notes

- Put exception-producing middlewares **after** `ErrorCatcher` + `ExceptionResponder`
- Put `CorsMiddleware` **before** `JwtMiddleware` so preflight `OPTIONS` is not blocked
- `AccessMiddleware` checks permissions based on route defaults
- Testing routes bypass authentication for development
- All middleware supports dependency injection
