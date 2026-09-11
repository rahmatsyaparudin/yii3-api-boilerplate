<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\ValueObject;

use App\Shared\Core\Enums\AppConstants;
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

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
    public const SYNCED     = null;
    public const NOT_SYNCED = 1;

    public const DIR_NONE             = 0;
    public const DIR_MASTER_TO_ORIGIN = 1;
    public const DIR_ORIGIN_TO_MASTER = 2;
    public const DIR_BIDIRECTIONAL    = 3;

    private function __construct(
        private ?int $originId,
        private ?int $syncFlag,
        private int $direction,
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
        ?int $syncFlag = self::NOT_SYNCED,
        ?int $direction = null,
    ): self {
        self::validateSyncFlag($syncFlag);

        $resolvedDirection = $direction ?? self::resolveDirection($originId, $syncFlag);
        self::validateDirection($resolvedDirection);

        return new self(
            originId: $originId,
            syncFlag: $syncFlag,
            direction: $resolvedDirection,
        );
    }

    public static function fromArray(array $data): self
    {
        return self::create(
            originId: isset($data[self::fieldOriginId()])
                ? (int) $data[self::fieldOriginId()]
                : null,
            syncFlag: \array_key_exists(self::fieldSyncFlag(), $data)
                ? ($data[self::fieldSyncFlag()] === null ? null : (int) $data[self::fieldSyncFlag()])
                : self::NOT_SYNCED,
            direction: $data['direction'] ?? null,
        );
    }

    public static function fromEntity(object $entity): self
    {
        $originId = \method_exists($entity, 'getOriginId')
            ? $entity->getOriginId()
            : null;

        $syncFlag = \method_exists($entity, 'getSyncFlag')
            ? $entity->getSyncFlag()
            : self::NOT_SYNCED;

        $direction = \method_exists($entity, 'getSyncDirection')
            ? $entity->getSyncDirection()
            : null;

        return self::create(
            originId: $originId,
            syncFlag: $syncFlag,
            direction: $direction,
        );
    }

    public static function masterToOrigin(?int $originId = null): self
    {
        return new self(
            originId: $originId,
            syncFlag: self::NOT_SYNCED,
            direction: self::DIR_MASTER_TO_ORIGIN,
        );
    }

    public static function originToMaster(int $originId): self
    {
        return new self(
            originId: $originId,
            syncFlag: self::NOT_SYNCED,
            direction: self::DIR_ORIGIN_TO_MASTER,
        );
    }

    public static function bidirectional(int $originId): self
    {
        return new self(
            originId: $originId,
            syncFlag: self::NOT_SYNCED,
            direction: self::DIR_BIDIRECTIONAL,
        );
    }

    public static function synced(): self
    {
        return new self(
            originId: null,
            syncFlag: self::SYNCED,
            direction: self::DIR_NONE,
        );
    }

    public function getOriginId(): ?int
    {
        return $this->originId;
    }

    public function getSyncFlag(): ?int
    {
        return $this->syncFlag;
    }

    public function getDirection(): int
    {
        return $this->direction;
    }

    public function isPending(): bool
    {
        return $this->syncFlag === self::NOT_SYNCED;
    }

    public function isSynced(): bool
    {
        return $this->syncFlag === self::SYNCED;
    }

    public function isMasterToOrigin(): bool
    {
        return $this->direction === self::DIR_MASTER_TO_ORIGIN
            || $this->direction === self::DIR_BIDIRECTIONAL;
    }

    public function isOriginToMaster(): bool
    {
        return $this->direction === self::DIR_ORIGIN_TO_MASTER
            || $this->direction === self::DIR_BIDIRECTIONAL;
    }

    public function isBidirectional(): bool
    {
        return $this->direction === self::DIR_BIDIRECTIONAL;
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
            syncFlag: self::NOT_SYNCED,
            direction: $this->direction,
        );
    }

    public function markSynced(): self
    {
        return new self(
            originId: $this->originId,
            syncFlag: self::SYNCED,
            direction: $this->direction,
        );
    }

    public function withOriginId(?int $originId): self
    {
        return new self(
            originId: $originId,
            syncFlag: $this->syncFlag,
            direction: self::resolveDirection($originId, $this->syncFlag, $this->direction),
        );
    }

    public function withDirection(int $direction): self
    {
        self::validateDirection($direction);

        return new self(
            originId: $this->originId,
            syncFlag: $this->syncFlag,
            direction: $direction,
        );
    }

    public function toArray(): array
    {
        return [
            self::fieldOriginId() => $this->originId,
            self::fieldSyncFlag() => $this->syncFlag,
            'direction'           => $this->direction,
        ];
    }

    public function toDbArray(): array
    {
        return [
            self::fieldOriginId() => $this->originId,
            self::fieldSyncFlag() => $this->syncFlag,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->originId === $other->originId
            && $this->syncFlag === $other->syncFlag
            && $this->direction === $other->direction;
    }

    private static function resolveDirection(
        ?int $originId,
        ?int $syncFlag,
        ?int $explicitDirection = null,
    ): int {
        if ($explicitDirection !== null) {
            return $explicitDirection;
        }

        if ($syncFlag === self::SYNCED) {
            return self::DIR_NONE;
        }

        if ($originId === null) {
            return self::DIR_MASTER_TO_ORIGIN;
        }

        return self::DIR_ORIGIN_TO_MASTER;
    }

    private static function validateSyncFlag(?int $value): void
    {
        if ($value !== self::SYNCED && $value !== self::NOT_SYNCED) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation',
                    key: 'sync_flag.invalid_value',
                    params: [
                        'allowed_values' => 'null, 1',
                        'value' => $value
                    ]
                )
            );
        }
    }

    private static function validateDirection(int $value): void
    {
        if (!\in_array($value, [self::DIR_NONE, self::DIR_MASTER_TO_ORIGIN, self::DIR_ORIGIN_TO_MASTER, self::DIR_BIDIRECTIONAL], true)) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation',
                    key: 'sync_flag.invalid_direction',
                    params: [
                        'allowed_values' => '0, 1, 2, 3',
                        'value' => $value
                    ]
                )
            );
        }
    }
}
