<?php

declare(strict_types=1);

namespace App\Domain\Brand\Repository;

/**
 * Brand Query Service Interface
 * 
 * Handles complex queries, pagination, sorting, and filtering.
 * Separated from repository to maintain DDD principles.
 */
interface BrandQueryServiceInterface
{
    /**
     * Get paginated list of brands
     */
    public function list(array $params = []): array;

    /**
     * Count brands by filter
     */
    public function count(array $filter = []): int;

    /**
     * Search brands by text
     */
    public function search(string $searchTerm, ?int $limit = null): array;
}
