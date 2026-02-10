<?php

declare(strict_types=1);

namespace App\Application\Example;

// Application Layer
use App\Application\Example\Command\CreateExampleCommand;
use App\Application\Example\Command\UpdateExampleCommand;
use App\Application\Example\Dto\ExampleResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\Example\Entity\Example;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Example\Service\ExampleDomainService;
use App\Domain\Shared\Security\AuthorizerInterface;
use App\Domain\Shared\ValueObject\Status;

// Shared Layer
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

    private function getEntityById(int $id, ?int $status = null): Example
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
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
        
        return $data;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list($criteria);
    }

    public function view(int $id): ExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );
        
        return ExampleResponse::fromEntity($data);
    }

    public function get(int $id): ExampleResponse
    {
        return $this->view($id);
    }

    public function create(CreateExampleCommand $command): ExampleResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->build();

        $data = Example::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfo
        );

        return ExampleResponse::fromEntity(example: $this->repository->insert($data));
    }

    public function update(int $id, UpdateExampleCommand $command): ExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $command->lockVersion ?? null
        );

        $newStatus = Status::tryFrom($command->status);

        $hasFieldChanges = $data->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        if (isset($command->name)) {
            $data->changeName($command->name);
        }

        if ($newStatus !== null) {
            $data->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        return ExampleResponse::fromEntity($this->repository->update($data));
    }

    public function delete(int $id, ?int $lockVersion = null): ExampleResponse
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
            authorizer: $this->auth,
            permission: 'example.delete',
            resource: $this->getResource(),
            id: $id
        );
        
        $this->domainService->validateCanBeDeleted(
            entity: $data,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        return ExampleResponse::fromEntity($this->repository->delete($data));
    }

    public function restore(int $id): ExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: Status::deleted()->value()
        );
        
        if ($data === null) {
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

        $newStatus = Status::inactive();

        $data->guardAgainstInvalidTransition(
            hasFieldChanges: false,
            newStatus: $newStatus
        );

        if ($newStatus !== null) {
            $data->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $data->getDetailInfo(),
                payload: [],
            )
            ->build();

        $data->updateDetailInfo(detailInfo: $detailInfo);

        $restoredExample = $this->repository->restore($data->getId());
        
        return ExampleResponse::fromEntity($restoredExample);
    }
}
