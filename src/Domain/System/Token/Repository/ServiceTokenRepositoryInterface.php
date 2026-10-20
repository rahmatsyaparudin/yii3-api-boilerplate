<?php

declare(strict_types=1);

namespace App\Domain\System\Token\Repository;

// Domain Layer
use App\Domain\System\Token\Entity\ServiceToken;
// Shared Layer
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;

/**
 * Service Token Repository Interface.
 *
 * Pure repository pattern for ServiceToken aggregate root operations.
 * Only handles aggregate root persistence and basic lookups.
 */
interface ServiceTokenRepositoryInterface
{
    public function getResource(): string;

    public function list(SearchCriteria $criteria): PaginatedResult;

    public function findById(int $id): ?ServiceToken;

    /**
     * Find an ACTIVE token row by its SHA-256 hash. Deleted/inactive
     * rows never match — revoked tokens die instantly.
     */
    public function findActiveByTokenHash(string $tokenHash): ?ServiceToken;

    /**
     * Username uniqueness across ALL rows (including deleted) —
     * `username` is the service identity and must stay unique forever.
     */
    public function existsUsername(string $username, ?int $ignoreId = null): bool;

    public function insert(ServiceToken $serviceToken): ServiceToken;

    public function update(ServiceToken $serviceToken): ServiceToken;

    /**
     * Stamp `last_used_at` on successful authentication — the caller
     * supplies the timestamp so it shares the app's time source.
     */
    public function touchLastUsed(int $id, string $lastUsedAt): void;
}
