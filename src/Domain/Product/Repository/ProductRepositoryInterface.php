<?php

declare(strict_types=1);

namespace App\Domain\Product\Repository;

// Domain Layer
use App\Domain\Product\Entity\Product;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Product Repository Interface
 * 
 * Pure repository pattern for Product aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface ProductRepositoryInterface
{
    public function insert(Product $product): Product;

    public function update(Product $product): Product;

    public function findById(int $id): ?Product;

    public function findByName(string $name): ?Product;

    public function existsByName(string $name): bool;

    public function delete(Product $product): Product;

    public function restore(int $id): ?Product;

    public function list(SearchCriteria $criteria): PaginatedResult;
}
