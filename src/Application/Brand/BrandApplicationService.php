<?php

declare(strict_types=1);

namespace App\Application\Brand;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Brand\Service\BrandDomainService;
use App\Domain\Shared\ValueObject\Status;
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\NotFoundException;
use App\Application\Shared\Factory\DetailInfoFactory;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;
use App\Shared\ValueObject\Message;

/**
 * Brand Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class BrandApplicationService
{
    public function __construct(
        private DetailInfoFactory $detailInfoFactory,
        private BrandRepositoryInterface $repository,
        private BrandDomainService $domainService
    ) {}

    /**
     * Create new brand
     */
    public function create(array $data): Brand
    {
        // Domain validation
        $this->domainService->validateUniqueName($data['name']);

        $detailInfo = $this->detailInfoFactory->create(
            payload: []
        );

        // Create brand entity
        $brand = Brand::create(
            name: $data['name'],
            status: Status::from($data['status'] ?? Status::DRAFT->value),
            detailInfo: $detailInfo,
            syncMdb: $data['sync_mdb'] ?? null
        );

        // Save and return
        return $this->repository->save($brand);
    }

    /**
     * Get brand by ID
     */
    public function get(int $id): Brand
    {
        $brand = $this->repository->findById($id);
        
        if ($brand === null) {
            throw new NotFoundException(
                translate: new Message(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => 'Brand',
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $brand;
    }

    /**
     * Update brand
     */
    public function update(int $id, array $data): Brand
    {
        $brand = $this->get($id);

        // Update name if provided
        if (isset($data['name'])) {
            $this->domainService->validateNameChange($brand, $data['name']);
            $brand->changeName($data['name']);
        }

        // Update status if provided
        if (isset($data['status'])) {
            $newStatus = Status::from($data['status']);
            if ($brand->getStatus()->value() !== $newStatus->value()) {
                $this->domainService->validateStatusTransition($brand->getStatus(), $newStatus);
                $brand->changeStatus($newStatus);
            }
        }

        // Update sync_mdb if provided
        if (isset($data['sync_mdb'])) {
            $brand->updateSyncMdb($data['sync_mdb']);
        }

        $detailInfo = $this->detailInfoFactory->update(
            detailInfo: $brand->getDetailInfo(), 
            payload: []
        );
        $brand->updateDetailInfo($detailInfo);

        // Save and return
        $this->repository->save($brand);
        return $brand;
    }

    /**
     * Delete brand
     */
    public function delete(int $id): void
    {
        $brand = $this->get($id);
        
        // Domain validation
        $this->domainService->canDeleteBrand($brand);
        
        $this->repository->delete($brand);
    }

    /**
     * List brands with filtering, pagination, sorting
     */
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list($criteria);
    }
}
