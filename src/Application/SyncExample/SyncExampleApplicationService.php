<?php

declare(strict_types=1);

namespace App\Application\SyncExample;

// Application Layer
use App\Application\Shared\Core\Factory\DetailInfoFactory;
use App\Application\Shared\Core\Factory\SyncFlagFactory;
use App\Application\Shared\Core\Factory\SyncIdentityFactory;
use App\Application\Shared\Core\Factory\SyncOutboxFactory;
use App\Application\SyncExample\Command\CreateSyncExampleCommand;
use App\Application\SyncExample\Command\UpdateSyncExampleCommand;
use App\Application\SyncExample\Dto\SyncExampleResponse;
// Domain Layer
use App\Domain\Shared\Core\Security\AuthorizerInterface;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\SyncExample\Entity\SyncExample;
use App\Domain\SyncExample\Repository\SyncExampleRepositoryInterface;
use App\Domain\SyncExample\Service\SyncExampleDomainService;
// Shared Layer
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;
use App\Shared\Core\Exception\NotFoundException;
use App\Shared\Core\ValueObject\Message;

/**
 * SyncExample Application Service (Mandor/Alur Kerja).
 *
 * Reference implementation of the full sync scheme: record identity is
 * stamped by SyncIdentityFactory (uuid + source_store_id + doc_role),
 * routing stays on SyncFlag, and queue rows are written to sync_queue in
 * the same transaction as the record (outbox pattern).
 */
final class SyncExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private DetailInfoFactory $detailInfoFactory,
        private SyncFlagFactory $syncFlagFactory,
        private SyncIdentityFactory $syncIdentityFactory,
        private SyncOutboxFactory $syncOutboxFactory,
        private SyncExampleRepositoryInterface $repository,
        private SyncExampleDomainService $domainService,
    ) {
    }

    public function getResource(): string
    {
        return SyncExample::RESOURCE;
    }

    private function getEntityById(int $id, ?int $status = null): SyncExample
    {
        $data = $this->repository->findById(
            id: $id,
            status: $status
        );

        if ($data === null) {
            throw new NotFoundException(translate: Message::create(key: 'resource.not_found', params: ['resource' => $this->getResource(), 'field' => 'id', 'value' => $id]));
        }

        return $data;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(
            criteria: $criteria
        );
    }

    public function view(int $id): SyncExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        return SyncExampleResponse::fromEntity(
            entity: $data
        );
    }

    public function create(CreateSyncExampleCommand $command): SyncExampleResponse
    {
        $detailInfo = $this->detailInfoFactory
            ->create(
                detailInfo: $command->detailInfo ?? []
            )
            ->build();

        $syncFlag = $this->syncFlagFactory->create(
            originId: $command->originId,
            syncFlag: $command->syncFlag ?? 1,
        );

        $syncIdentity = $this->syncIdentityFactory->create();

        $data = SyncExample::create(
            name: $command->name,
            status: ResourceStatus::from($command->status),
            detailInfo: $detailInfo,
            syncFlag: $syncFlag,
            syncIdentity: $syncIdentity,
        );

        $queueRows = $this->syncOutboxFactory->buildRows(
            table: 'sync_example',
            identity: $syncIdentity,
            syncFlag: $syncFlag,
            operation: 'INSERT',
            payload: $data->toArray(),
        );

        return SyncExampleResponse::fromEntity(
            entity: $this->repository->insertWithOutbox(
                syncExample: $data,
                queueRows: $queueRows,
            )
        );
    }

    public function update(int $id, UpdateSyncExampleCommand $command): SyncExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->assertEditable($data);

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

        $data->applyStatus(
            newStatus: $newStatus
        );

        $detailInfo = $this->detailInfoFactory
            ->update(
                detailInfo: $data->getDetailInfo(),
                payload: $command->detailInfo ?? [],
            )
            ->build();

        $data->updateDetailInfo(
            detailInfo: $detailInfo
        );

        return SyncExampleResponse::fromEntity(
            entity: $this->repository->update(
                entity: $data
            )
        );
    }

    public function delete(int $id, ?int $lockVersion = null): SyncExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: null
        );

        $this->assertEditable($data);

        $this->repository->verifyLockVersion(
            entity: $data,
            version: $lockVersion,
        );

        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'sync-example.delete',
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

        return SyncExampleResponse::fromEntity(
            entity: $this->repository->delete(
                entity: $data
            )
        );
    }

    public function restore(int $id): SyncExampleResponse
    {
        $data = $this->getEntityById(
            id: $id,
            status: ResourceStatus::deleted()->value()
        );

        if ($data === null) {
            throw new NotFoundException(translate: Message::create(key: 'resource.not_found', params: ['resource' => $this->getResource(), 'field' => 'id', 'value' => $id]));
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

        return SyncExampleResponse::fromEntity(
            entity: $restoredData
        );
    }

    /**
     * doc_role guard: hanya record LOCAL yang boleh diubah user.
     * Record COPY/INTERSTORE hanya boleh ditulis oleh sync applier.
     */
    private function assertEditable(SyncExample $data): void
    {
        $identity = $data->getSyncIdentity();

        if ($identity !== null) {
            $this->syncIdentityFactory->assertEditableByUser($identity);
        }
    }
}
