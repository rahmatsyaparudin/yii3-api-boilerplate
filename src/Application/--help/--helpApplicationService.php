<?php

declare(strict_types=1);

namespace App\Application\--help;

// Application Layer
use App\Application\--help\Command\Create--helpCommand;
use App\Application\--help\Command\Update--helpCommand;
use App\Application\--help\Dto\--helpResponse;
use App\Application\Shared\Factory\DetailInfoFactory;

// Domain Layer
use App\Domain\--help\Entity\--help;
use App\Domain\--help\Repository\--helpRepositoryInterface;
use App\Domain\--help\Service\--helpDomainService;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Security\AuthorizerInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

/**
 * --help Application Service (Mandor/Alur Kerja)
 * 
 * Orchestrates use cases and coordinates domain & infrastructure
 */
final class --helpApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private --helpRepositoryInterface $repository,
        private --helpDomainService $domainService
    ) {
    }

    public function getResource(): string
    {
        return --help::RESOURCE;
    }

    private function getEntityById(int $id): --help
    {
        $--help = $this->repository->findById($id);
        
        if ($--help === null) {
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
        
        return $--help;
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list($criteria);
    }

    public function view(int $id): --helpResponse
    {
        $--help = $this->getEntityById($id);
        return --helpResponse::fromEntity($--help);
    }

    public function get(int $id): --helpResponse
    {
        return $this->view($id);
    }

    public function create(Create--helpCommand $command): --helpResponse
    {
        $this->domainService->validateUniqueValue(
            value: $command->name,
            field: 'name',
            resource: --help::RESOURCE,
            repository: $this->repository,
            excludeId: null
        );

        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: []
            )
            ->withApproved()
            ->build();

        $--help = --help::create(
            name: $command->name,
            status: Status::from($command->status),
            detailInfo: $detailInfo,
            syncMdb: $command->syncMdb !== null ? ($command->syncMdb ? 1 : 0) : null
        );

        return --helpResponse::fromEntity(--help: $this->repository->insert($--help));
    }

    public function update(int $id, Update--helpCommand $command): --helpResponse
    {
        $--help = $this->getEntityById($id);

        $--help->verifyLockVersion($command->lockVersion);

        $newStatus = Status::tryFrom($command->status);

        $hasFieldChanges = $--help->hasFieldChanges(
            data: (array) $command,
            removeNulls: true
        );

        $--help->validateStateTransition(
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

            $--help->changeName($command->name);
        }

        if ($newStatus !== null) {
            $--help->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $--help->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $--help->updateDetailInfo(detailInfo: $detailInfo);

        return --helpResponse::fromEntity($this->repository->update($--help));
    }

    public function delete(int $id): --helpResponse
    {
        $--help = $this->getEntityById($id);

        $this->domainService->guardPermission(
            authorizer: $this->auth,
            permission: '--help.delete',
            resource: $this->getResource(),
            id: $id
        );
        
        $this->domainService->validateCanBeDeleted(
            entity: $--help,
            resource: $this->getResource(),
        );

        $detailInfo = $this->detailInfoFactory
            ->delete(
                detailInfo: $--help->getDetailInfo(),
                payload: [],
            )
            ->build();

        $--help->updateDetailInfo(detailInfo: $detailInfo);

        return --helpResponse::fromEntity($this->repository->delete($--help));
    }

    public function restore(int $id): --helpResponse
    {
        $--help = $this->repository->restore($id);
        
        if ($--help === null) {
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

        $--help->validateStateTransition(
            hasFieldChanges: false,
            newStatus: $newStatus
        );

        if ($newStatus !== null) {
            $--help->transitionTo($newStatus);
        }

        $detailInfo = $this->detailInfoFactory
            ->restore(
                detailInfo: $--help->getDetailInfo(),
                payload: [],
            )
            ->build();

        $--help->updateDetailInfo(detailInfo: $detailInfo);

        $restored--help = $this->repository->restore($--help->getId());
        
        return --helpResponse::fromEntity($restored--help);
    }
}
