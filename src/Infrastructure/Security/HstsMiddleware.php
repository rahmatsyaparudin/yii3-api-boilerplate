<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class HstsMiddleware implements MiddlewareInterface
{
    private int $maxAge;
    private bool $includeSubDomains;
    private bool $preload;

    public function __construct(
        int $maxAge = 31536000,
        bool $includeSubDomains = true,
        bool $preload = false
    ) {
        $this->maxAge            = $maxAge;
        $this->includeSubDomains = $includeSubDomains;
        $this->preload           = $preload;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        // Only add HSTS on HTTPS connections
        if ($request->getUri()->getScheme() === 'https') {
            $hsts = 'max-age=' . $this->maxAge;

            if ($this->includeSubDomains) {
                $hsts .= '; includeSubDomains';
            }

            if ($this->preload) {
                $hsts .= '; preload';
            }

            $response = $response->withHeader('Strict-Transport-Security', $hsts);
        }

        return $response;
    }
}
