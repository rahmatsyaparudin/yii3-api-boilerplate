<?php

declare(strict_types=1);

namespace App\Domain\Brand\Repository;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Application\BrandInput;
use App\Shared\Request\RawParams;
use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;

interface BrandRepositoryInterface
{
    /**
     * Find brand by name
     */
    public function findByName(string $name): ?Brand;

    /**
     * @return array<int, Brand>
     */
    public function list(?RawParams $params = null, ?PaginationParams $pagination = null, ?SortParams $sort = null): array;

    public function count(?RawParams $params = null): int;

    /**
     * @return Brand|null
     */
    public function findById(int $id): ?Brand;

    /**
     * @return Brand
     */
    public function create(BrandInput $input): Brand;

    /**
     * @return void
     */
    public function update(int $id, array $input): void;

    public function delete(int $id): bool;
}
