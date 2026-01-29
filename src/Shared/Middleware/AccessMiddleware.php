<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// Infrastructure Layer
use App\Infrastructure\Security\CurrentUser;

// Shared Layer
use App\Shared\Exception\ForbiddenException;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

// Vendor Layer
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Router\CurrentRoute;
use Yiisoft\Router\FastRoute\UrlMatcher;
use Yiisoft\Router\Route;

final class AccessMiddleware implements MiddlewareInterface
{
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private UrlMatcher $urlMatcher,
    ) {
    }

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        /** @var CurrentRoute|null $currentRoute */
        $currentRoute = $request->getAttribute(CurrentRoute::class);

        if ($currentRoute === null) {
            // Try to match the route manually before Router runs
            $result     = $this->urlMatcher->match($request);
            $route      = $result->route();
            $permission = $route?->getData('defaults')['permission'] ?? null;
            if ($permission === null) {
                return $handler->handle($request);
            }
        } else {
            $route      = $currentRoute;
            $permission = $route->getArgument('permission') ?? null;
            if ($permission === null) {
                return $handler->handle($request);
            }
        }

        $actor = $this->currentUser->getActor();

        if ($actor === null) {
            throw new ForbiddenException(
                translate: new Message(
                    key: 'access.insufficient_permissions'
                )
            );
        }

        $allowed = $this->accessChecker->userHasPermission(
            $actor->getId() ?? null,
            $permission,
            ['actor' => $actor]
        );

        if (!$allowed) {
            throw new ForbiddenException(
                translate: new Message(
                    key: 'access.insufficient_permissions'
                )
            );
        }

        return $handler->handle($request);
    }
}
