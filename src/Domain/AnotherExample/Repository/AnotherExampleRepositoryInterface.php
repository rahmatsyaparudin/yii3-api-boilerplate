<?php

declare(strict_types=1);

namespace App\Domain\AnotherExample\Repository;

// Domain Layer
use App\Domain\AnotherExample\Entity\AnotherExample;

// Shared Layer
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * AnotherExample Repository Interface
 * 
 * Pure repository pattern for AnotherExample aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface AnotherExampleRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?AnotherExample;

    public function findByName(string $name, ?int $status = null): ?AnotherExample;

    public function existsByName(string $name, ?int $status = null): bool;
    
    public function list(SearchCriteria $criteria): PaginatedResult;
    
    public function insert(AnotherExample $anotherExample): AnotherExample;

    public function update(AnotherExample $anotherExample): AnotherExample;

    public function delete(AnotherExample $anotherExample): AnotherExample;

    public function restore(int $id): ?AnotherExample;

}
