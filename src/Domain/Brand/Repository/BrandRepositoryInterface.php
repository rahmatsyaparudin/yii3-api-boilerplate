<?php

declare(strict_types=1);

namespace App\Domain\Brand\Repository;

use App\Domain\Brand\Entity\Brand;
use App\Shared\Request\RawParams;
use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;

interface BrandRepositoryInterface
{
    /**
     * Find brand by name
     */
    public function findByName(string $name): ?array;

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?RawParams $params = null, ?PaginationParams $pagination = null, ?SortParams $sort = null): array;

    public function count(?RawParams $params = null): int;

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
