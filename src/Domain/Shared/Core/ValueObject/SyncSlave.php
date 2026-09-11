<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\ValueObject;

use App\Shared\Core\Enums\AppConstants;
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Value Object untuk mengelola sinkronisasi master/slave.
 *
 * Setiap record bisa memiliki:
 * - slave_id   : id slave tempat record berasal atau dituju.
 * - sync_slave : flag 0/1 apakah perlu disync ke slave (master -> slave).
 * - direction  : arah sync eksplisit (master->slave, slave->master, bidirectional).
 */
final readonly class SyncSlave
{
    public const SYNC_DISABLED = 0;
    public const SYNC_ENABLED  = 1;

    public const DIR_NONE            = 0;
    public const DIR_MASTER_TO_SLAVE = 1;
    public const DIR_SLAVE_TO_MASTER = 2;
    public const DIR_BIDIRECTIONAL   = 3;

    private function __construct(
        private ?int $slaveId,
        private int $syncSlave,
        private int $direction,
    ) {
    }

    public static function fieldSlaveId(): string
    {
        return AppConstants::SLAVE_ID;
    }

    public static function fieldSyncSlave(): string
    {
        return AppConstants::SYNC_SLAVE;
    }

    public static function create(
        ?int $slaveId,
        int $syncSlave,
        ?int $direction = null,
    ): self {
        self::validateSyncSlave($syncSlave);

        $resolvedDirection = $direction ?? self::resolveDirection($slaveId, $syncSlave);
        self::validateDirection($resolvedDirection);

        return new self(
            slaveId: $slaveId,
            syncSlave: $syncSlave,
            direction: $resolvedDirection,
        );
    }

    public static function fromArray(array $data): self
    {
        return self::create(
            slaveId: isset($data[self::fieldSlaveId()]) && $data[self::fieldSlaveId()] !== null
                ? (int) $data[self::fieldSlaveId()]
                : null,
            syncSlave: isset($data[self::fieldSyncSlave()])
                ? (int) $data[self::fieldSyncSlave()]
                : self::SYNC_DISABLED,
            direction: $data['direction'] ?? null,
        );
    }

    public static function fromEntity(object $entity): self
    {
        $slaveId = \method_exists($entity, 'getSlaveId')
            ? $entity->getSlaveId()
            : null;

        $syncSlave = \method_exists($entity, 'getSyncSlave')
            ? $entity->getSyncSlave()
            : self::SYNC_DISABLED;

        $direction = \method_exists($entity, 'getSyncDirection')
            ? $entity->getSyncDirection()
            : null;

        return self::create(
            slaveId: $slaveId,
            syncSlave: $syncSlave,
            direction: $direction,
        );
    }

    public static function masterToSlave(?int $slaveId = null): self
    {
        return new self(
            slaveId: $slaveId,
            syncSlave: self::SYNC_ENABLED,
            direction: self::DIR_MASTER_TO_SLAVE,
        );
    }

    public static function slaveToMaster(int $slaveId): self
    {
        return new self(
            slaveId: $slaveId,
            syncSlave: self::SYNC_ENABLED,
            direction: self::DIR_SLAVE_TO_MASTER,
        );
    }

    public static function bidirectional(int $slaveId): self
    {
        return new self(
            slaveId: $slaveId,
            syncSlave: self::SYNC_ENABLED,
            direction: self::DIR_BIDIRECTIONAL,
        );
    }

    public static function disabled(): self
    {
        return new self(
            slaveId: null,
            syncSlave: self::SYNC_DISABLED,
            direction: self::DIR_NONE,
        );
    }

    public function getSlaveId(): ?int
    {
        return $this->slaveId;
    }

    public function getSyncSlave(): int
    {
        return $this->syncSlave;
    }

    public function getDirection(): int
    {
        return $this->direction;
    }

    public function isEnabled(): bool
    {
        return $this->syncSlave === self::SYNC_ENABLED;
    }

    public function isDisabled(): bool
    {
        return $this->syncSlave === self::SYNC_DISABLED;
    }

    public function isMasterToSlave(): bool
    {
        return $this->direction === self::DIR_MASTER_TO_SLAVE
            || $this->direction === self::DIR_BIDIRECTIONAL;
    }

    public function isSlaveToMaster(): bool
    {
        return $this->direction === self::DIR_SLAVE_TO_MASTER
            || $this->direction === self::DIR_BIDIRECTIONAL;
    }

    public function isBidirectional(): bool
    {
        return $this->direction === self::DIR_BIDIRECTIONAL;
    }

    public function needsSyncToSlave(): bool
    {
        return $this->isEnabled() && $this->isMasterToSlave();
    }

    public function needsSyncToMaster(): bool
    {
        return $this->isEnabled() && $this->isSlaveToMaster();
    }

    public function markForSync(): self
    {
        return new self(
            slaveId: $this->slaveId,
            syncSlave: self::SYNC_ENABLED,
            direction: $this->direction,
        );
    }

    public function markSynced(): self
    {
        return new self(
            slaveId: $this->slaveId,
            syncSlave: self::SYNC_DISABLED,
            direction: $this->direction,
        );
    }

    public function withSlaveId(int $slaveId): self
    {
        return new self(
            slaveId: $slaveId,
            syncSlave: $this->syncSlave,
            direction: self::resolveDirection($slaveId, $this->syncSlave, $this->direction),
        );
    }

    public function withDirection(int $direction): self
    {
        self::validateDirection($direction);

        return new self(
            slaveId: $this->slaveId,
            syncSlave: $this->syncSlave,
            direction: $direction,
        );
    }

    public function toArray(): array
    {
        return [
            self::fieldSlaveId()   => $this->slaveId,
            self::fieldSyncSlave() => $this->syncSlave,
            'direction'            => $this->direction,
        ];
    }

    public function toDbArray(): array
    {
        return [
            self::fieldSlaveId()   => $this->slaveId,
            self::fieldSyncSlave() => $this->syncSlave,
        ];
    }

    public function equals(self $other): bool
    {
        return $this->slaveId === $other->slaveId
            && $this->syncSlave === $other->syncSlave
            && $this->direction === $other->direction;
    }

    private static function resolveDirection(
        ?int $slaveId,
        int $syncSlave,
        ?int $explicitDirection = null,
    ): int {
        if ($explicitDirection !== null) {
            return $explicitDirection;
        }

        if ($syncSlave === self::SYNC_DISABLED) {
            return self::DIR_NONE;
        }

        if ($slaveId === null) {
            return self::DIR_MASTER_TO_SLAVE;
        }

        return self::DIR_SLAVE_TO_MASTER;
    }

    private static function validateSyncSlave(int $value): void
    {
        if ($value !== self::SYNC_DISABLED && $value !== self::SYNC_ENABLED) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'sync_slave.invalid_value', 
                    params: [
                        'allowed_values' => '0, 1', 
                        'value' => $value
                    ]
                )
            );
        }
    }

    private static function validateDirection(int $value): void
    {
        if (!\in_array($value, [self::DIR_NONE, self::DIR_MASTER_TO_SLAVE, self::DIR_SLAVE_TO_MASTER, self::DIR_BIDIRECTIONAL], true)) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'sync_slave.invalid_direction', 
                    params: [
                        'allowed_values' => '0, 1, 2, 3', 
                        'value' => $value
                    ]
                )
            );
        }
    }
}
