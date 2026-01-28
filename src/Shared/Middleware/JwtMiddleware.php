<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// Infrastructure Layer
use App\Infrastructure\Security\ActorProvider;
use App\Infrastructure\Security\CurrentUser;
use App\Infrastructure\Security\JwtService;

// Shared Layer
use App\Shared\Exception\UnauthorizedException;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Vendor Layer
use Yiisoft\Router\CurrentRoute;

final class JwtMiddleware implements MiddlewareInterface
{
    private array $publicPaths = [
        '/',
        '/auth/login',
        '/auth/refresh',
    ];

    public function __construct(
        private JwtService $jwtService,
        private ActorProvider $actorProvider,
        private CurrentUser $currentUser
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $path = $request->getUri()->getPath();

        // Skip JWT validation for public paths
        if ($this->isPublicPath($path)) {
            return $handler->handle($request);
        }

        $authHeader = $request->getHeaderLine('Authorization');
        if ($authHeader === '') {
            throw new UnauthorizedException(
                translate: new Message(
                    key: 'auth.header_missing'
                )
            );
        }

        $token = \str_replace('Bearer ', '', $authHeader);

        try {
            $claims = $this->jwtService->decode($token);
            $actor  = $this->actorProvider->fromToken($claims);

            // Set actor in CurrentUser service for automatic injection
            $this->currentUser->setActor($actor);

            // Also inject actor to request attributes for backward compatibility
            $request = $request->withAttribute('actor', $actor);
        } catch (\Exception $e) {
            throw new UnauthorizedException(
                translate: new Message(
                    key: 'auth.invalid_token',
                    params: ['error' => $e->getMessage()]
                )
            );
        }

        return $handler->handle($request);
    }

    private function isPublicPath(string $path): bool
    {
        foreach ($this->publicPaths as $publicPath) {
            if ($path === $publicPath) {
                return true;
            }
        }

        return false;
    }
}
