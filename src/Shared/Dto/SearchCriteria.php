<?php

declare(strict_types=1);

namespace App\Shared\Dto;

/**
 * Data Transfer Object for search criteria
 * 
 * This class encapsulates search parameters including filters, pagination,
 * and sorting options. It provides a type-safe way to pass search criteria
 * between application layers and ensures consistent parameter handling.
 * 
 * @package App\Shared\Dto
 * 
 * @example
 * // Basic search with pagination
 * // JSON:
 * // {
 * //     "filter": {
 * //         "status": "active"
 * //     },
 * //     "pagination": {
 * //         "page": 1,
 * //         "page_size": 10
 * //     }
 * // }
 * $criteria = new SearchCriteria(
 *     filter: ['status' => 'active'],
 *     page: 1,
 *     pageSize: 10
 * );
 * 
 * @example
 * // Exact format from your specification
 * // JSON:
 * // {
 * //     "filter": {
 * //         "id": 2,
 * //         "name": "acer",
 * //         "status": 3
 * //     },
 * //     "pagination": {
 * //         "page": 1,
 * //         "page_size": 10
 * //     },
 * //     "sort": {
 * //         "by": "name",
 * //         "dir": "desc"
 * //     }
 * // }
 * $criteria = new SearchCriteria(
 *     filter: [
 *         'id' => 2,
 *         'name' => 'acer',
 *         'status' => 3
 *     ],
 *     page: 1,
 *     pageSize: 10,
 *     sortBy: 'name',
 *     sortDir: 'desc',
 *     allowedSort: ['id' => 'id', 'name' => 'name', 'status' => 'status']
 * );
 * 
 * @example
 * // Product search with multiple filters
 * // JSON:
 * // {
 * //     "filter": {
 * //         "category": "electronics",
 * //         "brand": "samsung",
 * //         "price_min": 100,
 * //         "price_max": 1000
 * //     },
 * //     "pagination": {
 * //         "page": 2,
 * //         "page_size": 20
 * //     },
 * //     "sort": {
 * //         "by": "price",
 * //         "dir": "asc"
 * //     }
 * // }
 * $criteria = new SearchCriteria(
 *     filter: [
 *         'category' => 'electronics',
 *         'brand' => 'samsung',
 *         'price_min' => 100,
 *         'price_max' => 1000
 *     ],
 *     page: 2,
 *     pageSize: 20,
 *     sortBy: 'price',
 *     sortDir: 'asc',
 *     allowedSort: ['id' => 'id', 'name' => 'name', 'price' => 'price', 'created_at' => 'created_at']
 * );
 * 
 * @example
 * // Manual offset for cursor-based pagination
 * // JSON:
 * // {
 * //     "filter": {
 * //         "user_id": 123
 * //     },
 * //     "pagination": {
 * //         "page": 1,
 * //         "page_size": 50,
 * //         "offset": 100
 * //     },
 * //     "sort": {
 * //         "by": "id",
 * //         "dir": "asc"
 * //     }
 * // }
 * $criteria = new SearchCriteria(
 *     filter: ['user_id' => 123],
 *     page: 1,
 *     pageSize: 50,
 *     offset: 100,
 *     sortBy: 'id',
 *     sortDir: 'asc'
 * );
 * 
 * @example
 * // Usage in repository
 * $results = $repository->findByCriteria($criteria);
 * // Returns paginated results with applied filters and sorting
 */
final readonly class SearchCriteria
{
    /**
     * Constructor for SearchCriteria
     * 
     * @param array $filter Search filters as key-value pairs
     * @param int $page Current page number (1-based)
     * @param int $pageSize Number of items per page (default: 10)
     * @param string $sortBy Field to sort by (default: 'id')
     * @param string $sortDir Sort direction 'asc' or 'desc' (default: 'asc')
     * @param int|null $offset Manual offset override (optional)
     * @param array $allowedSort Allowed sortable fields with column mapping
     */
    public function __construct(
        public array $filter,
        public int $page,
        public int $pageSize = 10,
        public string $sortBy = 'id',
        public string $sortDir = 'asc',
        public ?int $offset = null,
        private array $allowedSort = ['id' => 'id'],
    ) {}

    /**
     * Calculate offset based on page and page size
     * 
     * Computes the database offset for pagination based on the current page
     * and page size. Uses zero-based indexing as expected by most databases.
     * 
     * @return int Calculated offset for database queries
     * 
     * @example
     * // JSON:
     * // {
     * //     "pagination": {
     * //         "page": 1,
     * //         "page_size": 10
     * //     }
     * // }
     * $criteria = new SearchCriteria(
     *     filter: [],
     *     page: 1,
     *     pageSize: 10
     * );
     * echo $criteria->calculateOffset(); // Output: 0
     * 
     * @example
     * // JSON:
     * // {
     * //     "pagination": {
     * //         "page": 3,
     * //         "page_size": 20
     * //     }
     * // }
     * $criteria = new SearchCriteria(
     *     filter: [],
     *     page: 3,
     *     pageSize: 20
     * );
     * echo $criteria->calculateOffset(); // Output: 40
     */
    public function calculateOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    /**
     * Get the offset for database queries
     * 
     * Returns the manual offset if set, otherwise calculates it based on
     * the current page and page size. This provides flexibility for both
     * automatic pagination and manual offset control.
     * 
     * @return int Offset value for database queries
     * 
     * @example
     * // JSON:
     * // {
     * //     "pagination": {
     * //         "page": 2,
     * //         "page_size": 10
     * //     }
     * // }
     * $criteria = new SearchCriteria(
     *     filter: [],
     *     page: 2,
    /**
     * Get order clause for database queries
     * 
     * Returns an array suitable for database query builders containing the
     * column name and sort direction. Validates the sort field against
     * allowed sort fields and defaults to the first allowed field if invalid.
     * 
     * @return array Order clause with column as key and direction as value
     * 
     * @example
     * // JSON:
     * // {
     * //     "sort": {
     * //         "by": "name",
     * //         "dir": "asc"
     * //     }
     * // }
     * $criteria = new SearchCriteria([], 1, 10, 'name', 'asc', allowedSort: ['id' => 'id', 'name' => 'name']);
     * print_r($criteria->getOrderClause());
     * // Output: ['name' => 256] (SORT_ASC = 256)
     * 
     * @example
     * // JSON:
     * // {
     * //     "sort": {
     * //         "by": "invalid_field",
     * //         "dir": "desc"
     * //     }
     * // }
     * // Falls back to first allowed field
     * $criteria = new SearchCriteria([], 1, 10, 'invalid_field', 'desc', allowedSort: ['id' => 'id', 'name' => 'name']);
     * print_r($criteria->getOrderClause());
     * // Output: ['id' => 256] (falls back to 'id')
     * 
     * @example
     * // JSON:
     * // {
     * //     "sort": {
     * //         "by": "created_at",
     * //         "dir": "desc"
     * //     }
     * // }
     * $criteria = new SearchCriteria([], 1, 10, 'created_at', 'desc', allowedSort: ['created_at' => 'created_at']);
     * print_r($criteria->getOrderClause());
     * // Output: ['created_at' => 512] (SORT_DESC = 512)
     */
    public function getOrderClause(): array
    {
        $column = $this->allowedSort[$this->sortBy] ?? array_values($this->allowedSort)[0];
        $direction = strtolower($this->sortDir) === 'desc' ? SORT_DESC : SORT_ASC;

        return [$column => $direction];
    }
}
