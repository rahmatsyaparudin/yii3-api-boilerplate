<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Concerns\Entity;

// Domain Layer
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
// Shared Layer
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\Exception\ConflictException;
use App\Shared\Core\Utility\Arrays;
use App\Shared\Core\ValueObject\Message;

trait Stateful
{
    private const IMMUTABLE_FIELDS = ['id', 'status', 'syncMdb'];

    /**
     * Mengelola state internal.
     */
    public function getStatus(): ResourceStatus
    {
        return $this->status;
    }

    public function markAsDeleted(): self
    {
        $this->status = ResourceStatus::deleted();

        return $this;
    }

    public function markAsRestored(): self
    {
        $this->status = ResourceStatus::inactive();

        return $this;
    }

    public function applyStatus(?ResourceStatus $newStatus): void
    {
        if ($newStatus === null) {
            return;
        }

        if ($this->status->equals($newStatus)) {
            return;
        }

        $this->status = $newStatus;
    }

    /**
     * Query untuk mengecek state tertentu.
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

    private function guardStatusTransition(ResourceStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'status.invalid_transition', 
                    params: [
                        'resource' => $this->getResource(), 
                        'from' => $this->status->label(), 
                        'to' => $newStatus->label()
                    ]
                )
            );
        }
    }

    /**
     * Menjaga agar status awal entitas selalu valid.
     * Digunakan di dalam constructor Entity.
     */
    protected static function guardInitialStatus(ResourceStatus $status, ?string $resource = null, ?callable $validator = null): void
    {
        if ($validator !== null) {
            $validator($status);
        }

        // 2. Cek aturan dasar dari Value Object Status
        if (!$status->isValidForCreation() && !$status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'status.invalid_on_creation', 
                    params: [
                        'resource' => $resource ?? 'Resource', 
                        'status' => $status->name() /* atau $status->value() */
                    ]
                )
            );
        }
    }

    public function guardAgainstInvalidTransition(
        bool $hasFieldChanges,
        ?ResourceStatus $newStatus = null,
        ?bool $isChangingStatus = null,
    ): bool {
        $currentStatus = $this->status;

        $isChangingStatus = ($newStatus !== null);
        $targetStatus     = $isChangingStatus ? $newStatus : $currentStatus;

        if ($hasFieldChanges && !$currentStatus->canBeUpdated()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'resource.update_not_allowed_by_status', 
                    params: [
                        'resource' => $this->getResource(), 
                        'current_status' => $currentStatus->label()
                    ]
                )
            );
        }

        if (!$hasFieldChanges && $isChangingStatus && $currentStatus->equals($targetStatus)) {
            throw new ConflictException(translate: Message::create(key: 'resource.status_already_set', params: ['resource' => $this->getResource(), 'current_status' => $currentStatus->label()]));
        }

        if ($isChangingStatus) {
            $this->guardStatusTransition($targetStatus);
        }

        return true;
    }
}
