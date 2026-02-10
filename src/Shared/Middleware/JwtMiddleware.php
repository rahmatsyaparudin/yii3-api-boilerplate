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

/**
 * JWT Authentication Middleware
 * 
 * This middleware handles JWT token authentication for API requests.
 * It validates JWT tokens from Authorization headers, extracts user claims,
 * and sets the current user context for the application. Supports public paths
 * that don't require authentication.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Basic JWT middleware setup
 * $middleware = new JwtMiddleware(
 *     jwtService: $jwtService,
 *     actorProvider: $actorProvider,
 *     currentUser: $currentUser
 * );
 * 
 * @example
 * // In middleware stack
 * $app->add(new JwtMiddleware(
 *     jwtService: $container->get(JwtService::class),
 *     actorProvider: $container->get(ActorProvider::class),
 *     currentUser: $container->get(CurrentUser::class)
 * ));
 * 
 * @example
 * // Request with valid JWT
 * // Authorization: Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...
 * $response = $middleware->process($request, $handler);
 * // User is authenticated and available via CurrentUser
 * 
 * @example
 * // Request without JWT on protected path
 * // No Authorization header
 * // Throws: UnauthorizedException with 'auth.header_missing' message
 * 
 * @example
 * // Request with invalid JWT
 * // Authorization: Bearer invalid-token
 * // Throws: UnauthorizedException with 'auth.invalid_token' message
 */
final class JwtMiddleware implements MiddlewareInterface
{
    private array $publicPaths = [
        '/',
        '/auth/login',
        '/auth/refresh',
    ];

    /**
     * JWT Middleware constructor
     * 
     * Creates a new JWT middleware with required dependencies for token
     * validation and user context management.
     * 
     * @param JwtService $jwtService JWT token validation service
     * @param ActorProvider $actorProvider User actor provider from token claims
     * @param CurrentUser $currentUser Current user context service
     * 
     * @example
     * // Dependency injection setup
     * $middleware = new JwtMiddleware(
     *     jwtService: $container->get(JwtService::class),
     *     actorProvider: $container->get(ActorProvider::class),
     *     currentUser: $container->get(CurrentUser::class)
     * );
     * 
     * @example
     * // Manual dependency injection
     * $middleware = new JwtMiddleware(
     *     jwtService: new JwtService($config),
     *     actorProvider: new ActorProvider(),
     *     currentUser: new CurrentUser()
     * );
     * 
     * @example
     * // In service container configuration
     * $container->set(JwtMiddleware::class, function($container) {
     *     return new JwtMiddleware(
     *         jwtService: $container->get(JwtService::class),
     *         actorProvider: $container->get(ActorProvider::class),
     *         currentUser: $container->get(CurrentUser::class)
     *     );
     * });
     */
    public function __construct(
        private JwtService $jwtService,
        private ActorProvider $actorProvider,
        private CurrentUser $currentUser
    ) {
    }

    /**
     * Process incoming request
     * 
     * Validates JWT tokens for protected paths and sets user context.
     * Skips validation for public paths and throws exceptions for invalid tokens.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @param RequestHandlerInterface $handler Next request handler
     * @return ResponseInterface Response from handler
     * @throws UnauthorizedException If authentication fails
     * 
     * @example
     * // Public path request (no JWT required)
     * $request = $request->withUri($request->getUri()->withPath('/'));
     * $response = $middleware->process($request, $handler);
     * // Processes normally without authentication
     * 
     * @example
     * // Protected path with valid JWT
     * $request = $request->withHeader('Authorization', 'Bearer valid-jwt-token');
     * $response = $middleware->process($request, $handler);
     * // User context is set and request is processed
     * 
     * @example
     * // Protected path without JWT
     * $response = $middleware->process($request, $handler);
     * // Throws: UnauthorizedException('auth.header_missing')
     * 
     * @example
     * // Protected path with invalid JWT
     * $request = $request->withHeader('Authorization', 'Bearer invalid-token');
     * $response = $middleware->process($request, $handler);
     * // Throws: UnauthorizedException('auth.invalid_token')
     * 
     * @example
     * // Request flow:
     * // 1. Check if path is public
     * // 2. Extract Authorization header if not public
     * // 3. Validate and decode JWT token
     * // 4. Create actor from token claims
     * // 5. Set actor in CurrentUser service
     * // 6. Add actor to request attributes
     * // 7. Process request with authentication context
     */
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
                translate: Message::create(
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
                translate: Message::create(
                    key: 'auth.invalid_token',
                    params: ['error' => $e->getMessage()]
                )
            );
        }

        return $handler->handle($request);
    }

    /**
     * Check if path is public
     * 
     * Determines if the requested path should bypass JWT authentication.
     * Public paths are configured in the $publicPaths array.
     * 
     * @param string $path Request path to check
     * @return bool True if path is public (no auth required)
     * 
     * @example
     * // Public path check
     * $isPublic = $this->isPublicPath('/');
     * // Returns: true
     * 
     * @example
     * // Auth endpoint check
     * $isPublic = $this->isPublicPath('/auth/login');
     * // Returns: true
     * 
     * @example
     * // Protected path check
     * $isPublic = $this->isPublicPath('/api/users');
     * // Returns: false
     * 
     * @example
     * // Refresh token endpoint
     * $isPublic = $this->isPublicPath('/auth/refresh');
     * // Returns: true
     * 
     * @example
     * // API endpoint
     * $isPublic = $this->isPublicPath('/v1/users');
     * // Returns: false (requires JWT)
     */
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
