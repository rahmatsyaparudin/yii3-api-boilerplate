<?php

declare(strict_types=1);

namespace App\Domain\Example\Repository;

// Domain Layer
use App\Domain\Example\Entity\Example;

// Shared Layer
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
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?Example;

    public function findByName(string $name, ?int $status = null): ?Example;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(Example $example): Example;

    public function update(Example $example): Example;

    public function delete(Example $example): Example;

    public function restore(int $id): ?Example;

}
