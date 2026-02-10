<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Vendor Layer
use Yiisoft\Router\CurrentRoute;

// Shared Layer
use App\Shared\Exception\TooManyRequestsException;
use App\Shared\ValueObject\Message;

/**
 * Rate Limit Security Middleware
 * 
 * This middleware implements rate limiting to protect against abuse and DoS attacks.
 * It uses an in-memory storage system with sliding window algorithm and supports
 * different rate limits for different endpoints based on request paths.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Basic rate limiting (100 requests per minute)
 * $middleware = new RateLimitMiddleware();
 * 
 * @example
 * // Custom rate limiting using named arguments
 * $middleware = new RateLimitMiddleware(
 *     maxRequests: 60,
 *     windowSize: 60
 * );
 * // Allows 60 requests per minute
 * 
 * @example
 * // Strict rate limiting for public APIs
 * $middleware = new RateLimitMiddleware(
 *     maxRequests: 10,
 *     windowSize: 60
 * );
 * // Allows 10 requests per minute
 * 
 * @example
 * // High-volume internal APIs
 * $middleware = new RateLimitMiddleware(
 *     maxRequests: 1000,
 *     windowSize: 60
 * );
 * // Allows 1000 requests per minute
 * 
 * @example
 * // In middleware stack
 * $app->add(new RateLimitMiddleware(
 *     maxRequests: 100,
 *     windowSize: 60
 * ));
 */
final class RateLimitMiddleware implements MiddlewareInterface
{
    private array $storage = [];
    private int $windowSize;
    private int $maxRequests;

    /**
     * Rate Limit Middleware constructor
     * 
     * Creates a new rate limiter with configurable request limits and time windows.
     * Uses sliding window algorithm for accurate rate limiting.
     * 
     * @param int $maxRequests Maximum requests allowed per window (default: 100)
     * @param int $windowSize Time window in seconds (default: 60)
     * 
     * @example
     * // Default rate limiting
     * $middleware = new RateLimitMiddleware();
     * // 100 requests per 60 seconds
     * 
     * @example
     * // Custom configuration using named arguments
     * $middleware = new RateLimitMiddleware(
     *     maxRequests: 50,
     *     windowSize: 30
     * );
     * // 50 requests per 30 seconds
     * 
     * @example
     * // Very strict limiting
     * $middleware = new RateLimitMiddleware(
     *     maxRequests: 5,
     *     windowSize: 60
     * );
     * // 5 requests per minute
     * 
     * @example
     * // Permissive limiting for trusted clients
     * $middleware = new RateLimitMiddleware(
     *     maxRequests: 1000,
     *     windowSize: 60
     * );
     * // 1000 requests per minute
     */
    public function __construct(int $maxRequests = 100, int $windowSize = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSize  = $windowSize;
    }

    /**
     * Process incoming request
     * 
     * Implements rate limiting using sliding window algorithm.
     * Adds rate limit headers to response and throws exception when limit exceeded.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface Response with rate limit headers
     * @throws TooManyRequestsException If rate limit exceeded
     * 
     * @example
     * // Basic middleware processing
     * $response = $middleware->process($request, $handler);
     * // Response includes rate limit headers
     * 
     * @example
     * // Rate limit headers added:
     * // X-RateLimit-Limit: 100
     * // X-RateLimit-Remaining: 99
     * // X-RateLimit-Reset: 1640995200
     * 
     * @example
     * // When limit exceeded:
     * throw new TooManyRequestsException(
     *     translate: Message::create(
     *         key: 'rate_limit.exceeded',
     *         params: [
     *             'seconds' => 60,
     *             'limit' => 100,
     *             'remaining' => 0,
     *             'reset' => 1640995200,
     *             'retry_after' => 60
     *         ]
     *     )
     * );
     * 
     * @example
     * // Request flow:
     * // 1. Extract client IP and generate cache key
     * // 2. Clean old entries from sliding window
     * // 3. Check current request count
     * // 4. Throw exception if limit exceeded
     * // 5. Add current request to storage
     * // 6. Process request and add headers
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $clientIp = $this->getClientIp($request);
        $key      = $this->getCacheKey($clientIp, $request);

        $now         = \time();
        $windowStart = $now - $this->windowSize;

        // Clean old entries
        if (isset($this->storage[$key])) {
            $this->storage[$key] = \array_filter(
                $this->storage[$key],
                fn ($timestamp) => $timestamp > $windowStart
            );
        }

        // Check current count
        $currentCount = \count($this->storage[$key] ?? []);

        if ($currentCount >= $this->maxRequests) {
            $resetTime = $now + $this->windowSize;

            throw new TooManyRequestsException(
                translate: Message::create(
                    key: 'rate_limit.exceeded',
                    params: [
                        'seconds' => $this->windowSize,
                        'limit' => $this->maxRequests,
                        'remaining' => 0,
                        'reset' => $resetTime,
                        'retry_after' => $this->windowSize
                    ]
                )
            );
        }

        // Add current request
        $this->storage[$key][] = $now;

        $response = $handler->handle($request);

        // Add rate limit headers
        return $response
            ->withHeader('X-RateLimit-Limit', (string) $this->maxRequests)
            ->withHeader('X-RateLimit-Remaining', (string) \max(0, $this->maxRequests - $currentCount - 1))
            ->withHeader('X-RateLimit-Reset', (string) ($now + $this->windowSize));
    }

    /**
     * Get client IP address
     * 
     * Extracts the real client IP from various headers and server parameters.
     * Handles proxy setups and load balancers with proper header precedence.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @return string Client IP address
     * 
     * @example
     * // Direct connection
     * $ip = $this->getClientIp($request);
     * // Returns: '192.168.1.100'
     * 
     * @example
     * // Behind proxy with X-Forwarded-For
     * // Header: X-Forwarded-For: 203.0.113.1, 192.168.1.100
     * $ip = $this->getClientIp($request);
     * // Returns: '203.0.113.1' (first IP in chain)
     * 
     * @example
     * // Behind proxy with X-Real-IP
     * // Header: X-Real-IP: 203.0.113.1
     * $ip = $this->getClientIp($request);
     * // Returns: '203.0.113.1'
     * 
     * @example
     * // Fallback to REMOTE_ADDR
     * $ip = $this->getClientIp($request);
     * // Returns: $_SERVER['REMOTE_ADDR'] or '127.0.0.1' fallback
     */
    private function getClientIp(ServerRequestInterface $request): string
    {
        $serverParams = $request->getServerParams();

        $headers = [
            'HTTP_X_FORWARDED_FOR',
            'HTTP_X_REAL_IP',
            'HTTP_CLIENT_IP',
            'REMOTE_ADDR',
        ];

        foreach ($headers as $header) {
            if (!empty($serverParams[$header])) {
                $ips = \explode(',', $serverParams[$header]);

                return \trim($ips[0]);
            }
        }

        return '127.0.0.1';
    }

    /**
     * Generate cache key for rate limiting
     * 
     * Creates a unique cache key based on client IP and request path.
     * Supports different rate limits for different API endpoints.
     * 
     * @param string $clientIp Client IP address
     * @param ServerRequestInterface $request PSR server request
     * @return string Cache key for rate limiting
     * 
     * @example
     * // Global rate limiting
     * $key = $this->getCacheKey('192.168.1.100', $request);
     * // Returns: 'global:192.168.1.100'
     * 
     * @example
     * // Example endpoint rate limiting
     * // Request: /v1/example/users
     * $key = $this->getCacheKey('192.168.1.100', $request);
     * // Returns: 'example:192.168.1.100'
     * 
     * @example
     * // Auth endpoint rate limiting
     * // Request: /v1/auth/login
     * $key = $this->getCacheKey('192.168.1.100', $request);
     * // Returns: 'auth:192.168.1.100'
     * 
     * @example
     * // Custom endpoint rate limiting
     * // Request: /v1/api/data
     * $key = $this->getCacheKey('192.168.1.100', $request);
     * // Returns: 'global:192.168.1.100' (default)
     * 
     * @example
     * // Path-based rate limiting strategy:
     * // - /v1/example/* → example:{ip}
     * // - /v1/auth/* → auth:{ip}
     * // - Everything else → global:{ip}
     */
    private function getCacheKey(string $clientIp, ServerRequestInterface $request): string
    {
        $uri  = $request->getUri();
        $path = $uri->getPath();

        // Different rate limits for different endpoints
        if (\str_starts_with($path, '/v1/example')) {
            return "example:$clientIp";
        }

        if (\str_starts_with($path, '/v1/auth')) {
            return "auth:$clientIp";
        }

        return "global:$clientIp";
    }
}
