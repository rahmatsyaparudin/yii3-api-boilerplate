<?php

declare(strict_types=1);

namespace App\Domain\--help\Repository;

// Domain Layer
use App\Domain\--help\Entity\--help;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * --help Repository Interface
 * 
 * Pure repository pattern for --help aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface --helpRepositoryInterface
{
    public function insert(--help $--help): --help;

    public function update(--help $--help): --help;

    public function findById(int $id): ?--help;

    public function findByName(string $name): ?--help;

    public function existsByName(string $name): bool;

    public function delete(--help $--help): --help;

    public function restore(int $id): ?--help;

    public function list(SearchCriteria $criteria): PaginatedResult;
}
