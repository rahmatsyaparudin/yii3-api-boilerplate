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

/**
 * CORS (Cross-Origin Resource Sharing) Middleware
 * 
 * This middleware handles CORS headers for cross-origin requests, allowing
 * web applications to make requests to different domains. It validates origins,
 * adds appropriate CORS headers, and handles preflight OPTIONS requests.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Basic configuration with wildcard origin
 * $middleware = new CorsMiddleware(
 *     config: [
 *         'allowedOrigins' => ['*'],
 *         'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
 *         'allowedHeaders' => ['Content-Type', 'Authorization']
 *     ],
 *     responseFactory: $responseFactory
 * );
 * 
 * @example
 * // Production configuration with specific origins
 * $middleware = new CorsMiddleware(
 *     config: [
 *         'allowedOrigins' => ['https://frontend.example.com', 'https://admin.example.com'],
 *         'allowedMethods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE'],
 *         'allowedHeaders' => ['Content-Type', 'Authorization', 'X-API-Key'],
 *         'exposedHeaders' => ['X-Total-Count', 'X-Page-Count'],
 *         'maxAge' => 7200,
 *         'allowCredentials' => true
 *     ],
 *     responseFactory: $responseFactory
 * );
 * 
 * @example
 * // Development configuration with permissive settings
 * $middleware = new CorsMiddleware(
 *     config: [
 *         'allowedOrigins' => ['*'],
 *         'allowedMethods' => ['*'],
 *         'allowedHeaders' => ['*'],
 *         'maxAge' => 0,
 *         'allowCredentials' => false
 *     ],
 *     responseFactory: $responseFactory
 * );
 * 
 * @example
 * // In middleware stack configuration
 * 'middleware' => [
 *     CorsMiddleware::class => [
 *         'config' => $corsConfig,
 *         'responseFactory' => ResponseFactoryInterface::class
 *     ],
 *     RequestParamsMiddleware::class,
 *     RateLimitMiddleware::class
 * ]
 * 
 * @example
 * // Route-specific CORS configuration
 * $middleware = new CorsMiddleware(
 *     config: [
 *         'allowedOrigins' => ['https://api.example.com'],
 *         'allowedMethods' => ['GET', 'POST'],
 *         'maxAge' => 3600
 *     ],
 *     responseFactory: $responseFactory
 * );
 * 
 * @example
 * // Handling preflight requests
 * // OPTIONS /api/users - Returns 204 with CORS headers
 * // GET /api/users - Returns data with CORS headers
 * // POST /api/users - Returns created resource with CORS headers
 */
final class CorsMiddleware implements MiddlewareInterface
{
    /**
     * CORS Middleware constructor
     * 
     * Initializes the middleware with CORS configuration and response factory.
     * 
     * @param array $config CORS configuration array
     * @param ResponseFactoryInterface $responseFactory Factory for creating responses
     * 
     * @example
     * // Basic configuration
     * $middleware = new CorsMiddleware(
     *     config: ['allowedOrigins' => ['*']],
     *     responseFactory: $responseFactory
     * );
     * 
     * @example
     * // Full configuration using named arguments
     * $middleware = new CorsMiddleware(
     *     config: [
     *         'allowedOrigins' => ['https://example.com'],
     *         'allowedMethods' => ['GET', 'POST', 'PUT', 'DELETE'],
     *         'allowedHeaders' => ['Content-Type', 'Authorization'],
     *         'maxAge' => 7200,
     *         'allowCredentials' => true
     *     ],
     *     responseFactory: $responseFactory
     * );
     * 
     * @example
     * // Development configuration
     * $middleware = new CorsMiddleware(
     *     config: [
     *         'allowedOrigins' => ['*'],
     *         'allowedMethods' => ['*'],
     *         'allowedHeaders' => ['*'],
     *         'maxAge' => 0,
     *         'allowCredentials' => false
     *     ],
     *     responseFactory: $responseFactory
     * );
     */
    public function __construct(
        private array $config,
        private ResponseFactoryInterface $responseFactory,
    ) {
    }

    /**
     * Process the incoming request and handle CORS
     * 
     * Validates the Origin header, handles preflight OPTIONS requests,
     * and adds appropriate CORS headers to all responses.
     * 
     * @param ServerRequestInterface $request The incoming HTTP request
     * @param RequestHandlerInterface $handler The next handler in the chain
     * @return ResponseInterface The response with CORS headers
     * 
     * @example
     * // Basic CORS processing
     * $response = $middleware->process($request, $handler);
     * 
     * @example
     * // In middleware chain
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     $request = $this->validateApiKey($request);
     *     $response = $this->corsMiddleware->process($request, $handler);
     *     return $this->addSecurityHeaders($response);
     * }
     * 
     * @example
     * // Custom CORS handling
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     // Log CORS requests for monitoring
     *     $origin = $request->getHeaderLine('Origin');
     *     if ($origin) {
     *         $this->logger->info('CORS request', ['origin' => $origin, 'method' => $request->getMethod()]);
     *     }
     *     
     *     return parent::process($request, $handler);
     * }
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $origin = $request->getHeaderLine('Origin');

        if ($origin === '') {
            return $handler->handle($request);
        }

        if (!$this->isOriginAllowed($origin)) {
            throw new ForbiddenException(
                translate: Message::create(
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

    /**
     * Add CORS headers to the response
     * 
     * Adds standard CORS headers including Allow-Origin, Allow-Methods,
     * Allow-Headers, Max-Age, Exposed-Headers, and Allow-Credentials.
     * 
     * @param ServerRequestInterface $request The incoming request
     * @param ResponseInterface $response The response to modify
     * @param string $allowOrigin The allowed origin value
     * @return ResponseInterface The response with CORS headers
     * 
     * @example
     * // Adding CORS headers
     * $response = $this->addCorsHeaders($request, $response, 'https://example.com');
     * 
     * @example
     * // Custom CORS header processing
     * private function addCorsHeaders(ServerRequestInterface $request, ResponseInterface $response, string $allowOrigin): ResponseInterface
     * {
     *     $response = $response
     *         ->withHeader('Access-Control-Allow-Origin', $allowOrigin)
     *         ->withHeader('Vary', 'Origin');
     *     
     *     // Add custom headers based on route
     *     $route = CurrentRoute::getName();
     *     if ($route === 'api.upload') {
     *         $response = $response->withHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
     *     }
     *     
     *     return $response;
     * }
     */
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

    /**
     * Check if the origin is allowed based on configuration
     * 
     * Validates the Origin header against the allowed origins list.
     * Supports wildcard '*' for allowing all origins.
     * 
     * @param string $origin The origin to validate
     * @return bool True if origin is allowed, false otherwise
     * 
     * @example
     * // Check if origin is allowed
     * $allowed = $this->isOriginAllowed('https://example.com');
     * 
     * @example
     * // Custom origin validation logic
     * private function isOriginAllowed(string $origin): bool
     * {
     *     $allowedOrigins = $this->config['allowedOrigins'] ?? ['*'];
     *     
     *     // Allow subdomains
     *     foreach ($allowedOrigins as $allowedOrigin) {
     *         if (str_ends_with($allowedOrigin, '.example.com') && 
     *             str_ends_with($origin, '.example.com')) {
     *             return true;
     *         }
     *     }
     *     
     *     return parent::isOriginAllowed($origin);
     * }
     */
    private function isOriginAllowed(string $origin): bool
    {
        $allowedOrigins = $this->config['allowedOrigins'] ?? ['*'];
        if (\in_array('*', $allowedOrigins, true)) {
            return true;
        }

        return \in_array($origin, $allowedOrigins, true);
    }

    /**
     * Get the appropriate Allow-Origin header value
     * 
     * Returns '*' for wildcard origins when credentials are not allowed,
     * otherwise returns the specific origin.
     * 
     * @param string $origin The request origin
     * @return string The Allow-Origin header value
     * 
     * @example
     * // Get Allow-Origin value
     * $allowOrigin = $this->getAllowOriginValue('https://example.com');
     * 
     * @example
     * // Custom Allow-Origin logic
     * private function getAllowOriginValue(string $origin): string
     * {
     *     // Always return specific origin for security
     *     return $origin;
     * }
     */
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
