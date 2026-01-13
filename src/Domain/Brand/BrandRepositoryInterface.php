<?php

declare(strict_types=1);

namespace App\Domain\Brand;

interface BrandRepositoryInterface
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit, int $offset, array $filters = [], ?string $sortBy = null, string $sortDir = 'asc'): array;

    public function count(array $filters = []): int;

    /**
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, int $status, array $detailInfo = [], ?int $syncMdb = null): array;

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, string $name, int $status, array $detailInfo = [], ?int $syncMdb = null): array;

    public function delete(int $id): void;
}
