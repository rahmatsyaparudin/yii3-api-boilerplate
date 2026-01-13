# API Readiness Checklist

## Functional

- [x] CRUD modules are complete (list, view, create, update, delete)
- [x] Validation exists for payload and query params
- [x] Consistent response format for success/fail/validation
- [x] Consistent error translation keys in `resources/messages/*/error.php`
- [ ] OpenAPI / Swagger contract is available
- [ ] Idempotency strategy for write endpoints (optional)

## Security

- [x] Authentication required for protected routes (JWT)
- [x] Authorization checks per action (permission rules)
- [x] CORS configured correctly per environment
- [x] Trusted host / proxy strategy defined
- [x] Rate limiting (optional)
- [x] Secure headers (optional)
- [x] Audit log strategy for create/update/delete

## Observability

- [x] Structured logging
- [x] Request ID / correlation ID
- [x] Error monitoring (Sentry, etc.)
- [x] Metrics (optional)

## Quality

- [x] Automated tests (unit + integration)
- [x] Static analysis (psalm) clean
- [x] Code style (php-cs-fixer) clean
- [x] API contract tests (optional)

## Operations

- [x] Environment variables documented
- [ ] Migration strategy documented
- [ ] CI/CD pipeline
- [x] Health check endpoints
- [x] Backup/restore procedure
- [x] Versioning strategy (`/v1`, `/v2`)
