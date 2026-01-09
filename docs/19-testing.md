# Testing

## Suggested test types

- Unit tests (Domain/Shared)
- Integration tests (API endpoints)

## High value scenarios

- Brand CRUD happy path
- Validation errors (422)
- Filter whitelist errors (400)
- JWT missing/invalid token (401)
- Forbidden access (403) once authorization middleware exists
- CORS preflight `OPTIONS`

## Tooling

This project already includes a `tests/` directory and test tooling configuration.
