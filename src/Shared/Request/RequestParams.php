<?php

declare(strict_types=1);

namespace App\Shared\Request;

// Shared Layer
use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;
use App\Shared\Request\DataParserInterface;

// PSR Interfaces
use Psr\Http\Message\ServerRequestInterface;

/**
 * Request Parameters Value Object
 * 
 * This readonly class provides a comprehensive interface for handling
 * request parameters including pagination, sorting, filtering, and raw
 * parameter access with validation and type safety.
 * 
 * @package App\Shared\Request
 * 
 * @example
 * // Basic usage with middleware
 * $params = $request->getAttribute('payload');
 * $pagination = $params->getPagination();
 * $sort = $params->getSort();
 * $filters = $params->getFilter()->all();
 * 
 * @example
 * // Creating from array data
 * $params = RequestParams::from([
 *     'filter' => ['status' => 'active'],
 *     'pagination' => ['page' => 2, 'page_size' => 25],
 *     'sort' => ['by' => 'name', 'dir' => 'desc']
 * ]);
 * 
 * @example
 * // In controller with validation
 * $params = RequestParams::fromRequest($request);
 * $criteria = new SearchCriteria(
 *     filter: $params->getFilter()->all(),
 *     page: $params->getPagination()->getPage(),
 *     pageSize: $params->getPagination()->getPageSize(),
 *     sortBy: $params->getSort()->getSortBy(),
 *     sortDir: $params->getSort()->getSortDir()
 * );
 * 
 * @example
 * // Custom configuration
 * $params = new RequestParams(
 *     parser: $customParser,
 *     defaultPageSize: 25,
 *     maxPageSize: 100
 * );
 * 
 * @example
 * // Accessing convenience methods
 * $page = $params->getPage();
 * $pageSize = $params->getPageSize();
 * $offset = $params->getOffset();
 * $withTotal = $params->withTotal();
 * 
 * @example
 * // Integration with repository
 * public function findByRequestParams(RequestParams $params): PaginatedResult
 * {
 *     $query = $this->createQuery();
 *     
 *     // Apply filters
 *     QueryConditionApplier::filterByExactMatch(
 *         query: $query,
 *         filters: $params->getFilter()->all(),
 *         allowedColumns: $this->allowedColumns
 *     );
 *     
 *     // Apply sorting
 *     if ($params->getSort()->getSortBy()) {
 *         $direction = $params->getSort()->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
 *         $query->orderBy([$params->getSort()->getSortBy() => $direction]);
 *     }
 *     
 *     // Apply pagination
 *     $query->limit($params->getPageSize());
 *     $query->offset($params->getOffset());
 *     
 *     return $this->paginate($query, $params);
 * }
 */
final readonly class RequestParams
{
    private const DEFAULT_PAGE_SIZE = 50;
    private const MAX_PAGE_SIZE = 200;
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_SORT_DIR = 'asc';

    private RawParams $rawParams;
    private RawParams $filter;
    private PaginationParams $pagination;
    private SortParams $sort;

    /**
     * Request Parameters constructor
     * 
     * Creates a new RequestParams object from a data parser with validation
     * and automatic parameter extraction for pagination, sorting, and filtering.
     * 
     * @param DataParserInterface $parser Data parser interface
     * @param int $defaultPageSize Default page size (default: 50)
     * @param int $maxPageSize Maximum allowed page size (default: 200)
     * 
     * @example
     * // Default configuration
     * $params = new RequestParams($parser);
     * 
     * @example
     * // Custom configuration using named arguments
     * $params = new RequestParams(
     *     parser: $parser,
     *     defaultPageSize: 25,
     *     maxPageSize: 100
     * );
     * 
     * @example
     * // High-performance configuration
     * $params = new RequestParams(
     *     parser: $parser,
     *     defaultPageSize: 10,
     *     maxPageSize: 50
     * );
     * 
     * @example
     * // Bulk data configuration
     * $params = new RequestParams(
     *     parser: $parser,
     *     defaultPageSize: 100,
     *     maxPageSize: 500
     * );
     */
    public function __construct(
        DataParserInterface $parser,
        int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        int $maxPageSize = self::MAX_PAGE_SIZE
    ) {
        $rawData = $parser->all();
        $this->rawParams = new RawParams($rawData);
        
        // Extract filter parameters
        $filterData = $rawData['filter'] ?? [];
        $this->filter = new RawParams($filterData);
        
        // Handle nested pagination structure
        $paginationData = $rawData['pagination'] ?? [];
        if (empty($paginationData)) {
            // Fallback to root level for backward compatibility
            $pageParam = $rawData['page'] ?? self::DEFAULT_PAGE;
            $pageSizeParam = $rawData['page_size'] ?? $defaultPageSize;
        } else {
            // Use nested pagination structure
            $pageParam = $paginationData['page'] ?? self::DEFAULT_PAGE;
            $pageSizeParam = $paginationData['page_size'] ?? $defaultPageSize;
        }
        
        // Validate page parameter
        if (!is_numeric($pageParam)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'pagination.invalid_parameter', 
                    params: [
                        'parameter' => 'page'
                    ]
                )
            );
        }
        $page = max(self::DEFAULT_PAGE, (int) $pageParam);
        
        // Validate page_size parameter
        if (!is_numeric($pageSizeParam)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'pagination.invalid_parameter', 
                    params: [
                        'parameter' => 'page_size'
                    ]
                )
            );
        }
        $pageSize = max(1, min($maxPageSize, (int) $pageSizeParam));
        $this->pagination = new PaginationParams(page: $page, page_size: $pageSize);
        
        // Handle nested sort structure
        $sortData = $rawData['sort'] ?? [];
        if (empty($sortData)) {
            // Fallback to root level for backward compatibility
            $sortData = $rawData;
        }
        $this->sort = new SortParams(
            by: $sortData['by'] ?? null,
            dir: $sortData['dir'] ?? self::DEFAULT_SORT_DIR
        );
    }

    // ====== MAIN GETTERS ======

    /**
     * Get raw parameters object
     * 
     * Returns the RawParams object containing all request parameters
     * including pagination, sorting, and filter data.
     * 
     * @return RawParams Raw parameters object
     * 
     * @example
     * // Access all raw parameters
     * $rawParams = $params->getRawParams();
     * $allParams = $rawParams->all();
     * 
     * @example
     * // Check for specific parameter
     * $rawParams = $params->getRawParams();
     * if ($rawParams->has('search')) {
     *     $searchTerm = $rawParams->get('search');
     * }
     * 
     * @example
     * // In service for debugging
     * public function debugParams(RequestParams $params): void
     * {
     *     $rawParams = $params->getRawParams();
     *     $this->logger->debug('Request parameters', $rawParams->all());
     * }
     */
    public function getRawParams(): RawParams
    {
        return $this->rawParams;
    }

    /**
     * Get filter parameters object
     * 
     * Returns the RawParams object containing only filter parameters
     * extracted from the 'filter' key in the request data.
     * 
     * @return RawParams Filter parameters object
     * 
     * @example
     * // Access filter parameters
     * $filters = $params->getFilter();
     * $statusFilter = $filters->get('status');
     * $nameFilter = $filters->get('name');
     * 
     * @example
     * // Apply filters to query
     * $filters = $params->getFilter()->all();
     * QueryConditionApplier::filterByExactMatch($query, $filters, $allowedColumns);
     * 
     * @example
     * // Check for specific filter
     * $filters = $params->getFilter();
     * if ($filters->has('category')) {
     *     $category = $filters->get('category');
     * }
     */
    public function getFilter(): RawParams
    {
        return $this->filter;
    }

    /**
     * Get pagination parameters object
     * 
     * Returns the PaginationParams object containing page number
     * and page size with validation and utility methods.
     * 
     * @return PaginationParams Pagination parameters object
     * 
     * @example
     * // Access pagination parameters
     * $pagination = $params->getPagination();
     * $page = $pagination->getPage();
     * $pageSize = $pagination->getPageSize();
     * 
     * @example
     * // Apply pagination to query
     * $pagination = $params->getPagination();
     * $query->limit($pagination->getLimit());
     * $query->offset($pagination->getOffset());
     * 
     * @example
     * // Get pagination metadata
     * $pagination = $params->getPagination();
     * $paginationData = $pagination->toArray();
     */
    public function getPagination(): PaginationParams
    {
        return $this->pagination;
    }

    /**
     * Get sort parameters object
     * 
     * Returns the SortParams object containing sort field and direction
     * with validation and utility methods for query building.
     * 
     * @return SortParams Sort parameters object
     * 
     * @example
     * // Access sort parameters
     * $sort = $params->getSort();
     * $sortBy = $sort->getSortBy();
     * $sortDir = $sort->getSortDir();
     * 
     * @example
     * // Apply sorting to query
     * $sort = $params->getSort();
     * if ($sort->getSortBy()) {
     *     $direction = $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
     *     $query->orderBy([$sort->getSortBy() => $direction]);
     * }
     * 
     * @example
     * // Get sort metadata
     * $sort = $params->getSort();
     * $sortData = $sort->toArray();
     */
    public function getSort(): SortParams
    {
        return $this->sort;
    }

    /**
     * Get current page number
     * 
     * Returns the current page number from pagination parameters.
     * Convenience method for quick access to page value.
     * 
     * @return int Current page number (1-based)
     * 
     * @example
     * // Get current page
     * $page = $params->getPage();
     * echo "Showing page $page";
     * 
     * @example
     * // In pagination controls
     * $currentPage = $params->getPage();
     * $totalPages = ceil($totalItems / $params->getPageSize());
     * 
     * @example
     * // In service layer
     * public function getItems(RequestParams $params): array
     * {
     *     $page = $params->getPage();
     *     return $this->repository->findByPage($page, $params->getPageSize());
     * }
     */
    public function getPage(): int
    {
        return $this->pagination->page;
    }

    /**
     * Get page size
     * 
     * Returns the page size from pagination parameters.
     * Convenience method for quick access to page size value.
     * 
     * @return int Number of items per page
     * 
     * @example
     * // Get page size
     * $pageSize = $params->getPageSize();
     * echo "Showing $pageSize items per page";
     * 
     * @example
     * // In query building
     * $query->limit($params->getPageSize());
     * 
     * @example
     * // In pagination calculations
     * $pageSize = $params->getPageSize();
     * $offset = ($params->getPage() - 1) * $pageSize;
     */
    public function getPageSize(): int
    {
        return $this->pagination->page_size;
    }

    /**
     * Get offset for database queries
     * 
     * Returns the calculated offset from pagination parameters.
     * Convenience method for quick access to offset value.
     * 
     * @return int Offset value for database queries
     * 
     * @example
     * // Get offset
     * $offset = $params->getOffset();
     * $query->offset($offset);
     * 
     * @example
     * // In repository
     * public function findByPage(RequestParams $params): array
     * {
     *     $query = $this->createQuery();
     *     $query->limit($params->getPageSize());
     *     $query->offset($params->getOffset());
     *     return $query->all();
     * }
     * 
     * @example
     * // Debug pagination
     * $page = $params->getPage();
     * $pageSize = $params->getPageSize();
     * $offset = $params->getOffset();
     * echo "Page: $page, Size: $pageSize, Offset: $offset";
     */
    public function getOffset(): int
    {
        return $this->pagination->getOffset();
    }

    // ====== CONVENIENCE METHODS ======

    /**
     * Get a specific parameter value
     * 
     * Returns a parameter value from the raw parameters with optional default.
     * Convenience method for quick access to individual parameters.
     * 
     * @param string $key Parameter key
     * @param mixed $default Default value if parameter not found
     * @return mixed Parameter value or default
     * 
     * @example
     * // Get specific parameter
     * $search = $params->get('search', '');
     * $category = $params->get('category', 'all');
     * 
     * @example
     * // In controller
     * public function listAction(RequestParams $params): array
     * {
     *     $search = $params->get('search');
     *     $category = $params->get('category');
     *     return $this->service->search($search, $category, $params);
     * }
     * 
     * @example
     * // With default values
     * $limit = $params->get('limit', 50);
     * $sort = $params->get('sort', 'name');
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->rawParams->get($key, $default);
    }

    /**
     * Check if a parameter exists
     * 
     * Returns true if the specified parameter key exists in the raw parameters.
     * Convenience method for parameter existence checking.
     * 
     * @param string $key Parameter key to check
     * @return bool True if parameter exists
     * 
     * @example
     * // Check for parameter existence
     * if ($params->has('search')) {
     *     $searchTerm = $params->get('search');
     *     // Apply search logic
     * }
     * 
     * @example
     * // Conditional filtering
     * if ($params->has('category')) {
     *     $category = $params->get('category');
     *     $query->andWhere(['category' => $category]);
     * }
     * 
     * @example
     * // Feature flags
     * if ($params->has('include_deleted')) {
     *     $includeDeleted = (bool) $params->get('include_deleted');
     * }
     */
    public function has(string $key): bool
    {
        return $this->rawParams->has($key);
    }

    /**
     * Get all parameters as array
     * 
     * Returns all raw parameters as an associative array.
     * Convenience method for accessing all parameters at once.
     * 
     * @return array All parameters as associative array
     * 
     * @example
     * // Get all parameters
     * $allParams = $params->all();
     * 
     * @example
     * // For logging
     * $this->logger->info('Request parameters', $params->all());
     * 
     * @example
     * // For caching
     * $cacheKey = md5(serialize($params->all()));
     * 
     * @example
     * // For debugging
     * $debugData = [
     *     'params' => $params->all(),
     *     'pagination' => $params->getPagination()->toArray(),
     *     'sort' => $params->getSort()->toArray()
     * ];
     */
    public function all(): array
    {
        return $this->rawParams->all();
    }

    /**
     * Check if total count is requested
     * 
     * Returns true if the 'with_total' parameter is set to a non-zero value.
     * Useful for determining whether to include total count in responses.
     * 
     * @return bool True if total count is requested
     * 
     * @example
     * // Check for total count request
     * if ($params->withTotal()) {
     *     $totalCount = $this->repository->countAll();
     *     $response['total'] = $totalCount;
     * }
     * 
     * @example
     * // In service layer
     * public function list(RequestParams $params): array
     * {
     *     $data = $this->getData($params);
     *     $response = ['data' => $data];
     *     
     *     if ($params->withTotal()) {
     *         $response['total'] = $this->getTotalCount();
     *     }
     *     
     *     return $response;
     * }
     * 
     * @example
     * // URL: /api/users?with_total=1
     * $params = RequestParams::fromRequest($request);
     * $includeTotal = $params->withTotal(); // true
     */
    public function withTotal(): bool
    {
        return ($this->rawParams->get('with_total') ?? '0') !== '0';
    }

    // ====== STATIC FACTORY ======

    /**
     * Create RequestParams from PSR request attribute
     * 
     * Extracts RequestParams object from a PSR server request attribute.
     * Typically used after RequestParamsMiddleware has processed the request.
     * 
     * @param ServerRequestInterface $request PSR server request
     * @param string $attribute Attribute name where RequestParams is stored
     * @return self RequestParams instance
     * @throws BadRequestException If RequestParams not found in attribute
     * 
     * @example
     * // In controller
     * $params = RequestParams::fromRequest($request);
     * 
     * @example
     * // With custom attribute name
     * $params = RequestParams::fromRequest($request, 'custom_params');
     * 
     * @example
     * // In action method
     * public function listAction(ServerRequestInterface $request): array
     * {
     *     $params = RequestParams::fromRequest($request);
     *     return $this->service->list($params);
     * }
     * 
     * @example
     * // With error handling
     * try {
     *     $params = RequestParams::fromRequest($request);
     * } catch (BadRequestException $e) {
     *     // Handle missing parameters
     *     return $this->errorResponse($e);
     * }
     */
    public static function fromRequest(ServerRequestInterface $request, string $attribute = 'payload'): self
    {
        $params = $request->getAttribute($attribute);
        if (!$params instanceof self) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'http.missing_request_params', 
                    params: [
                        'parameter' => 'page'
                    ]
                )
            );
        }

        return $params;
    }

    // ====== STATIC CREATION ======

    /**
     * Create RequestParams from array data
     * 
     * Creates a new RequestParams instance from an associative array.
     * Useful for testing, CLI commands, or manual parameter creation.
     * 
     * @param array $data Request data array
     * @param int $defaultPageSize Default page size (default: 50)
     * @param int $maxPageSize Maximum allowed page size (default: 200)
     * @return self RequestParams instance
     * 
     * @example
     * // Basic creation
     * $params = RequestParams::from([
     *     'page' => 2,
     *     'page_size' => 25,
     *     'sort' => ['by' => 'name', 'dir' => 'desc']
     * ]);
     * 
     * @example
     * // With nested structure
     * $params = RequestParams::from([
     *     'filter' => ['status' => 'active'],
     *     'pagination' => ['page' => 1, 'page_size' => 50],
     *     'sort' => ['by' => 'created_at', 'dir' => 'desc'],
     *     'with_total' => '1'
     * ]);
     * 
     * @example
     * // Custom configuration using named arguments
     * $params = RequestParams::from(
     *     data: $requestData,
     *     defaultPageSize: 25,
     *     maxPageSize: 100
     * );
     * 
     * @example
     * // In testing
     * $params = RequestParams::from([
     *     'filter' => ['name' => 'test'],
     *     'page' => 1,
     *     'page_size' => 10
     * ]);
     * $result = $service->list($params);
     */
    public static function from(array $data, int $defaultPageSize = self::DEFAULT_PAGE_SIZE, int $maxPageSize = self::MAX_PAGE_SIZE): self
    {
        // Create a simple parser that implements our interface
        $parser = new class($data) implements DataParserInterface {
            public function __construct(private array $data) {}
            public function all(): array { return $this->data; }
            public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
        };

        return new self($parser, $defaultPageSize, $maxPageSize);
    }
}
