<?php

declare(strict_types=1);

namespace App\Infrastructure\Monitoring;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Vendor Layer
use Yiisoft\Router\CurrentRoute;

final class RequestIdMiddleware implements MiddlewareInterface
{
    private string $headerName;

    public function __construct(string $headerName = 'X-Request-Id')
    {
        $this->headerName = $headerName;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $requestId = $request->getHeaderLine($this->headerName);

        if (empty($requestId)) {
            $requestId = $this->generateRequestId();
            $request   = $request->withHeader($this->headerName, $requestId);
        }

        $response = $handler->handle($request);

        // Add request ID to response
        return $response->withHeader($this->headerName, $requestId);
    }

    private function generateRequestId(): string
    {
        return \uniqid('req_', true);
    }
}
