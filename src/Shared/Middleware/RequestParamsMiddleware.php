<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// Application Layer
use App\Shared\Request\RequestParams;

// Shared Layer  
use App\Shared\Request\DataParserInterface;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// PSR Middlewares
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Request Parameters Middleware
 * 
 * This middleware extracts and processes request parameters from both query
 * string and request body, creating a standardized RequestParams object that
 * can be used throughout the application. It handles pagination parameters,
 * sorting, filtering, and raw parameter extraction with validation.
 * 
 * @package App\Shared\Middleware
 * 
 * @example
 * // Basic usage with default configuration
 * $middleware = new RequestParamsMiddleware();
 * 
 * @example
 * // Custom configuration using named arguments
 * $middleware = new RequestParamsMiddleware(
 *     defaultPageSize: 25,
 *     maxPageSize: 100
 * );
 * 
 * @example
 * // In middleware stack configuration
 * 'middleware' => [
 *     RequestParamsMiddleware::class,
 *     CorsMiddleware::class,
 *     RateLimitMiddleware::class,
 * ],
 * 
 * @example
 * // In controller - accessing parsed parameters
 * public function actionList(ServerRequestInterface $request): array
 * {
 *     $params = $request->getAttribute('payload');
 *     
 *     if (!$params instanceof RequestParams) {
 *         throw new RuntimeException('RequestParams not found in request');
 *     }
 *     
 *     $pagination = $params->getPagination();
 *     $sorting = $params->getSorting();
 *     $filters = $params->getRawParams()->all();
 *     
 *     return $this->service->list($pagination, $sorting, $filters);
 * }
 * 
 * @example
 * // In service - using RequestParams for data processing
 * public function processSearch(RequestParams $params): PaginatedResult
 * {
 *     $criteria = new SearchCriteria(
 *         filter: $params->getRawParams()->all(),
 *         page: $params->getPagination()->getPage(),
 *         pageSize: $params->getPagination()->getPageSize(),
 *         sortBy: $params->getSorting()->getSortBy(),
 *         sortDir: $params->getSorting()->getSortDir()
 *     );
 *     
 *     return $this->repository->findByCriteria($criteria);
 * }
 * 
 * @example
 * // Configuration in DI container
 * 'middleware.requestParams' => [
 *     'class' => RequestParamsMiddleware::class,
 *     '__construct()' => [
 *         'defaultPageSize' => 20,
 *         'maxPageSize' => 100
 *     ]
 * ]
 */
final class RequestParamsMiddleware implements MiddlewareInterface
{
    /**
     * Request Parameters Middleware constructor
     * 
     * Configures pagination limits and parameter processing settings.
     * 
     * @param int $defaultPageSize Default page size for pagination (default: 50)
     * @param int $maxPageSize Maximum allowed page size to prevent abuse (default: 200)
     * 
     * @example
     * // Default configuration
     * $middleware = new RequestParamsMiddleware();
     * 
     * @example
     * // Custom limits using named arguments
     * $middleware = new RequestParamsMiddleware(
     *     defaultPageSize: 25,
     *     maxPageSize: 100
     * );
     * 
     * @example
     * // High-performance configuration for APIs
     * $middleware = new RequestParamsMiddleware(
     *     defaultPageSize: 10,
     *     maxPageSize: 50
     * );
     * 
     * @example
     * // Bulk data processing configuration
     * $middleware = new RequestParamsMiddleware(
     *     defaultPageSize: 100,
     *     maxPageSize: 500
     * );
     */
    public function __construct(
        private int $defaultPageSize = 50,
        private int $maxPageSize = 200
    ) {
    }

    /**
     * Process the incoming request and extract parameters
     * 
     * Creates a RequestParams object from query and body parameters,
     * applies pagination limits, and stores the result in request attributes
     * for downstream use.
     * 
     * @param ServerRequestInterface $request The incoming HTTP request
     * @param RequestHandlerInterface $handler The next handler in the chain
     * @return ResponseInterface The response from the next handler
     * 
     * @example
     * // Basic middleware processing
     * $response = $middleware->process($request, $handler);
     * 
     * @example
     * // In middleware stack
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     $request = $this->addCorsHeaders($request);
     *     $request = $this->processAuth($request);
     *     return $this->paramsMiddleware->process($request, $handler);
     * }
     * 
     * @example
     * // Custom parameter processing
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     // Add custom processing before or after parameter extraction
     *     $request = $this->validateContentType($request);
     *     
     *     $response = parent::process($request, $handler);
     *     
     *     return $this->addDebugHeaders($response);
     * }
     */
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1️⃣ Buat parser menggunakan interface yang sama
        $parser = new class($request) implements DataParserInterface {
            public function __construct(private ServerRequestInterface $request) {}
            
            public function all(): array 
            {
                $query = $this->request->getQueryParams();
                $body = $this->request->getParsedBody() ?? [];
                
                if (\is_object($body)) {
                    $body = (array) $body;
                }
                
                return \array_merge($query, $body);
            }
            
            public function get(string $key, mixed $default = null): mixed 
            {
                $data = $this->all();
                return $data[$key] ?? $default;
            }
        };

        // 2️⃣ Buat RequestParams
        $params = new RequestParams($parser, $this->defaultPageSize, $this->maxPageSize);

        // 3️⃣ Simpan di request attribute
        $request = $request->withAttribute('paginationConfig', [
            'defaultPageSize' => $this->defaultPageSize,
            'maxPageSize'     => $this->maxPageSize,
        ]);
        $request = $request->withAttribute('payload', $params);

        return $handler->handle($request);
    }
}
