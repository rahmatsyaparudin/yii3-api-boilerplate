# OpenAPI / Swagger

## Why

OpenAPI provides a contract for:

- Endpoint paths and methods
- Request/response schemas
- Authentication requirements
- Error responses

## Suggested implementation

- Maintain an `openapi.yaml` in `docs/` or project root.
- Document common response envelopes (success/fail).
- Include JWT bearer authentication scheme.

## What to include

- `/v1/brand/*` endpoints
- Query params: `page`, `page_size`, `sort_by`, `sort_dir`, filters
- Error responses: 400/401/403/404/409/422
