# Observability

## Logging

- Use structured logs where possible.
- Log request start/end, status code, duration.

## Correlation ID

- Accept an incoming header (example `X-Request-Id`) or generate one.
- Attach it to logs and responses.

## Error monitoring

- Integrate Sentry or similar.
- Ensure sensitive data is not leaked.
