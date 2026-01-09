# API Readiness Checklist

## Functional

- [ ] CRUD modules are complete (list, view, create, update, delete)
- [ ] Validation exists for payload and query params
- [ ] Consistent response format for success/fail/validation
- [ ] Consistent error translation keys in `resources/messages/*/error.php`

## Security

- [ ] Authentication required for protected routes (JWT)
- [ ] Authorization checks per action (permission rules)
- [ ] CORS configured correctly per environment
- [ ] Trusted host / proxy strategy defined
- [ ] Rate limiting (optional)

## Observability

- [ ] Structured logging
- [ ] Request ID / correlation ID
- [ ] Error monitoring (Sentry, etc.)

## Quality

- [ ] Automated tests (unit + integration)
- [ ] Static analysis (psalm) clean
- [ ] Code style (php-cs-fixer) clean

## Operations

- [ ] Environment variables documented
- [ ] Migration strategy documented
- [ ] CI/CD pipeline
- [ ] Health check endpoint
- [ ] Versioning strategy (`/v1`, `/v2`)
