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
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Security\AuthorizerInterface;

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

    private function getEntityById(int $id): Example
    {
        $example = $this->repository->findById($id);

        if ($example === null) {
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
        
        return $example;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list($criteria);
    }

    public function view(int $id): ExampleResponse
    {
        $example = $this->getEntityById($id);
        return ExampleResponse::fromEntity($example);
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
            ->withApproved()
            ->build();

        $example = Example::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfo
        );

        return ExampleResponse::fromEntity(example: $this->repository->insert($example));
    }

    public function update(int $id, UpdateExampleCommand $command): ExampleResponse
    {
        $example = $this->getEntityById($id);

        // Only verify lock version if optimistic lock is enabled and lockVersion is provided
        if ($command->lockVersion !== null) {
            $example->verifyLockVersion($command->lockVersion);
        }

        $newStatus = Status::tryFrom($command->status);

        $hasFieldChanges = $example->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $example->validateStateTransition(
            hasFieldChanges: $hasFieldChanges,
            newStatus: $newStatus
        );

        if (isset($command->name)) {
            $example->changeName($command->name);
        }

        if ($newStatus !== null) {
            $example->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $example->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $example->updateDetailInfo(detailInfo: $detailInfo);

        return ExampleResponse::fromEntity($this->repository->update($example));
    }

    public function delete(int $id, ?int $lockVersion = null): ExampleResponse
    {
        $example = $this->getEntityById($id);

        // Only verify lock version if optimistic lock is enabled and lockVersion is provided
        if ($lockVersion !== null) {
            $example->verifyLockVersion($lockVersion);
        }

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: 'example.delete',
            resource: $this->getResource(),
            id: $id
        );
        
        $this->domainService->validateCanBeDeleted(
            entity: $example,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $example->getDetailInfo(),
                payload: [],
            )
            ->build();

        $example->updateDetailInfo(detailInfo: $detailInfo);

        return ExampleResponse::fromEntity($this->repository->delete($example));
    }

    public function restore(int $id): ExampleResponse
    {
        $example = $this->repository->restore($id);
        
        if ($example === null) {
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

        $example->validateStateTransition(
            hasFieldChanges: false,
            newStatus: $newStatus
        );

        if ($newStatus !== null) {
            $example->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $example->getDetailInfo(),
                payload: [],
            )
            ->build();

        $example->updateDetailInfo(detailInfo: $detailInfo);

        $restoredExample = $this->repository->restore($example->getId());
        
        return ExampleResponse::fromEntity($restoredExample);
    }
}
