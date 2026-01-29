<?php

declare(strict_types=1);

namespace App\Shared\Dto;

/**
 * Data Transfer Object for paginated results
 * 
 * This class provides a standardized structure for returning paginated data
 * along with metadata about the pagination, filters, and sorting applied.
 * It's designed to be immutable and type-safe for consistent API responses.
 * 
 * @package App\Shared\Dto
 * 
 * @example
 * // Basic pagination result
 * $result = new PaginatedResult(
 *     data: [
 *         ['id' => 1, 'name' => 'John'],
 *         ['id' => 2, 'name' => 'Jane']
 *     ],
 *     total: 50,
 *     page: 1,
 *     pageSize: 10
 * );
 * 
 * @example
 * // Pagination with filters and sorting
 * $result = new PaginatedResult(
 *     data: $users,
 *     total: 150,
 *     page: 2,
 *     pageSize: 20,
 *     filter: ['status' => 'active', 'role' => 'user'],
 *     sort: ['name' => 'asc', 'created_at' => 'desc']
 * );
 * 
 * @example
 * // Usage in controller response
 * $result = new PaginatedResult(
 *     data: $repository->findAll($criteria),
 *     total: $repository->count($criteria),
 *     page: $criteria->page,
 *     pageSize: $criteria->pageSize,
 *     filter: $criteria->filter,
 *     sort: ['name' => $criteria->sortDir]
 * );
 * 
 * return [
 *     'data' => $result->data,
 *     'meta' => $result->getMeta(),
 *     'pagination' => [
 *         'current_page' => $result->page,
 *         'total_pages' => $result->getTotalPages(),
 *         'per_page' => $result->pageSize,
 *         'total' => $result->total
 *     ]
 * ];
 */
final readonly class PaginatedResult
{
    /**
     * Constructor for PaginatedResult
     * 
     * @param array $data The paginated data items
     * @param int $total Total number of items across all pages
     * @param int $page Current page number (1-based)
     * @param int $pageSize Number of items per page
     * @param array $filter Applied filters as key-value pairs
     * @param array $sort Applied sorting as field-direction pairs
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $pageSize,
        public array $filter = [],
        public array $sort = []
    ) {
        if ($this->pageSize <= 0) {
            $this->pageSize = 10;
        }
    }

    /**
     * Calculate the total number of pages
     * 
     * Returns the total pages needed to display all items based on the page size.
     * If there are no items, returns 1 to avoid division by zero.
     * 
     * @return int Total number of pages
     * 
     * @example
     * // 50 total items, 10 per page = 5 pages
     * $result = new PaginatedResult(
     *     data: [['id' => 1, 'name' => 'John']],
     *     total: 50,
     *     page: 1,
     *     pageSize: 10
     * );
     * echo $result->getTotalPages(); // Output: 5
     * 
     * @example
     * // 0 total items = 1 page (avoid division by zero)
     * $result = new PaginatedResult(
     *     data: [],
     *     total: 0,
     *     page: 1,
     *     pageSize: 10
     * );
     * echo $result->getTotalPages(); // Output: 1
     * 
     * @example
     * // 95 total items, 20 per page = 5 pages (rounded up)
     * $result = new PaginatedResult(
     *     data: array_fill(0, 15, ['id' => 1]),
     *     total: 95,
     *     page: 1,
     *     pageSize: 20
     * );
     * echo $result->getTotalPages(); // Output: 5
     */
    public function getTotalPages(): int
    {
        return $this->total === 0 ? 1 : (int) ceil($this->total / $this->pageSize);
    }

    /**
     * Get metadata for the paginated result
     * 
     * Returns an array containing metadata about the current result including
     * applied filters, sorting, and pagination information. This is useful
     * for API responses to provide clients with context about the data.
     * 
     * @return array Metadata containing filter, sort, and pagination information
     * 
     * @example
     * // Basic pagination metadata
     * $result = new PaginatedResult(
     *     data: [['id' => 1, 'name' => 'John']],
     *     total: 50,
     *     page: 1,
     *     pageSize: 10
     * );
     * print_r($result->getMeta());
     * // Output:
     * // [
     * //     'filter' => [],
     * //     'sort' => [],
     * //     'pagination' => [
     * //         'total' => 50,
     * //         'display' => 1,
     * //         'page' => 1,
     * //         'page_size' => 10
     * //     ]
     * // ]
     * 
     * @example
     * // Metadata with filters and sorting
     * $result = new PaginatedResult(
     *     data: $users,
     *     total: 150,
     *     page: 2,
     *     pageSize: 20,
     *     filter: ['status' => 'active', 'role' => 'user'],
     *     sort: ['name' => 'asc', 'created_at' => 'desc']
     * );
     * print_r($result->getMeta());
     * // Output:
     * // [
     * //     'filter' => ['status' => 'active', 'role' => 'user'],
     * //     'sort' => ['name' => 'asc', 'created_at' => 'desc'],
     * //     'pagination' => [
     * //         'total' => 150,
     * //         'display' => 20,
     * //         'page' => 2,
     * //         'page_size' => 20
     * //     ]
     * // ]
     * 
     * @example
     * // Usage in API response
     * $result = new PaginatedResult(
     *     data: $repository->findAll($criteria),
     *     total: $repository->count($criteria),
     *     page: $criteria->page,
     *     pageSize: $criteria->pageSize,
     *     filter: $criteria->filter,
     *     sort: ['name' => $criteria->sortDir]
     * );
     * 
     * return [
     *     'data' => $result->data,
     *     'meta' => $result->getMeta(),
     *     'pagination' => [
     *         'current_page' => $result->page,
     *         'total_pages' => $result->getTotalPages(),
     *         'per_page' => $result->pageSize,
     *         'total' => $result->total,
     *         'has_next_page' => $result->page < $result->getTotalPages(),
     *         'has_prev_page' => $result->page > 1
     *     ]
     * ];
     */
    public function getMeta(): array
    {
        return [
            'filter' => $this->filter,
            'sort' => $this->sort,
            'pagination' => [
                'total' => $this->total,
                'display' => count($this->data),
                'page' => $this->page,
                'page_size' => $this->pageSize,
                // 'total_pages' => $this->getTotalPages(),
            ],
        ];
    }
}