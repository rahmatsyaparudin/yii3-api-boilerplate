<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Exception\UnauthorizedException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class TrustedHostMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly array $allowedHosts,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $host = $request->getUri()->getHost();

        if ($host === '' || !$this->isAllowedHost($host)) {
            throw new UnauthorizedException(
                translate: [
                    'key' => 'auth.authorization_header_missing',
                    'params' => []
                ]
            );
        }

        return $handler->handle($request);
    }

    private function isAllowedHost(string $host): bool
    {
        foreach ($this->allowedHosts as $allowedHost) {
            if ($this->matchHost($host, $allowedHost)) {
                return true;
            }
        }

        return false;
    }

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
