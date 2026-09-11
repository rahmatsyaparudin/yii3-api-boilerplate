<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\ValueObject;

use App\Domain\Shared\Core\Enum\SyncDirection;
use App\Domain\Shared\Core\Enum\SyncStatus;
use App\Shared\Core\Enums\AppConstants;

/**
 * Value Object untuk mengelola sinkronisasi master/origin.
 *
 * Setiap record bisa memiliki:
 * - origin_id  : id origin tempat record berasal atau dituju (integer, default null).
 * - sync_flag  : flag null/1 status sync (null: synced, 1: not synced, default 1).
 * - direction  : arah sync eksplisit (master->origin, origin->master, bidirectional).
 */
final readonly class SyncFlag
{
    private function __construct(
        private ?int $originId,
        private SyncStatus $status,
        private SyncDirection $direction,
    ) {
    }

    public static function fieldOriginId(): string
    {
        return AppConstants::ORIGIN_ID;
    }

    public static function fieldSyncFlag(): string
    {
        return AppConstants::SYNC_FLAG;
    }

    public static function create(
        ?int $originId = null,
        SyncStatus $status = SyncStatus::NOT_SYNCED,
        ?SyncDirection $direction = null,
    ): self {
        return new self(
            originId: $originId,
            status: $status,
            direction: $direction ?? self::resolveDirection($originId, $status),
        );
    }

    public static function fromArray(array $data): self
    {
        return self::create(
            originId: isset($data[self::fieldOriginId()])
                ? (int) $data[self::fieldOriginId()]
                : null,
            status: \array_key_exists(self::fieldSyncFlag(), $data)
                ? SyncStatus::fromDbValue(
                    $data[self::fieldSyncFlag()] === null
                        ? null
                        : (int) $data[self::fieldSyncFlag()]
                )
                : SyncStatus::NOT_SYNCED,
            direction: isset($data['direction'])
                ? SyncDirection::fromValue((int) $data['direction'])
                : null,
        );
    }

    public static function fromEntity(object $entity): self
    {
        $originId = \method_exists($entity, 'getOriginId')
            ? $entity->getOriginId()
            : null;

        $rawDirection = \method_exists($entity, 'getSyncDirection')
            ? $entity->getSyncDirection()
            : null;

        return self::create(
            originId: $originId,
            status: self::statusFromEntity($entity),
            direction: match (true) {
                $rawDirection instanceof SyncDirection => $rawDirection,
                $rawDirection === null                 => null,
                default                                => SyncDirection::fromValue((int) $rawDirection),
            },
        );
    }

    private static function statusFromEntity(object $entity): SyncStatus
    {
        if (\method_exists($entity, 'getSyncFlagValue')) {
            return SyncStatus::fromDbValue($entity->getSyncFlagValue());
        }

        if (\method_exists($entity, 'getSyncFlag')) {
            $flag = $entity->getSyncFlag();

            return match (true) {
                $flag instanceof self => $flag->getSyncStatus(),
                $flag === null        => SyncStatus::NOT_SYNCED,
                default               => SyncStatus::fromDbValue($flag),
            };
        }

        return SyncStatus::NOT_SYNCED;
    }

    public static function masterToOrigin(?int $originId = null): self
    {
        return new self(
            originId: $originId,
            status: SyncStatus::NOT_SYNCED,
            direction: SyncDirection::MASTER_TO_ORIGIN,
        );
    }

    public static function originToMaster(int $originId): self
    {
        return new self(
            originId: $originId,
            status: SyncStatus::NOT_SYNCED,
            direction: SyncDirection::ORIGIN_TO_MASTER,
        );
    }

    public static function bidirectional(int $originId): self
    {
        return new self(
            originId: $originId,
            status: SyncStatus::NOT_SYNCED,
            direction: SyncDirection::BIDIRECTIONAL,
        );
    }

    public static function synced(): self
    {
        return new self(
            originId: null,
            status: SyncStatus::SYNCED,
            direction: SyncDirection::NONE,
        );
    }

    public function getOriginId(): ?int
    {
        return $this->originId;
    }

    public function getSyncStatus(): SyncStatus
    {
        return $this->status;
    }

    /**
     * Nilai mentah kolom sync_flag (null: synced, 1: not synced).
     */
    public function getSyncFlag(): ?int
    {
        return $this->status->dbValue();
    }

    public function getDirection(): SyncDirection
    {
        return $this->direction;
    }

    public function isPending(): bool
    {
        return $this->status->isPending();
    }

    public function isSynced(): bool
    {
        return $this->status->isSynced();
    }

    public function isMasterToOrigin(): bool
    {
        return $this->direction === SyncDirection::MASTER_TO_ORIGIN
            || $this->direction === SyncDirection::BIDIRECTIONAL;
    }

    public function isOriginToMaster(): bool
    {
        return $this->direction === SyncDirection::ORIGIN_TO_MASTER
            || $this->direction === SyncDirection::BIDIRECTIONAL;
    }

    public function isBidirectional(): bool
    {
        return $this->direction === SyncDirection::BIDIRECTIONAL;
    }

    public function needsSyncToOrigin(): bool
    {
        return $this->isPending() && $this->isMasterToOrigin();
    }

    public function needsSyncToMaster(): bool
    {
        return $this->isPending() && $this->isOriginToMaster();
    }

    public function markForSync(): self
    {
        return new self(
            originId: $this->originId,
            status: SyncStatus::NOT_SYNCED,
            direction: $this->direction,
        );
    }

    public function markSynced(): self
    {
        return new self(
            originId: $this->originId,
            status: SyncStatus::SYNCED,
            direction: $this->direction,
        );
    }

    public function withOriginId(?int $originId): self
    {
        return new self(
            originId: $originId,
            status: $this->status,
            direction: self::resolveDirection($originId, $this->status, $this->direction),
        );
    }

    public function withDirection(SyncDirection $direction): self
    {
        return new self(
            originId: $this->originId,
            status: $this->status,
            direction: $direction,
        );
    }

    public function toArray(): array
    {
        return [
            self::fieldOriginId() => $this->originId,
            self::fieldSyncFlag() => $this->status->dbValue(),
            'direction'           => $this->direction->value,
        ];
    }

    public function toDbArray(): array
    {
        return [
            self::fieldOriginId() => $this->originId,
            self::fieldSyncFlag() => $this->status->dbValue(),
        ];
    }

    public function equals(self $other): bool
    {
        return $this->originId === $other->originId
            && $this->status === $other->status
            && $this->direction === $other->direction;
    }

    private static function resolveDirection(
        ?int $originId,
        SyncStatus $status,
        ?SyncDirection $explicitDirection = null,
    ): SyncDirection {
        if ($explicitDirection !== null) {
            return $explicitDirection;
        }

        if ($status === SyncStatus::SYNCED) {
            return SyncDirection::NONE;
        }

        if ($originId === null) {
            return SyncDirection::MASTER_TO_ORIGIN;
        }

        return SyncDirection::ORIGIN_TO_MASTER;
    }
}
