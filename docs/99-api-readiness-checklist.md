# API Readiness Checklist

## Functional

- [ ] CRUD modules are complete (list, view, create, update, delete)
- [ ] Validation exists for payload and query params
- [ ] Consistent response format for success/fail/validation
- [ ] Consistent error translation keys in `resources/messages/*/error.php`
- [ ] OpenAPI / Swagger contract is available
- [ ] Idempotency strategy for write endpoints (optional)

## Security

- [ ] Authentication required for protected routes (JWT)
- [ ] Authorization checks per action (permission rules)
- [ ] CORS configured correctly per environment
- [ ] Trusted host / proxy strategy defined
- [ ] Rate limiting (optional)
- [ ] Secure headers (optional)
- [ ] Audit log strategy for create/update/delete

## Observability

- [ ] Structured logging
- [ ] Request ID / correlation ID
- [ ] Error monitoring (Sentry, etc.)
- [ ] Metrics (optional)

## Quality

- [ ] Automated tests (unit + integration)
- [ ] Static analysis (psalm) clean
- [ ] Code style (php-cs-fixer) clean
- [ ] API contract tests (optional)

## Operations

- [ ] Environment variables documented
- [ ] Migration strategy documented
- [ ] CI/CD pipeline
- [ ] Health check endpoints
- [ ] Backup/restore procedure
- [ ] Health check endpoint
- [ ] Versioning strategy (`/v1`, `/v2`)
