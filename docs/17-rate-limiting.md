# Rate Limiting

## Overview

Rate limiting protects your API from abuse and ensures fair usage by limiting the number of requests clients can make within a time window.

## Implementation

### 1. RateLimitMiddleware

The `RateLimitMiddleware` provides in-memory rate limiting with different limits per endpoint:

```php
// Default: 100 requests per minute per IP
new RateLimitMiddleware(maxRequests: 100, windowSize: 60);
```

### 2. Configuration

Add to `config/common/params.php`:

```php
'app/rateLimit' => [
    'maxRequests' => 100,    // Maximum requests per window
    'windowSize' => 60,      // Window size in seconds
],
```

### 3. Middleware Pipeline

Rate limiting is applied after JWT authentication but before access control:

```php
// config/web/di/application.php
CorsMiddleware::class,
JwtMiddleware::class,
RateLimitMiddleware::class,    // <- Added here
RequestBodyParser::class,
AccessMiddleware::class,
Router::class,
```

## Rate Limiting Strategy

### Per-Endpoint Limits

Different endpoints have different rate limits:

- **Auth endpoints** (`/v1/auth/*`): Stricter limits for login attempts
- **Brand endpoints** (`/v1/brand/*`): Standard limits
- **Global**: Fallback for all other endpoints

### Client Identification

Rate limits are applied per:
- IP address (primary)
- Endpoint category
- User agent (optional)

## Response Headers

Rate limit headers are included in responses:

```
X-RateLimit-Limit: 100
X-RateLimit-Remaining: 95
X-RateLimit-Reset: 1641024000
```

## Error Response

When rate limit is exceeded:

```json
{
    "code": 429,
    "success": false,
    "message": "Too many requests. Please try again in 60 seconds.",
    "errors": [],
    "meta": {
        "limit": 100,
        "remaining": 0,
        "reset": 1641024000,
        "retry_after": 60
    }
}
```

## Database Backed Rate Limiting

For distributed applications, use `DatabaseRateLimiter`:

```php
// Migration included
migrations/m20240101_000001_create_rate_limits.php

// Usage
$rateLimiter = new DatabaseRateLimiter($db, $cache);
$allowed = $rateLimiter->isAllowed('auth:127.0.0.1', 5, 300); // 5 requests per 5 minutes
```

## Advanced Configuration

### Custom Limits per Route

```php
// In middleware factory
RateLimitMiddleware::class => static function () use ($params) {
    return new RateLimitMiddleware(
        maxRequests: 1000,  // Higher limit for trusted clients
        windowSize: 60
    );
},
```

### Redis Backed Rate Limiting

For high-traffic applications:

```php
use Yiisoft\Redis\Connection;

$redisRateLimiter = new RedisRateLimiter(
    $redisConnection,
    $prefix = 'rate_limit:'
);
```

## Monitoring

### Rate Limit Metrics

Monitor rate limit hits to detect abuse:

```sql
-- Check rate limit violations
SELECT 
    DATE(created_at) as date,
    COUNT(*) as violations,
    key
FROM rate_limits 
WHERE created_at >= NOW() - INTERVAL '24 hours'
GROUP BY DATE(created_at), key
ORDER BY violations DESC;
```

### Alerting

Set up alerts for:
- High rate of 429 responses
- Unusual patterns from specific IPs
- Global rate limit exhaustion

## Best Practices

1. **Layered Limits**: Apply both global and per-endpoint limits
2. **Graceful Degradation**: Return useful retry-after headers
3. **Whitelisting**: Exclude trusted IPs from strict limits
4. **Burst Capacity**: Allow short bursts within limits
5. **Documentation**: Clearly document limits in API docs

## Testing

### Unit Tests

```php
public function testRateLimit(): void
{
    // Make requests up to limit
    for ($i = 0; $i < 100; $i++) {
        $response = $this->client->get('/v1/brand/data');
        $this->assertEquals(200, $response->getStatusCode());
    }
    
    // Next request should be rate limited
    $response = $this->client->get('/v1/brand/data');
    $this->assertEquals(429, $response->getStatusCode());
}
```

### Load Testing

```bash
# Use Apache Bench to test rate limits
ab -n 150 -c 10 http://localhost:8080/v1/brand/data
```

## Security Considerations

1. **IP Spoofing**: Validate X-Forwarded-For headers
2. **Distributed Attacks**: Use shared storage for rate limits
3. **Rate Limit Bypass**: Monitor for multiple IPs from same network
4. **Resource Exhaustion**: Ensure rate limit storage doesn't fill up

## Troubleshooting

### Common Issues

1. **False Positives**: Check IP detection logic
2. **Storage Issues**: Monitor rate limit storage capacity
3. **Clock Drift**: Ensure synchronized time in distributed systems
4. **Memory Leaks**: Clean up old rate limit entries

### Debug Information

Enable debug mode to see rate limit status:

```php
// In RateLimitMiddleware
if ($this->debug) {
    error_log("Rate limit: $currentCount/$this->maxRequests for key: $key");
}
```

## Performance Impact

- **Memory**: ~1KB per tracked client
- **CPU**: Minimal overhead per request
- **Database**: Additional writes for rate limit storage

For high-traffic applications, consider:
- Redis for distributed rate limiting
- Sliding window algorithms
- Approximate algorithms (token bucket)

## Notes

CORS `OPTIONS` requests should not be rate-limited aggressively.
