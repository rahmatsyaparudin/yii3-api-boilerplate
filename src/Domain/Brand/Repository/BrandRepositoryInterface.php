<?php

declare(strict_types=1);

namespace App\Domain\Brand\Repository;

use App\Domain\Brand\Entity\Brand;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Brand Repository Interface
 * 
 * Pure repository pattern for Brand aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface BrandRepositoryInterface
{
    /**
     * Find brand by ID
     */
    public function findById(int $id): ?Brand;

    /**
     * Find brand by name
     */
    public function findByName(string $name): ?Brand;

    /**
     * Check if brand exists by name
     */
    public function existsByName(string $name): bool;

    /**
     * Save brand aggregate root
     */
    public function save(Brand $brand): Brand;

    /**
     * Delete brand aggregate root
     */
    public function delete(Brand $brand): void;

    /**
     * List brands with filtering, pagination, and sorting
     */
    public function list(SearchCriteria $criteria): PaginatedResult;
}
