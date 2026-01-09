# Rate Limiting

## Why

Protects the API from abuse and reduces load.

## Where to apply

- Authentication endpoints (login/refresh)
- High-traffic read endpoints (list/data)

## Suggested approach

- Add a middleware that:
  - Uses IP + route key
  - Stores counters in Redis (recommended) or DB
  - Throws `TooManyRequests` (HTTP 429)

## Notes

CORS `OPTIONS` requests should not be rate-limited aggressively.
