# Health Checks

## Why

Health checks are used by load balancers and deployment platforms to determine if the API is up and ready.

## Suggested endpoints

- `GET /health`
  - Returns 200 when application is running.
  - Does not require database.

- `GET /ready`
  - Returns 200 only when dependencies are ready.
  - Typically checks database connectivity.

## Notes

- Keep responses minimal and fast.
- Avoid heavy queries.
