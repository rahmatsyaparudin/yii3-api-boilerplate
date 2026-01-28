<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// Shared Layer
use App\Shared\Exception\ForbiddenException;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final class CorsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private array $config,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $handler->handle($request);
        }

        if (!$this->isOriginAllowed($origin)) {
            throw new ForbiddenException(
                translate: new Message(
                    key: 'request.origin_not_allowed',
                    params: ['origin' => $origin]
                )
            );
        }

        $allowOrigin = $this->getAllowOriginValue($origin);

        if (\strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = $this->responseFactory->createResponse(Status::NO_CONTENT);

            return $this->addCorsHeaders($request, $response, $allowOrigin);
        }

        $response = $handler->handle($request);

        return $this->addCorsHeaders($request, $response, $allowOrigin);
    }

    private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response, string $allowOrigin): ResponseInterface
    {
        $response = $response
            ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
            ->withHeader('Vary', 'Origin');

        $allowedMethods = $this->config['allowedMethods'] ?? ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $allowedHeaders = $this->config['allowedHeaders'] ?? ['Content-Type', 'Authorization'];
        $maxAge         = $this->config['maxAge'] ?? 3600;

        $response = $response
            ->withHeader('Access-Control-Allow-Methods', \implode(', ', $allowedMethods))
            ->withHeader('Access-Control-Allow-Headers', \implode(', ', $allowedHeaders))
            ->withHeader('Access-Control-Max-Age', (string) $maxAge);

        $exposed = $this->config['exposedHeaders'] ?? [];
        if (!empty($exposed)) {
            $response = $response->withHeader('Access-Control-Expose-Headers', \implode(', ', $exposed));
        }

        $allowCredentials = (bool) ($this->config['allowCredentials'] ?? false);
        if ($allowCredentials) {
            $response = $response->withHeader('Access-Control-Allow-Credentials', 'true');
        }

        return $response;
    }

    private function isOriginAllowed(string $origin): bool
    {
        $allowedOrigins = $this->config['allowedOrigins'] ?? ['*'];
        if (\in_array('*', $allowedOrigins, true)) {
            return true;
        }

        return \in_array($origin, $allowedOrigins, true);
    }

    private function getAllowOriginValue(string $origin): string
    {
        $allowedOrigins   = $this->config['allowedOrigins'] ?? ['*'];
        $allowCredentials = (bool) ($this->config['allowCredentials'] ?? false);

        if (!$allowCredentials && \in_array('*', $allowedOrigins, true)) {
            return '*';
        }

        return $origin;
    }
}
