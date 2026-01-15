<?php

declare(strict_types=1);

namespace App\Domain\Brand\Service;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Request\RawParams;
use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;

final readonly class BrandService
{
    private const RESOURCE = 'Brand';

    public function __construct(
        private BrandRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?RawParams $params = null, ?PaginationParams $pagination = null, ?SortParams $sort = null): array
    {
        return $this->repository->list(
            params: $params,
            pagination: $pagination,
            sort: $sort
        );
    }

    public function count(?RawParams $params = null): int
    {
        return $this->repository->count(
            params: $params
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        $brand = $this->repository->findById(
            id: $id
        );

        if ($brand === null) {
            throw new NotFoundException(
                translate: [
                    'key' => 'db.not_found',
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id
                    ]
                ]
            );
        }

        return $brand;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, int $status = null, array $detailInfo = [], ?int $syncMdb = null): array
    {
        // Default to active status if not provided
        $status ??= Status::active();
        
        return $this->repository->create(
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, ?string $name = null, ?int $status = null, array $detailInfo = []): array
    {
        return $this->repository->update(
            id: $id,
            name: $name,
            status: $status,
            detailInfo: $detailInfo
        );
    }

    public function delete(int $id): void
    {
        $this->repository->delete(
            id: $id
        );
    }
}
