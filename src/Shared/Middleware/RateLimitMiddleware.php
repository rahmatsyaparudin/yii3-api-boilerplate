<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Exception\TooManyRequestsException;
use App\Shared\ValueObject\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RateLimitMiddleware implements MiddlewareInterface
{
    private array $storage = [];
    private int $windowSize;
    private int $maxRequests;

    public function __construct(int $maxRequests = 100, int $windowSize = 60)
    {
        $this->maxRequests = $maxRequests;
        $this->windowSize  = $windowSize;
    }

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
                translate: new Message(
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
