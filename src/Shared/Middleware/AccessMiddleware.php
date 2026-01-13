<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Infrastructure\Security\CurrentUser;
use App\Shared\Exception\ForbiddenException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Yiisoft\Access\AccessCheckerInterface;
use Yiisoft\Router\FastRoute\CurrentRoute;
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
            $route      = $currentRoute->getRoute();
            $permission = $route?->getData('defaults')['permission'] ?? null;
            if ($permission === null) {
                return $handler->handle($request);
            }
        }

        $actor = $this->currentUser->getActor();

        if ($actor === null) {
            throw new ForbiddenException(
                translate: [
                    'key' => 'access.insufficient_permissions',
                    'params' => []
                ]
            );
        }

        $allowed = $this->accessChecker->userHasPermission(
            $actor->id ?? null,
            $permission,
            ['actor' => $actor]
        );

        if (!$allowed) {
            throw new ForbiddenException(
                translate: [
                    'key' => 'access.insufficient_permissions',
                    'params' => []
                ]
            );
        }

        return $handler->handle($request);
    }
}
