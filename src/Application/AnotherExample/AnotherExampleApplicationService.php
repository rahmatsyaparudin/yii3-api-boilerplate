<?php

declare(strict_types=1);

namespace App\Application\AnotherExample;

// Application Layer
use App\Application\AnotherExample\AnotherExampleDetailInfoFactory;
use App\Application\AnotherExample\Command\CreateAnotherExampleCommand;
use App\Application\AnotherExample\Command\UpdateAnotherExampleCommand;
use App\Application\AnotherExample\Dto\AnotherExampleResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\AnotherExample\Entity\AnotherExample;
use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;
use App\Domain\AnotherExample\Service\AnotherExampleDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * AnotherExample Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class AnotherExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private AnotherExampleDetailInfoFactory $anotherExampleDetailInfoFactory,
        private AnotherExampleRepositoryInterface $repository,
        private AnotherExampleDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return AnotherExample::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): AnotherExample
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }
        
        return $data;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): AnotherExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return AnotherExampleResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateAnotherExampleCommand $command): AnotherExampleResponse
    {
        $detailInfoPayload = $this->anotherExampleDetailInfoFactory
            ->buildFromPrimitives(
                exampleId: $command->exampleId
            )
            ->toArray();

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $detailInfoPayload
            )
            ->build();

        $data = AnotherExample::create(
            name: $command->name,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo,
            exampleId: $command->exampleId,
        );

        return AnotherExampleResponse::fromEntity(
            entity: $this->repository->insert(
                entity: $data
            )
        );
    }

    public function update(int $id, UpdateAnotherExampleCommand $command): AnotherExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = ResourceStatus::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        $data->updateName(
            newName: $command->name
        );

        $data->updateExampleId(
            exampleId: $command->exampleId
        );

        $data->applyStatus(
            newStatus: $newStatus
        );

        $exampleSnapshot = $this->anotherExampleDetailInfoFactory
            ->buildFromPrimitives($command->exampleId);

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: array_merge(
                    $command->detailInfo ?? [],
                    $exampleSnapshot->toArray()
                ),
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AnotherExampleResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): AnotherExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data, 
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'anotherexample.delete',
            resource: $this->getResource(),
        );
        
        $this->domainService->ensureDeletable(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return AnotherExampleResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): AnotherExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );
        
        if ($data === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found', 
                    params: [
                        'resource' => $this->getResource(),
                        'field' => 'id',
                        'value' => $id
                    ]
                )
            );
        }

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: ResourceStatus::restored()
        );

        $data->markAsRestored();

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        $restoredData = $this->repository->restore(
            id: $data->getId()
        );
        
        return AnotherExampleResponse::fromEntity(
            entity: $restoredData
        );
    }
}
