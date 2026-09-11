<?php

declare(strict_types=1);

namespace App\Application\Shared\Core\Factory;

// Domain Layer
use App\Domain\Shared\Core\Contract\DateTimeProviderInterface;
use App\Domain\Shared\Core\Enum\SyncDirection;
use App\Domain\Shared\Core\Enum\SyncStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;

// Infrastructure Layer
use App\Infrastructure\Core\Security\CurrentUser;

final class SyncFlagFactory
{
    public function __construct(
        private DateTimeProviderInterface $dateTime,
        private CurrentUser $currentUser,
    ) {}

    /**
     * Membuat SyncFlag dari nilai mentah.
     */
    public function create(
        ?int $originId = null,
        ?int $syncFlag = 1,
        ?int $direction = null,
    ): SyncFlag {
        return SyncFlag::create(
            originId: $originId,
            status: SyncStatus::fromDbValue($syncFlag),
            direction: $direction === null
                ? null
                : SyncDirection::fromValue($direction),
        );
    }

    /**
     * Membuat SyncFlag dari request body / array input.
     */
    public function fromRequest(array $data): SyncFlag
    {
        return SyncFlag::fromArray($data);
    }

    /**
     * Membuat SyncFlag dari database row.
     */
    public function fromRecord(array $row): SyncFlag
    {
        return SyncFlag::fromArray($row);
    }

    /**
     * Membuat SyncFlag dari entity yang memiliki getter originId & syncFlag.
     */
    public function fromEntity(object $entity): SyncFlag
    {
        return SyncFlag::fromEntity($entity);
    }

    /**
     * Master meneruskan record ke satu atau semua origin.
     */
    public function masterToOrigin(?int $originId = null): SyncFlag
    {
        return SyncFlag::masterToOrigin($originId);
    }

    /**
     * Origin mengirim record ke master.
     */
    public function originToMaster(int $originId): SyncFlag
    {
        return SyncFlag::originToMaster($originId);
    }

    /**
     * Sinkronisasi dua arah antara master dan origin tertentu.
     */
    public function bidirectional(int $originId): SyncFlag
    {
        return SyncFlag::bidirectional($originId);
    }

    /**
     * Tandai record sudah synced (tidak perlu disync).
     */
    public function synced(): SyncFlag
    {
        return SyncFlag::synced();
    }

    /**
     * Tandai record agar perlu disync.
     */
    public function markForSync(SyncFlag $syncFlag): SyncFlag
    {
        return $syncFlag->markForSync();
    }

    /**
     * Tandai record sudah selesai disync.
     */
    public function markSynced(SyncFlag $syncFlag): SyncFlag
    {
        return $syncFlag->markSynced();
    }

    /**
     * Resolve arah sync berdasarkan origin_id & sync_flag.
     */
    public function resolveDirection(
        ?int $originId,
        ?int $syncFlag,
        ?int $explicitDirection = null,
    ): SyncDirection {
        if (SyncStatus::fromDbValue($syncFlag) === SyncStatus::SYNCED) {
            return SyncDirection::NONE;
        }

        if ($explicitDirection !== null) {
            return SyncDirection::fromValue($explicitDirection);
        }

        if ($originId === null) {
            return SyncDirection::MASTER_TO_ORIGIN;
        }

        return SyncDirection::ORIGIN_TO_MASTER;
    }

    /**
     * Apakah record perlu di-push dari master ke origin?
     */
    public function shouldPushToOrigin(SyncFlag $syncFlag): bool
    {
        return $syncFlag->needsSyncToOrigin();
    }

    /**
     * Apakah record perlu di-push dari origin ke master?
     */
    public function shouldPushToMaster(SyncFlag $syncFlag): bool
    {
        return $syncFlag->needsSyncToMaster();
    }

    /**
     * Build payload untuk sync queue/job.
     */
    public function buildPayload(
        SyncFlag $syncFlag,
        string $table,
        int $recordId,
        string $operation,
        array $data = [],
    ): array {
        $actor = $this->currentUser->getActor();

        return [
            'table' => $table,
            'record_id' => $recordId,
            'origin_id' => $syncFlag->getOriginId(),
            'direction' => $syncFlag->getDirection()->value,
            'operation' => $operation,
            'payload' => $data,
            'synced_at' => null,
            'created_at' => $this->dateTime->database(),
            'created_by' => $actor->getUsername(),
        ];
    }

    /**
     * Build payload khusus master -> origin.
     */
    public function buildMasterToOriginPayload(
        ?int $originId,
        string $table,
        int $recordId,
        string $operation,
        array $data = [],
    ): array {
        return $this->buildPayload(
            syncFlag: SyncFlag::masterToOrigin($originId),
            table: $table,
            recordId: $recordId,
            operation: $operation,
            data: $data,
        );
    }

    /**
     * Build payload khusus origin -> master.
     */
    public function buildOriginToMasterPayload(
        int $originId,
        string $table,
        int $recordId,
        string $operation,
        array $data = [],
    ): array {
        return $this->buildPayload(
            syncFlag: SyncFlag::originToMaster($originId),
            table: $table,
            recordId: $recordId,
            operation: $operation,
            data: $data,
        );
    }

    /**
     * Gabungkan SyncFlag ke dalam detail_info.
     */
    public function mergeIntoDetailInfo(SyncFlag $syncFlag, array $detailInfo = []): array
    {
        $detailInfo['sync_flag'] = $syncFlag->toArray();

        return $detailInfo;
    }

    /**
     * Build log entry untuk perubahan status sync.
     */
    public function buildSyncLog(
        SyncFlag $syncFlag,
        string $table,
        int $recordId,
        string $operation,
    ): array {
        $actor = $this->currentUser->getActor();

        return [
            'table' => $table,
            'record_id' => $recordId,
            'origin_id' => $syncFlag->getOriginId(),
            'sync_flag' => $syncFlag->getSyncFlag(),
            'direction' => $syncFlag->getDirection()->value,
            'operation' => $operation,
            'timestamp' => $this->dateTime->database(),
            'by' => $actor->getUsername(),
        ];
    }
}
