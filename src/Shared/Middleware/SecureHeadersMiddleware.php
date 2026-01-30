<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Secure Headers Security Middleware
 * 
 * This middleware adds comprehensive security headers to HTTP responses
 * to protect against various web vulnerabilities including XSS, clickjacking,
 * and other client-side attacks. It supports customizable CSP and permissions policies.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Default secure headers
 * $middleware = new SecureHeadersMiddleware();
 * // Adds: X-Content-Type-Options, X-Frame-Options, X-XSS-Protection, etc.
 * 
 * @example
 * // Custom CSP configuration
 * $middleware = new SecureHeadersMiddleware([
 *     'csp' => [
 *         'script-src' => "'self' https://cdn.example.com",
 *         'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
 *         'img-src' => "'self' data: https: https://images.example.com"
 *     ]
 * ]);
 * 
 * @example
 * // Custom permissions policy
 * $middleware = new SecureHeadersMiddleware([
 *     'permissions' => [
 *         'geolocation' => 'self',
 *         'camera' => '()',
 *         'microphone' => '()'
 *     ]
 * ]);
 * 
 * @example
 * // Custom headers
 * $middleware = new SecureHeadersMiddleware([
 *     'custom' => [
 *         'X-Custom-Header' => 'custom-value',
 *         'X-API-Version' => 'v1.0'
 *     ]
 * ]);
 * 
 * @example
 * // Complete configuration
 * $middleware = new SecureHeadersMiddleware([
 *     'csp' => [
 *         'default-src' => "'self'",
 *         'script-src' => "'self' https://analytics.example.com"
 *     ],
 *     'permissions' => [
 *         'geolocation' => 'self',
 *         'camera' => '()'
 *     ],
 *     'custom' => [
 *         'X-Frame-Options' => 'DENY'
 *     ]
 * ]);
 */
final class SecureHeadersMiddleware implements MiddlewareInterface
{
    private array $headers;

    /**
     * Secure Headers Middleware constructor
     * 
     * Creates a new middleware instance with configurable security headers.
     * Supports CSP, permissions policy, and custom header configuration.
     * 
     * @param array $config Configuration array for security headers
     * 
     * @example
     * // Default configuration
     * $middleware = new SecureHeadersMiddleware();
     * 
     * @example
     * // CSP-only configuration using named arguments
     * $middleware = new SecureHeadersMiddleware(
 *     config: [
 *         'csp' => [
 *             'script-src' => "'self' https://cdn.example.com",
 *             'style-src' => "'self' 'unsafe-inline'"
 *         ]
 *     ]
 * );
     * 
     * @example
     * // Permissions policy configuration
     * $middleware = new SecureHeadersMiddleware([
 *     'permissions' => [
 *         'geolocation' => 'self',
 *         'camera' => '()',
 *         'microphone' => '()',
 *         'payment' => '()'
 *     ]
 * ]);
     * 
     * @example
     * // Production-hardened configuration
     * $middleware = new SecureHeadersMiddleware([
 *     'csp' => [
 *         'default-src' => "'self'",
 *         'script-src' => "'self'",
 *         'style-src' => "'self'",
 *         'img-src' => "'self' data:",
 *         'font-src' => "'self'",
 *         'connect-src' => "'self' https://api.example.com"
 *     ],
 *     'permissions' => [
 *         'geolocation' => '()',
 *         'camera' => '()',
 *         'microphone' => '()',
 *         'payment' => '()',
 *         'usb' => '()',
 *         'magnetometer' => '()',
 *         'gyroscope' => '()',
 *         'accelerometer' => '()'
 *     ],
 *     'custom' => [
 *         'X-Frame-Options' => 'DENY',
 *         'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload'
 *     ]
 * ]);
     */
    public function __construct(array $config = [])
    {
        $this->headers = \array_merge([
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-XSS-Protection'          => '1; mode=block',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Content-Security-Policy'   => $this->buildCsp($config['csp'] ?? []),
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Permissions-Policy'        => $this->buildPermissionsPolicy($config['permissions'] ?? []),
        ], $config['custom'] ?? []);
    }

    /**
     * Process incoming request
     * 
     * Adds security headers to the response before returning it.
     * Applies all configured headers to enhance security.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface Response with security headers
     * 
     * @example
     * // Basic middleware processing
     * $response = $middleware->process($request, $handler);
     * // Response includes all security headers
     * 
     * @example
     * // In middleware stack
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     return $this->secureHeadersMiddleware->process($request, $handler);
     * }
     * 
     * @example
     * // Response headers added:
     * // X-Content-Type-Options: nosniff
     * // X-Frame-Options: SAMEORIGIN
     * // X-XSS-Protection: 1; mode=block
     * // Referrer-Policy: strict-origin-when-cross-origin
     * // Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; ...
     * // Strict-Transport-Security: max-age=31536000; includeSubDomains
     * // Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), ...
     * 
     * @example
     * // Header application flow:
     * // 1. Process request through handler
     * // 2. Get response from handler
     * // 3. Apply security headers to response
     * // 4. Return modified response
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->headers as $name => $value) {
            if ($value !== null && \is_string($value)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    /**
     * Build Content Security Policy string
     * 
     * Creates a CSP header value from configuration array.
     * Supports all standard CSP directives with sensible defaults.
     * 
     * @param array $config CSP configuration array
     * @return string CSP header value
     * 
     * @example
     * // Default CSP
     * $csp = $this->buildCsp([]);
     * // Returns: "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; ..."
     * 
     * @example
     * // Custom CSP configuration
     * $csp = $this->buildCsp([
     *     'script-src' => "'self' https://cdn.example.com",
     *     'style-src' => "'self' 'unsafe-inline' https://fonts.googleapis.com",
     *     'img-src' => "'self' data: https: https://images.example.com"
     * ]);
     * // Returns: "default-src 'self'; script-src 'self' https://cdn.example.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com; img-src 'self' data: https: https://images.example.com; ..."
     * 
     * @example
     * // Array format for sources
     * $csp = $this->buildCsp([
     *     'script-src' => ["'self'", "https://cdn.example.com"],
     *     'connect-src' => ["'self'", "https://api.example.com"]
     * ]);
     * // Arrays are automatically imploded with spaces
     * 
     * @example
     * // Production CSP (restrictive)
     * $csp = $this->buildCsp([
     *     'default-src' => "'self'",
     *     'script-src' => "'self'",
     *     'style-src' => "'self'",
     *     'img-src' => "'self' data:",
     *     'font-src' => "'self'",
     *     'connect-src' => "'self'",
     *     'frame-ancestors' => "'none'",
     *     'base-uri' => "'self'",
     *     'form-action' => "'self'"
     * ]);
     */
    private function buildCsp(array $config): string
    {
        $directives = \array_merge([
            'default-src' => "'self'",
            'script-src'  => "'self' 'unsafe-inline' 'unsafe-eval'",
            'style-src'   => "'self' 'unsafe-inline'",
            'img-src'     => "'self' data: https:",
            'font-src'    => "'self'",
            'connect-src' => "'self'",
        ], $config);

        foreach ($directives as $directive => $sources) {
            if (\is_array($sources)) {
                $sources = \implode(' ', $sources);
            }
            $directives[$directive] = $directive . ' ' . (string) $sources;
        }

        return \implode('; ', $directives);
    }

    /**
     * Build Permissions Policy string
     * 
     * Creates a Permissions Policy header value from configuration array.
     * Controls access to browser features and APIs.
     * 
     * @param array $config Permissions policy configuration array
     * @return string Permissions Policy header value
     * 
     * @example
     * // Default permissions policy (restrictive)
     * $policy = $this->buildPermissionsPolicy([]);
     * // Returns: "geolocation=(), microphone=(), camera=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()"
     * 
     * @example
     * // Allow specific features
     * $policy = $this->buildPermissionsPolicy([
     *     'geolocation' => 'self',
     *     'camera' => '()',
     *     'microphone' => '()'
     * ]);
     * // Returns: "geolocation=self, camera=(), microphone=(), payment=(), usb=(), magnetometer=(), gyroscope=(), accelerometer=()"
     * 
     * @example
     * // Allow multiple origins
     * $policy = $this->buildPermissionsPolicy([
     *     'geolocation' => 'self https://trusted.example.com',
     *     'camera' => '()',
     *     'microphone' => '()'
     * ]);
     * // Returns: "geolocation=self https://trusted.example.com, camera=(), microphone=(), ..."
     * 
     * @example
     * // Production restrictions
     * $policy = $this->buildPermissionsPolicy([
     *     'geolocation' => '()',
     *     'microphone' => '()',
     *     'camera' => '()',
     *     'payment' => '()',
     *     'usb' => '()',
     *     'magnetometer' => '()',
     *     'gyroscope' => '()',
     *     'accelerometer' => '()',
     *     'ambient-light-sensor' => '()',
     *     'autoplay' => '()',
     *     'document-domain' => '()',
     *     'encrypted-media' => '()',
     *     'fullscreen' => '()',
     * 'picture-in-picture' => '()'
     * ]);
     */
    private function buildPermissionsPolicy(array $config): string
    {
        $policies = \array_merge([
            'geolocation'   => '()',
            'microphone'    => '()',
            'camera'        => '()',
            'payment'       => '()',
            'usb'           => '()',
            'magnetometer'  => '()',
            'gyroscope'     => '()',
            'accelerometer' => '()',
        ], $config);

        $policy = [];
        foreach ($policies as $feature => $allowlist) {
            $policy[] = $feature . '=' . (string) $allowlist;
        }

        return \implode(', ', $policy);
    }
}
