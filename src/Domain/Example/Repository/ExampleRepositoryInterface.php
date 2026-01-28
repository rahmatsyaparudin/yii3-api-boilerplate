<?php

declare(strict_types=1);

namespace App\Domain\Example\Repository;

use App\Domain\Example\Entity\Example;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Example Repository Interface
 * 
 * Pure repository pattern for Example aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface ExampleRepositoryInterface
{
    public function findById(int $id): ?Example;

    public function findByName(string $name): ?Example;

    public function existsByName(string $name): bool;

    public function save(Example $example): Example;

    public function delete(Example $example): Example;

    public function restore(int $id): ?Example;

    public function list(SearchCriteria $criteria): PaginatedResult;
}
