<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Shared Layer
use App\Shared\Exception\UnauthorizedException;
use App\Shared\ValueObject\Message;

/**
 * Trusted Host Security Middleware
 * 
 * This middleware validates that incoming requests originate from trusted
 * hostnames to prevent host header injection attacks. It supports exact
 * hostname matching and wildcard subdomain patterns.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Basic configuration with exact hostnames
 * $middleware = new TrustedHostMiddleware(
 *     allowedHosts: ['example.com', 'api.example.com']
 * );
 * 
 * @example
 * // Wildcard subdomain support
 * $middleware = new TrustedHostMiddleware(
 *     allowedHosts: ['example.com', '*.example.com']
 * );
 * // Matches: example.com, api.example.com, admin.example.com
 * 
 * @example
 * // Multiple environment configuration
 * $middleware = new TrustedHostMiddleware(
 *     allowedHosts: [
 *         'localhost',
 *         '127.0.0.1',
 *         '*.example.com',
 *         'staging.example.com'
 *     ]
 * );
 * 
 * @example
 * // In middleware stack
 * $middleware = new TrustedHostMiddleware(
 *     allowedHosts: $config->get('security.trusted_hosts')
 * );
 * $app->add($middleware);
 * 
 * @example
 * // Dependency injection configuration
 * $container->set(TrustedHostMiddleware::class, function($container) {
 *     return new TrustedHostMiddleware(
 *         allowedHosts: $container->get('config')['security']['trusted_hosts']
 *     );
 * });
 */
final class TrustedHostMiddleware implements MiddlewareInterface
{
    /**
     * Trusted Host Middleware constructor
     * 
     * Creates a new middleware instance with a list of allowed hostnames.
     * Supports exact hostnames and wildcard patterns for subdomains.
     * 
     * @param array $allowedHosts List of allowed hostnames
     * 
     * @example
     * // Exact hostname matching
     * $middleware = new TrustedHostMiddleware(
     *     allowedHosts: ['example.com', 'api.example.com']
     * );
     * 
     * @example
     * // Wildcard subdomain matching
     * $middleware = new TrustedHostMiddleware(
     *     allowedHosts: ['*.example.com', 'example.org']
     * );
     * 
     * @example
     * // Development environment
     * $middleware = new TrustedHostMiddleware(
     *     allowedHosts: ['localhost', '127.0.0.1', '*.test']
     * );
     * 
     * @example
     * // Production environment
     * $middleware = new TrustedHostMiddleware(
     *     allowedHosts: [
     *         'example.com',
     *         'www.example.com',
     *         'api.example.com',
     *         '*.example.com'
     *     ]
     * );
     */
    public function __construct(
        private readonly array $allowedHosts,
    ) {
    }

    /**
     * Process incoming request
     * 
     * Validates the request host against the allowed hosts list.
     * Throws UnauthorizedException if the host is not trusted.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface Response from handler
     * @throws UnauthorizedException If host is not allowed
     * 
     * @example
     * // Basic middleware processing
     * $response = $middleware->process($request, $handler);
     * 
     * @example
     * // In application middleware stack
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     return $this->trustedHostMiddleware->process($request, $handler);
     * }
     * 
     * @example
     * // With error handling
     * try {
     *     return $this->trustedHostMiddleware->process($request, $handler);
     * } catch (UnauthorizedException $e) {
     *     return $this->createErrorResponse($e);
     * }
     * 
     * @example
     * // Request flow:
     * // 1. Extract host from request URI
     * // 2. Validate against allowed hosts
     * // 3. Pass to next handler if valid
     * // 4. Throw exception if invalid
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();

        if ($host === '' || !$this->isAllowedHost($host)) {
            throw new UnauthorizedException(
                translate: Message::create(
                    key: 'security.host_not_allowed',
                    params: ['host' => $host]
                )
            );
        }

        return $handler->handle($request);
    }

    /**
     * Check if host is allowed
     * 
     * Iterates through the allowed hosts list and checks if the
     * given host matches any of the configured patterns.
     * 
     * @param string $host Hostname to check
     * @return bool True if host is allowed
     * 
     * @example
     * // Check exact match
     * $allowed = $this->isAllowedHost('example.com');
     * // Returns: true if 'example.com' is in allowed hosts
     * 
     * @example
     * // Check subdomain match
     * $allowed = $this->isAllowedHost('api.example.com');
     * // Returns: true if '*.example.com' is in allowed hosts
     * 
     * @example
     * // Check invalid host
     * $allowed = $this->isAllowedHost('malicious.com');
     * // Returns: false if not in allowed hosts
     * 
     * @example
     * // Empty host handling
     * $allowed = $this->isAllowedHost('');
     * // Returns: false (empty host is not allowed)
     */
    private function isAllowedHost(string $host): bool
    {
        foreach ($this->allowedHosts as $allowedHost) {
            if ($this->matchHost($host, $allowedHost)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Match host against allowed pattern
     * 
     * Supports exact hostname matching and wildcard subdomain patterns.
     * Wildcard patterns use the format '*.example.com' to match all subdomains.
     * 
     * @param string $host Request hostname
     * @param string $allowedHost Allowed host pattern
     * @return bool True if host matches pattern
     * 
     * @example
     * // Exact match
     * $match = $this->matchHost('example.com', 'example.com');
     * // Returns: true
     * 
     * @example
     * // Subdomain wildcard match
     * $match = $this->matchHost('api.example.com', '*.example.com');
     * // Returns: true
     * 
     * @example
     * // No match
     * $match = $this->matchHost('malicious.com', 'example.com');
     * // Returns: false
     * 
     * @example
     * // Wildcard edge cases
     * $match = $this->matchHost('example.com', '*.example.com');
     * // Returns: false (wildcard doesn't match base domain)
     * 
     * @example
     * // Multiple subdomain levels
     * $match = $this->matchHost('api.v1.example.com', '*.example.com');
     * // Returns: true
     */
    private function matchHost(string $host, string $allowedHost): bool
    {
        if ($allowedHost === '') {
            return false;
        }

        if ($host === $allowedHost) {
            return true;
        }

        if (\str_starts_with($allowedHost, '*.')) {
            $suffix = \substr($allowedHost, 1);

            return $suffix !== '' && \str_ends_with($host, $suffix) && $host !== \ltrim($suffix, '.');
        }

        return false;
    }
}
