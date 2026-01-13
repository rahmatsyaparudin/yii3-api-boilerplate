# Secure Headers

## Overview

Secure headers protect your API from various web vulnerabilities by instructing browsers on how to handle content and interactions.

## Implementation

### 1. SecureHeadersMiddleware

The `SecureHeadersMiddleware` adds comprehensive security headers to all responses:

```php
// Default headers included
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN  
X-XSS-Protection: 1; mode=block
Referrer-Policy: strict-origin-when-cross-origin
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'...
Permissions-Policy: geolocation=(), microphone=(), camera=()
```

### 2. HSTS Middleware

Separate HSTS middleware for HTTPS-only sites:

```php
// Only added on HTTPS connections
Strict-Transport-Security: max-age=31536000; includeSubDomains
```

## Configuration

Add to `config/common/params.php`:

```php
'app/secureHeaders' => [
    'csp' => [
        'default-src' => "'self'",
        'script-src' => "'self' 'unsafe-inline'",
        'style-src' => "'self' 'unsafe-inline'",
        'img-src' => "'self' data: https:",
        'connect-src' => "'self'",
    ],
    'permissions' => [
        'geolocation' => '()',
        'microphone' => '()',
        'camera' => '()',
    ],
    'custom' => [
        'X-Content-Type-Options' => 'nosniff',
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
    ],
],
'app/hsts' => [
    'maxAge' => 31536000,
    'includeSubDomains' => true,
    'preload' => false,
],
```

## Middleware Pipeline

Secure headers are applied after rate limiting but before access control:

```php
// config/web/di/application.php
CorsMiddleware::class,
JwtMiddleware::class,
static fn() => new RateLimitMiddleware(100, 60),
static fn() => new SecureHeadersMiddleware(),
RequestBodyParser::class,
AccessMiddleware::class,
Router::class,
```

Note: Middleware with constructor parameters must be instantiated using factory functions in the application pipeline, as the middleware dispatcher doesn't use the DI container.

## Security Headers Explained

### Content Security Policy (CSP)

Prevents XSS and data injection attacks:

```php
'default-src' => "'self'",           // Only allow same-origin
'script-src' => "'self' 'unsafe-inline'",  // Allow inline scripts
'style-src' => "'self' 'unsafe-inline'",   // Allow inline styles
'img-src' => "'self' data: https:",        // Allow images and data URIs
'connect-src' => "'self'",                 // Only connect to same origin
'frame-ancestors' => "'none'",             // Prevent framing
```

### X-Frame-Options

Prevents clickjacking attacks:
- `SAMEORIGIN`: Only allows framing by same origin
- `DENY`: Completely prevents framing

### X-Content-Type-Options

Prevents MIME-type sniffing attacks:
- `nosniff`: Browser won't guess content types

### X-XSS-Protection

Enables browser XSS filtering:
- `1; mode=block`: Block detected XSS attacks

### Referrer Policy

Controls how much referrer information is sent:
- `strict-origin-when-cross-origin`: Full URL for same-origin, only origin for cross-origin

### Permissions Policy

Disables browser features:
- `geolocation=()`: Disables geolocation API
- `microphone=()`: Disables microphone access
- `camera=()`: Disables camera access

### Strict Transport Security (HSTS)

Enforces HTTPS connections:
- `max-age=31536000`: 1 year in seconds
- `includeSubDomains`: Apply to all subdomains
- `preload`: Include in browser preload list

## Environment-Specific Configuration

### Development

```php
'app/secureHeaders' => [
    'csp' => [
        'script-src' => "'self' 'unsafe-inline' 'unsafe-eval'",  // Allow eval for debugging
    ],
],
'app/hsts' => [
    'maxAge' => 0,  // Disable HSTS in development
],
```

### Production

```php
'app/secureHeaders' => [
    'csp' => [
        'script-src' => "'self'",  // Strict CSP
        'upgrade-insecure-requests' => '',  // Upgrade HTTP to HTTPS
    ],
],
'app/hsts' => [
    'maxAge' => 31536000,
    'includeSubDomains' => true,
    'preload' => true,  // Submit to preload list
],
```

## Testing

### Verify Headers

```bash
# Check response headers
curl -I http://localhost:8080/v1/brand/index

# Expected headers
X-Content-Type-Options: nosniff
X-Frame-Options: SAMEORIGIN
X-XSS-Protection: 1; mode=block
Content-Security-Policy: default-src 'self'...
```

### Security Scanners

Test with online tools:
- [Security Headers Scanner](https://securityheaders.com/)
- [Mozilla Observatory](https://observatory.mozilla.org/)
- [CSP Evaluator](https://csp-evaluator.withgoogle.com/)

## Best Practices

1. **Start Conservative**: Begin with strict CSP and relax as needed
2. **Report-Only Mode**: Use CSP report-only for testing
3. **Monitor Violations**: Track CSP violation reports
4. **Regular Updates**: Review and update policies regularly
5. **Environment Differences**: Use different policies per environment

## Common Issues

### CSP Violations

Symptoms:
- Scripts don't execute
- Styles don't apply
- Images don't load

Solutions:
- Add missing sources to CSP directives
- Use nonces or hashes for inline content
- Check browser console for violations

### HSTS Issues

Symptoms:
- Can't access HTTP version
- Certificate problems lock you out

Solutions:
- Start with short max-age values
- Test thoroughly before enabling includeSubDomains
- Keep backup access method available

## Advanced Configuration

### CSP with Nonces

```php
// Generate nonce per request
$nonce = base64_encode(random_bytes(16));

// In middleware
$csp = "default-src 'self'; script-src 'self' 'nonce-{$nonce}'";
$response = $response->withHeader('Content-Security-Policy', $csp);

// In templates
<script nonce="<?php echo $nonce; ?>">
    // Your inline script
</script>
```

### Report-Only CSP

```php
'app/secureHeaders' => [
    'csp' => [
        'default-src' => "'self'",
        'report-uri' => '/csp-violation-report',
    ],
    'reportOnly' => true,  // Only report, don't block
],
```

### Feature Detection

```php
// Conditionally apply headers based on browser
$userAgent = $request->getHeaderLine('User-Agent');
if (strpos($userAgent, 'Chrome') !== false) {
    // Chrome-specific headers
}
```

## Monitoring

### CSP Violation Reporting

```php
// Endpoint to receive CSP reports
public function cspViolationReport(ServerRequestInterface $request): ResponseInterface
{
    $report = json_decode($request->getBody()->getContents(), true);
    
    // Log violation
    error_log("CSP Violation: " . json_encode($report));
    
    return $this->responseFactory->create(204);
}
```

### Header Validation

```php
// Test middleware to verify headers
class SecurityHeaderTestMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);
        
        // Verify required headers are present
        $requiredHeaders = ['X-Content-Type-Options', 'X-Frame-Options'];
        foreach ($requiredHeaders as $header) {
            if (!$response->hasHeader($header)) {
                throw new \RuntimeException("Missing security header: $header");
            }
        }
        
        return $response;
    }
}
```

## Performance Impact

- **Minimal**: Header addition is negligible
- **CSP Parsing**: Small one-time cost per page load
- **HSTS**: No performance impact after initial connection

## Compliance

Secure headers help with:
- **PCI DSS**: Required for payment processing
- **GDPR**: Data protection compliance
- **SOC 2**: Security compliance frameworks
- **OWASP**: Security best practices
