<?php

declare(strict_types=1);

namespace App\Domain\SyncExample\Repository;

// Domain Layer
use App\Domain\SyncExample\Entity\SyncExample;
// Shared Layer
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;

/**
 * SyncExample Repository Interface.
 *
 * Pure repository pattern for SyncExample aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface SyncExampleRepositoryInterface
{
    public function getResource(): string;

    public function findById(int $id, ?int $status = null): ?SyncExample;

    public function findByName(string $name, ?int $status = null): ?SyncExample;

    public function findByUuid(string $uuid): ?SyncExample;

    public function existsByName(string $name, ?int $status = null): bool;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function insert(SyncExample $syncExample): SyncExample;

    /**
     * Insert the record together with its sync_queue outbox rows inside
     * one transaction (outbox pattern).
     *
     * @param array<int, array<string, mixed>> $queueRows
     */
    public function insertWithOutbox(SyncExample $syncExample, array $queueRows): SyncExample;

    public function update(SyncExample $syncExample): SyncExample;

    public function delete(SyncExample $syncExample): SyncExample;

    public function restore(int $id): ?SyncExample;
}
