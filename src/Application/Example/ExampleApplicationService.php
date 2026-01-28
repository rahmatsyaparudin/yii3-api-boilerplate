<?php

declare(strict_types=1);

namespace App\Application\Example;

use App\Application\Example\Command\CreateExampleCommand;
use App\Application\Example\Command\UpdateExampleCommand;
use App\Application\Example\Dto\ExampleResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

use App\Domain\Example\Entity\Example;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Example\Service\ExampleDomainService;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Security\AuthorizerInterface;

use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * Example Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private ExampleRepositoryInterface $repository,
        private ExampleDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return Example::RESOURCE;
    }

    private function getEntityById(int $id): Example
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

    public function viewExample(int $id): ExampleResponse
    {
        $brand = $this->getEntityById($id);
        return ExampleResponse::fromEntity($brand);
    }

    public function get(int $id): ExampleResponse
    {
        return $this->viewExample($id);
    }

    public function create(CreateExampleCommand $command): ExampleResponse
    {
        $this->domainService->validateUniqueValue(
            value: $command->name,
            field: 'name',
            resource: Example::RESOURCE,
            repository: $this->repository,
            excludeId: null
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->withApproved()
            ->build();

        $brand = Example::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfo,
            syncMdb: $command->syncMdb ?? null
        );

        return ExampleResponse::fromEntity(brand: $this->repository->save($brand));
    }

    public function update(int $id, UpdateExampleCommand $command): ExampleResponse
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

        return ExampleResponse::fromEntity($this->repository->save($brand));
    }

    public function delete(int $id): ExampleResponse
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

        return ExampleResponse::fromEntity($this->repository->delete($brand));
    }

    public function restore(int $id): ExampleResponse
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

        return ExampleResponse::fromEntity($this->repository->save($brand));
    }
}
