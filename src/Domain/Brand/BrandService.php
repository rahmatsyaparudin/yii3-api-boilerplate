<?php

declare(strict_types=1);

namespace App\Domain\Brand;

use App\Shared\Constants\StatusEnum;
use App\Shared\Exception\NotFoundException;

final readonly class BrandService
{
    public function __construct(
        private BrandRepositoryInterface $repository,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(int $limit, int $offset, array $filters = [], ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        return $this->repository->list($limit, $offset, $filters, $sortBy, $sortDir);
    }

    public function count(array $filters = []): int
    {
        return $this->repository->count($filters);
    }

    /**
     * @return array<string, mixed>
     */
    public function get(int $id): array
    {
        $brand = $this->repository->findById($id);
        if ($brand === null) {
            throw new NotFoundException(
                translate: [
                    'key' => 'resource.not_found',
                    'params' => ['resource' => 'Brand']
                ]
            );
        }

        return $brand;
    }

    /**
     * @return array<string, mixed>
     */
    public function create(string $name, int $status = StatusEnum::ACTIVE->value, array $detailInfo = []): array
    {
        return $this->repository->create($name, $status, $detailInfo);
    }

    /**
     * @return array<string, mixed>
     */
    public function update(int $id, string $name, int $status, array $detailInfo = []): array
    {
        return $this->repository->update($id, $name, $status, $detailInfo);
    }

    public function delete(int $id): void
    {
        $this->repository->delete($id);
    }
}
