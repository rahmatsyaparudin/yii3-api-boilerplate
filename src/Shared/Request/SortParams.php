<?php

declare(strict_types=1);

namespace App\Shared\Request;

/**
 * Sort Parameters Value Object
 * 
 * This readonly class encapsulates sorting parameters for API requests,
 * providing type-safe access to sort field and direction with validation
 * and utility methods for database query building.
 * 
 * @package App\Shared\Request
 * 
 * @example
 * // Basic sorting with defaults
 * $sort = new SortParams();
 * echo $sort->getSortBy(); // null
 * echo $sort->getSortDir(); // 'asc'
 * 
 * @example
 * // Custom sorting using named arguments
 * $sort = new SortParams(
 *     by: 'name',
 *     dir: 'desc'
 * );
 * echo $sort->getSortBy(); // 'name'
 * echo $sort->getSortDir(); // 'desc'
 * 
 * @example
 * // In controller with request parameters
 * $sortBy = $request->getQueryParam('sort_by');
 * $sortDir = $request->getQueryParam('sort_dir', 'asc');
 * $sort = new SortParams(
 *     by: $sortBy,
 *     dir: $sortDir
 * );
 * 
 * @example
 * // In repository for database queries
 * $query = $this->createQuery();
 * if ($sort->getSortBy()) {
 *     $query->orderBy([$sort->getSortBy() => $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC]);
 * }
 * $results = $query->all();
 * 
 * @example
 * // Converting to array for API responses
 * $sort = new SortParams(by: 'created_at', dir: 'desc');
 * $sortData = $sort->toArray();
 * // Returns: ['by' => 'created_at', 'dir' => 'desc']
 * 
 * @example
 * // Integration with SearchCriteria
 * $criteria = new SearchCriteria(
 *     filter: $filters,
 *     page: $pagination->getPage(),
 *     pageSize: $pagination->getPageSize(),
 *     sortBy: $sort->getSortBy(),
 *     sortDir: $sort->getSortDir()
 * );
 */
final readonly class SortParams
{
    /**
     * Sort Parameters constructor
     * 
     * Creates a new sort parameters object with field and direction.
     * Supports both ascending and descending sort directions.
     * 
     * @param string|null $by Field to sort by (null for no sorting)
     * @param string $dir Sort direction 'asc' or 'desc' (default: 'asc')
     * 
     * @example
     * // Default sorting (no sort)
     * $sort = new SortParams();
     * 
     * @example
     * // Ascending sort using named arguments
     * $sort = new SortParams(
     *     by: 'name',
     *     dir: 'asc'
     * );
     * 
     * @example
     * // Descending sort
     * $sort = new SortParams(
     *     by: 'created_at',
     *     dir: 'desc'
     * );
     * 
     * @example
     * // No sorting specified
     * $sort = new SortParams(by: null, dir: 'asc');
     */
    public function __construct(
        public ?string $by = null,
        public string $dir = 'asc'
    ) {
    }

    /**
     * Get the sort field
     * 
     * Returns the field name to sort by, or null if no sorting
     * is specified. Useful for conditional query building.
     * 
     * @return string|null Field name or null
     * 
     * @example
     * // In repository query building
     * if ($sort->getSortBy()) {
     *     $query->orderBy([$sort->getSortBy() => $direction]);
     * }
     * 
     * @example
     * // For validation
     * $allowedFields = ['name', 'created_at', 'status'];
     * if ($sort->getSortBy() && !in_array($sort->getSortBy(), $allowedFields)) {
     *     throw new InvalidArgumentException('Invalid sort field');
     * }
     * 
     * @example
     * // In service layer
     * public function applySorting(SortParams $sort, Query $query): Query
     * {
     *     if ($sort->getSortBy()) {
     *         $direction = $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
     *         $query->orderBy([$sort->getSortBy() => $direction]);
     *     }
     *     return $query;
     * }
     */
    public function getSortBy(): ?string
    {
        return $this->by;
    }

    /**
     * Get the sort direction
     * 
     * Returns the sort direction string ('asc' or 'desc').
     * Always returns a value, defaulting to 'asc' if not specified.
     * 
     * @return string Sort direction ('asc' or 'desc')
     * 
     * @example
     * // In repository query building
     * $direction = $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
     * $query->orderBy([$sort->getSortBy() => $direction]);
     * 
     * @example
     * // For validation
     * $validDirections = ['asc', 'desc'];
     * if (!in_array($sort->getSortDir(), $validDirections)) {
     *     throw new InvalidArgumentException('Invalid sort direction');
     * }
     * 
     * @example
     * // In service layer
     * public function getDatabaseDirection(SortParams $sort): int
     * {
     *     return $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
     * }
     * 
     * @example
     * // For UI display
     * $currentDirection = $sort->getSortDir();
     * $nextDirection = $currentDirection === 'asc' ? 'desc' : 'asc';
     */
    public function getSortDir(): string
    {
        return $this->dir;
    }

    /**
     * Convert sort parameters to array
     * 
     * Returns an associative array representation of the sort
     * parameters, useful for API responses or serialization.
     * 
     * @return array Array with by and dir keys
     * 
     * @example
     * // For API response metadata
     * $sort = new SortParams(by: 'name', dir: 'desc');
     * $metadata = $sort->toArray();
     * // Returns: ['by' => 'name', 'dir' => 'desc']
     * 
     * @example
     * // In controller response
     * public function listAction(ServerRequestInterface $request): array
     * {
     *     $sort = $this->getSortParams($request);
     *     $results = $this->service->list($sort);
     *     
     *     return [
     *         'data' => $results,
     *         'sort' => $sort->toArray()
     *     ];
     * }
     * 
     * @example
     * // For logging and debugging
     * $sort = new SortParams(by: 'created_at', dir: 'desc');
     * $this->logger->info('Sort request', $sort->toArray());
     * 
     * @example
     * // In service for caching keys
     * public function getCacheKey(SortParams $sort): string
     * {
     *     $params = $sort->toArray();
     *     return 'list_' . md5(serialize($params));
     * }
     * 
     * @example
     * // For URL generation
     * $sort = new SortParams(by: 'name', dir: 'asc');
     * $queryParams = $sort->toArray();
     * $url = '/api/users?' . http_build_query($queryParams);
     * // Returns: '/api/users?by=name&dir=asc'
     */
    public function toArray(): array
    {
        return [
            'by' => $this->by,
            'dir' => $this->dir,
        ];
    }
}
