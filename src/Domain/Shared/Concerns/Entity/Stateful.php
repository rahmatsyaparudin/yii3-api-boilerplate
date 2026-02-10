<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

// Domain Layer
use App\Domain\Shared\ValueObject\Status;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\Exception\ConflictException;
use App\Shared\ValueObject\Message;
use App\Shared\Utility\Arrays;

trait Stateful
{
    private const IMMUTABLE_FIELDS = ['id', 'status', 'syncMdb'];

    /**
     * Mengelola state internal
     */
    public function getStatus(): Status
    {
        return $this->status;
    }

    public function markAsDeleted(): self
    {
        $this->status = Status::deleted();
        return $this;
    }

    public function markAsRestored(): self
    {
        $this->status = Status::draft(); // Restore to draft status
        return $this;
    }

    public function transitionTo(Status $nextState): void
    {
        if ($this->status->equals($nextState)) {
            return;
        }

        $this->status = $nextState;
    }

    /**
     * Query untuk mengecek state tertentu
     */
    public function isInState(string $statusValue): bool
    {
        return $this->status->value() === $statusValue;
    }

    public function canBeDeleted(): bool
    {
        return $this->status->canBeDeleted();
    }

    public function canBeUpdated(): bool
    {
        return $this->status->canBeUpdated();
    }

    public function isAvailableForUse(): bool
    {
        return $this->status->isAvailableForUse();
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function hasFieldChanges(array $data, bool $removeNulls = true): bool
    {
        $filteredData = $removeNulls ? Arrays::removeNulls($data) : $data;

        $updatableData = Arrays::getUpdatableKeys(
            data: $filteredData,
            exclude: self::IMMUTABLE_FIELDS
        );

        return !empty($updatableData);
    }

    private function guardStatusTransition(Status $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'status.invalid_transition',
                    domain: 'validation',
                    params: [
                        'resource' => $this->getResource(),
                        'from' => $this->status->label(),
                        'to' => $newStatus->label()
                    ]
                ),
            );
        }
    }

    /**
     * Menjaga agar status awal entitas selalu valid.
     * Digunakan di dalam constructor Entity.
     */
    protected static function guardInitialStatus(Status $status, ?string $resource = null, ?callable $validator = null): void
    {
        if ($validator !== null) {
            $validator($status);
        }
        
        // 2. Cek aturan dasar dari Value Object Status
        if (!$status->isValidForCreation() && !$status->isDeleted()) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'status.invalid_on_creation', 
                    domain: 'validation',
                    params: [
                        'resource' => $resource ?? 'Resource',
                        'status'   => $status->name() // atau $status->value()
                    ]
                ),
            );
        }
    }

    public function guardAgainstInvalidTransition(
        bool $hasFieldChanges,
        ?Status $newStatus = null,
        ?bool $isChangingStatus = null,
    ): bool {
        $currentStatus = $this->status;
        
        $isChangingStatus = ($newStatus !== null);
        $targetStatus = $isChangingStatus ? $newStatus : $currentStatus;

        if ($hasFieldChanges && !$currentStatus->canBeUpdated()) {
            throw new ConflictException(
                translate: new Message(
                    key: 'resource.update_not_allowed_by_status',
                    params: [
                        'resource' => $this->getResource(),
                        'current_status' => $currentStatus->label(),
                    ]
                )
            );
        }

        if (!$hasFieldChanges && $isChangingStatus && $currentStatus->equals($targetStatus)) {
            throw new ConflictException(
                translate: new Message(
                    key: 'resource.status_already_set',
                    params: [
                        'resource' => $this->getResource(),
                        'current_status' => $currentStatus->label(),
                    ]
                )
            );
        }

        if ($isChangingStatus) {
            $this->guardStatusTransition($targetStatus);
        }

        return true;
    }
}