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
    public function findById(int $id): ?Brand;

    public function findByName(string $name): ?Brand;

    public function existsByName(string $name): bool;

    public function save(Brand $brand): Brand;

    public function delete(Brand $brand): Brand;

    public function restore(int $id): ?Brand;

    public function list(SearchCriteria $criteria): PaginatedResult;
}
