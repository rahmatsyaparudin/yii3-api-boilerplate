<?php

declare(strict_types=1);

namespace App\Application\Brand;

use App\Application\Brand\Command\CreateBrandCommand;
use App\Application\Brand\Command\UpdateBrandCommand;
use App\Application\Brand\Dto\BrandResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Brand\Service\BrandDomainService;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Security\AuthorizerInterface;

use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Brand Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class BrandApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private BrandRepositoryInterface $repository,
        private BrandDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return Brand::RESOURCE;
    }

    private function getEntityById(int $id): Brand
    {
        $brand = $this->repository->findById($id);
        
        if ($brand === null) {
            throw new NotFoundException(
                translate: new Message(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $brand;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list($criteria);
    }

    public function viewBrand(int $id): BrandResponse
    {
        $brand = $this->getEntityById($id);
        return BrandResponse::fromEntity($brand);
    }

    public function get(int $id): BrandResponse
    {
        return $this->viewBrand($id);
    }

    public function create(CreateBrandCommand $command): BrandResponse
    {
        $this->domainService->validateUniqueValue(
            value: $command->name,
            field: 'name',
            resource: Brand::RESOURCE,
            repository: $this->repository,
            excludeId: null
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->withApproved()
            ->build();

        $brand = Brand::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfo,
            syncMdb: $command->syncMdb ?? null
        );

        return BrandResponse::fromEntity(brand: $this->repository->save($brand));
    }

    public function update(int $id, UpdateBrandCommand $command): BrandResponse
    {
        $brand = $this->getEntityById($id);

        $brand->verifyLockVersion($command->lockVersion);

        $newStatus = Status::tryFrom($command->status);

        $hasFieldChanges = $brand->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $brand->validateStateTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        if (isset($command->name)) {
            $this->domainService->validateUniqueValue(
                field: 'name',
                value: $command->name,
                resource: $this->getResource(),
                repository: $this->repository,
                excludeId: $id
            );

            $brand->changeName($command->name);
        }

        if ($newStatus !== null) {
            $brand->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $brand->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $brand->updateDetailInfo(detailInfo: $detailInfo);

        return BrandResponse::fromEntity($this->repository->save($brand));
    }

    public function delete(int $id): BrandResponse
    {
        $brand = $this->getEntityById($id);

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: 'brand.delete',
            resource: $this->getResource(),
            id: $id
        );
        
        $this->domainService->validateCanBeDeleted(
            entity: $brand,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $brand->getDetailInfo(),
                payload: [],
            )
            ->build();

        $brand->updateDetailInfo(detailInfo: $detailInfo);

        return BrandResponse::fromEntity($this->repository->delete($brand));
    }

    public function restore(int $id): BrandResponse
    {
        $brand = $this->repository->restore($id);
        
        if ($brand === null) {
            throw new NotFoundException(
                translate: new Message(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $newStatus = Status::draft();

        $brand->validateStateTransition(
            hasFieldChanges: false,
            newStatus: $newStatus
        );

        if ($newStatus !== null) {
            $brand->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $brand->getDetailInfo(),
                payload: [],
            )
            ->build();

        $brand->updateDetailInfo(detailInfo: $detailInfo);

        return BrandResponse::fromEntity($this->repository->save($brand));
    }
}
