<?php

declare(strict_types=1);

namespace App\Shared\Request;

/**
 * Pagination Parameters Value Object
 * 
 * This readonly class encapsulates pagination parameters for API requests,
 * providing type-safe access to page number and page size with validation
 * and utility methods for database query building.
 * 
 * @package App\Shared\Request
 * 
 * @example
 * // Basic pagination with defaults
 * $pagination = new PaginationParams();
 * echo $pagination->getPage(); // 1
 * echo $pagination->getPageSize(); // 50
 * 
 * @example
 * // Custom pagination using named arguments
 * $pagination = new PaginationParams(
 *     page: 2,
 *     page_size: 25
 * );
 * echo $pagination->getOffset(); // 25
 * echo $pagination->getLimit(); // 25
 * 
 * @example
 * // In controller with request parameters
 * $page = (int) $request->getQueryParam('page', 1);
 * $pageSize = (int) $request->getQueryParam('page_size', 50);
 * $pagination = new PaginationParams(
 *     page: $page,
 *     page_size: $pageSize
 * );
 * 
 * @example
 * // In repository for database queries
 * $query = $this->createQuery();
 * $query->limit($pagination->getLimit());
 * $query->offset($pagination->getOffset());
 * $results = $query->all();
 * 
 * @example
 * // Converting to array for API responses
 * $pagination = new PaginationParams(page: 3, page_size: 20);
 * $paginationData = $pagination->toArray();
 * // Returns: ['page' => 3, 'page_size' => 20]
 * 
 * @example
 * // Integration with SearchCriteria
 * $criteria = new SearchCriteria(
 *     filter: $filters,
 *     page: $pagination->getPage(),
 *     pageSize: $pagination->getPageSize()
 * );
 */
final readonly class PaginationParams
{
    /**
     * Pagination Parameters constructor
     * 
     * Creates a new pagination parameters object with page number and page size.
     * Uses 1-based page numbering as expected by most APIs.
     * 
     * @param int $page Current page number (1-based, default: 1)
     * @param int $page_size Number of items per page (default: 50)
     * 
     * @example
     * // Default pagination
     * $pagination = new PaginationParams();
     * 
     * @example
     * // Custom page and size using named arguments
     * $pagination = new PaginationParams(
     *     page: 2,
     *     page_size: 25
     * );
     * 
     * @example
     * // Large page size for data export
     * $pagination = new PaginationParams(
     *     page: 1,
     *     page_size: 1000
     * );
     * 
     * @example
     * // Small page size for mobile apps
     * $pagination = new PaginationParams(
     *     page: 1,
     *     page_size: 10
     * );
     */
    public function __construct(
        public int $page = 1,
        public int $page_size = 50
    ) {
    }

    /**
     * Get the limit for database queries
     * 
     * Returns the page size value suitable for use in LIMIT clauses
     * for database queries or pagination controls.
     * 
     * @return int Number of items per page
     * 
     * @example
     * // In repository query building
     * $query = $this->createQuery();
     * $query->limit($pagination->getLimit());
     * 
     * @example
     * // For pagination controls
     * $itemsPerPage = $pagination->getLimit();
     * echo "Showing $itemsPerPage items per page";
     * 
     * @example
     * // In service layer
     * public function getPaginatedResults(PaginationParams $pagination): array
     * {
     *     $query = $this->buildQuery();
     *     $query->limit($pagination->getLimit());
     *     $query->offset($pagination->getOffset());
     *     return $query->all();
     * }
     */
    public function getLimit(): int
    {
        return $this->page_size;
    }

    /**
     * Get the offset for database queries
     * 
     * Calculates the database offset based on the current page and page size.
     * Uses 0-based indexing as expected by most database systems.
     * 
     * @return int Offset value for database queries
     * 
     * @example
     * // In repository query building
     * $query = $this->createQuery();
     * $query->offset($pagination->getOffset());
     * 
     * @example
     * // Page 1, size 50 -> offset 0
     * $pagination = new PaginationParams(page: 1, page_size: 50);
     * echo $pagination->getOffset(); // 0
     * 
     * @example
     * // Page 3, size 25 -> offset 50
     * $pagination = new PaginationParams(page: 3, page_size: 25);
     * echo $pagination->getOffset(); // 50
     * 
     * @example
     * // In service layer with validation
     * public function validatePagination(PaginationParams $pagination): void
     * {
     *     if ($pagination->getOffset() < 0) {
     *         throw new InvalidArgumentException('Invalid pagination offset');
     *     }
     * }
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->page_size;
    }

    /**
     * Convert pagination parameters to array
     * 
     * Returns an associative array representation of the pagination
     * parameters, useful for API responses or serialization.
     * 
     * @return array Array with page and page_size keys
     * 
     * @example
     * // For API response metadata
     * $pagination = new PaginationParams(page: 2, page_size: 25);
     * $metadata = $pagination->toArray();
     * // Returns: ['page' => 2, 'page_size' => 25]
     * 
     * @example
     * // In controller response
     * public function listAction(ServerRequestInterface $request): array
     * {
     *     $pagination = $this->getPaginationParams($request);
     *     $results = $this->service->list($pagination);
     *     
     *     return [
     *         'data' => $results,
     *         'pagination' => $pagination->toArray()
     *     ];
     * }
     * 
     * @example
     * // For logging and debugging
     * $pagination = new PaginationParams(page: 5, page_size: 20);
     * $this->logger->info('Pagination request', $pagination->toArray());
     * 
     * @example
     * // In service for caching keys
     * public function getCacheKey(PaginationParams $pagination): string
     * {
     *     $params = $pagination->toArray();
     *     return 'list_' . md5(serialize($params));
     * }
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'page_size' => $this->page_size,
        ];
    }
}
