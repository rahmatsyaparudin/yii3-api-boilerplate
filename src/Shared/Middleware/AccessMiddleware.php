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

/**
 * Access Control Middleware
 * 
 * This middleware implements role-based access control (RBAC) for API endpoints.
 * It checks user permissions against route requirements and enforces access policies.
 * Works with YiiSoft Access Checker and integrates with the authentication system.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Basic access middleware setup
 * $middleware = new AccessMiddleware(
 *     accessChecker: $accessChecker,
 *     currentUser: $currentUser,
 *     urlMatcher: $urlMatcher
 * );
 * 
 * @example
 * // In middleware stack (after JWT middleware)
 * $app->add(new AccessMiddleware(
 *     accessChecker: $container->get(AccessCheckerInterface::class),
 *     currentUser: $container->get(CurrentUser::class),
 *     urlMatcher: $container->get(UrlMatcher::class)
 * ));
 * 
 * @example
 * // Route with permission requirement
 * // Route: ['pattern' => '/api/admin/users', 'permission' => 'admin.users.read']
 * // User with 'admin.users.read' permission can access
 * 
 * @example
 * // User without permission
 * // User lacks 'admin.users.read' permission
 * // Throws: ForbiddenException with 'access.insufficient_permissions'
 * 
 * @example
 * // Unauthenticated user
 * // No current user/actor set
 * // Throws: ForbiddenException with 'access.insufficient_permissions'
 */
final class AccessMiddleware implements MiddlewareInterface
{
    /**
     * Access Middleware constructor
     * 
     * Creates a new access control middleware with required dependencies
     * for permission checking and user context management.
     * 
     * @param AccessCheckerInterface $accessChecker Permission checking service
     * @param CurrentUser $currentUser Current user context service
     * @param UrlMatcher $urlMatcher Route URL matcher for permission extraction
     * 
     * @example
     * // Dependency injection setup
     * $middleware = new AccessMiddleware(
     *     accessChecker: $container->get(AccessCheckerInterface::class),
     *     currentUser: $container->get(CurrentUser::class),
     *     urlMatcher: $container->get(UrlMatcher::class)
     * );
     * 
     * @example
     * // Manual dependency injection
     * $middleware = new AccessMiddleware(
     *     accessChecker: new AccessChecker($rbac),
     *     currentUser: new CurrentUser(),
     *     urlMatcher: new UrlMatcher($routes)
     * );
     * 
     * @example
     * // In service container configuration
     * $container->set(AccessMiddleware::class, function($container) {
     *     return new AccessMiddleware(
     *         accessChecker: $container->get(AccessCheckerInterface::class),
     *         currentUser: $container->get(CurrentUser::class),
     *         urlMatcher: $container->get(UrlMatcher::class)
     *     );
     * });
     */
    public function __construct(
        private AccessCheckerInterface $accessChecker,
        private CurrentUser $currentUser,
        private UrlMatcher $urlMatcher,
    ) {
    }

    /**
     * Process incoming request
     * 
     * Validates user permissions for the requested route. Extracts permission
     * requirements from route and checks against current user's permissions.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface Response from handler
     * @throws ForbiddenException If access is denied
     * 
     * @example
     * // Request with valid permissions
     * // User has 'admin.users.read' permission
     * // Route requires 'admin.users.read' permission
     * $response = $middleware->process($request, $handler);
     * // Request is processed normally
     * 
     * @example
     * // Request without required permission
     * // User lacks 'admin.users.delete' permission
     * // Route requires 'admin.users.delete' permission
     * $response = $middleware->process($request, $handler);
     * // Throws: ForbiddenException('access.insufficient_permissions')
     * 
     * @example
     * // Unauthenticated request
     * // No current user/actor set
     * $response = $middleware->process($request, $handler);
     * // Throws: ForbiddenException('access.insufficient_permissions')
     * 
     * @example
     * // Route without permission requirement
     * // Route has no permission specified
     * $response = $middleware->process($request, $handler);
     * // Request is processed without permission check
     * 
     * @example
     * // Request flow:
     * // 1. Extract current route from request
     * // 2. Get permission requirement from route
     * // 3. Skip if no permission required
     * // 4. Get current user/actor
     * // 5. Check user has required permission
     * // 6. Allow or deny access based on check
     */
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
                translate: Message::create(
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
                translate: Message::create(
                    key: 'access.insufficient_permissions'
                )
            );
        }

        return $handler->handle($request);
    }
}
