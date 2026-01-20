<?php

declare(strict_types=1);

namespace App\Domain\Brand\Service;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Application\BrandInput;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\NoChangesException;
use App\Shared\Request\RawParams;
use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;
use App\Shared\Helper\DetailInfoHelper;
use App\Shared\Helper\ArrayHelper;

final readonly class BrandService
{
    private const RESOURCE = 'Brand';

    public function __construct(
        private BrandRepositoryInterface $repository,
        private DetailInfoHelper $detailInfoHelper,
    ) {
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function list(?RawParams $params = null, ?PaginationParams $pagination = null, ?SortParams $sort = null): array
    {
        $data = $this->repository->list(
            params: $params,
            pagination: $pagination,
            sort: $sort
        );
        
        return array_map(
            fn(Brand $b) => [
                'id' => $b->id(),
                'name' => $b->name(),
                'status' => $b->status()->value(),
                'detail_info' => $b->detailInfo(),
            ],
            $data
        );
    }

    public function count(?RawParams $params = null): int
    {
        return $this->repository->count(
            params: $params
        );
    }

    public function create(BrandInput $input): Brand
    {
        $createChangeLog = $this->detailInfoHelper->createChangeLog();
        $input->detailInfo = array_merge($input->getDetailInfo(), $createChangeLog);

        return $this->repository->create($input);
    }

    public function update(int $id, BrandInput $input): Brand
    {
        $existing = $this->repository->findById($id);
        if (!$existing) {
            throw new NotFoundException(
                translate: [
                    'key' => 'resource.not_found', 
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id
                    ]
                ]
            );
        }

        $detailInfo = array_merge(
            $input->getDetailInfo() ?? [],
            $this->detailInfoHelper->updateChangeLog($existing->detailInfo())
        );

        $updateData = $input->toUpdateArray($detailInfo);

        if (! ArrayHelper::hasDirtyData(
            after: $updateData, 
            before: $existing->toArray(), 
            exclude: ['detail_info.change_log'],
        )){
            throw new NoChangesException(
                translate: [
                    'key' => 'resource.no_changes_detected',
                    'params' => [
                        'resource' => self::RESOURCE,
                        'id' => $id
                    ]
                ],
                data: $existing->toArray()
            );
        }

        $this->repository->update(
            id: $id, 
            input: $updateData,
        );

        // Return entity Brand terbaru
        $finalData = array_merge($existing->toArray(), $updateData);
        return Brand::fromArray($finalData);
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
                    'key' => 'resource.not_found',
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id
                    ]
                ]
            );
        }

        return $brand->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function updates(int $id, ?string $name = null, ?int $status = null, array $detailInfo = []): array
    {
        return $this->repository->update(
            id: $id,
            name: $name,
            status: $status,
            detailInfo: $detailInfo
        );
    }

    public function delete(int $id): bool
    {
        return $this->repository->delete(
            id: $id
        );
    }
}
