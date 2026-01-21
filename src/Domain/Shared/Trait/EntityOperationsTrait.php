<?php

declare(strict_types=1);

namespace App\Domain\Shared\Trait;

use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * Entity Operations Trait
 * 
 * Menyediakan common entity operations yang sering digunakan:
 * - Change name
 * - Change status
 * Update detail info
 * Update sync fields
 * Status validation
 * Transition validation
 */
trait EntityOperationsTrait
{
    /**
     * Resource name
     */
    protected string $resource;

    /**
     * Change entity name
     */
    protected function changeName(object $newName, callable $validator = null): void
    {
        if ($this->name->equals($newName)) {
            return; // No change needed
        }

        // Validate if validator provided
        if ($validator) {
            $validator($newName);
        }

        $this->name = $newName;
    }

    /**
     * Change entity status
     */
    protected function changeStatus(object $newStatus, callable $validator = null): void
    {
        if ($this->status === $newStatus) {
            return; // No change needed
        }

        // Validate status transition if validator provided
        if ($validator) {
            $validator($newStatus);
        }

        $this->status = $newStatus;
    }

    /**
     * Update detail info
     */
    protected function updateDetailInfo(object $newDetailInfo): void
    {
        $this->detailInfo = $newDetailInfo;
    }

    /**
     * Update sync field
     */
    protected function updateSyncField(mixed $newValue, string $fieldName): void
    {
        $this->$fieldName = $newValue;
    }

    /**
     * Validate status for creation
     */
    protected function validateStatusForCreation(object $status, callable $validator = null): void
    {
        if ($validator) {
            $validator($status);
        }
        
        if (!$status->isValidForCreation()) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'status.invalid_on_creation', 
                    params: [
                        'resource' => $this->resource,
                        'status' => $status->value
                    ]
                )
            );
        }
    }

    /**
     * Validate status transition
     */
    protected function validateStatusTransition(object $currentStatus, object $newStatus, callable $validator = null): void
    {
        if ($currentStatus === $newStatus) {
            return; // No change needed
        }

        // Validate transition if validator provided
        if ($validator) {
            $validator($newStatus);
        }

        if (!$this->canChangeTo($currentStatus, $newStatus)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'status.invalid_transition', 
                    domain: 'validation',
                    params: [
                        'resource' => $this->resource,
                        'from' => $currentStatus->name(), 
                        'to' => $newStatus->name()
                    ]
                )
            );
        }
    }

    /**
     * Check if entity can change to new status
     */
    protected function canChangeTo(object $currentStatus, object $newStatus): bool
    {
        return $currentStatus->allowsTransitionTo($newStatus);
    }

    /**
     * Check if entity is active
     */
    protected function isActive(): bool
    {
        return $this->status->isActive();
    }

    /**
     * Check if entity is inactive
     */
    protected function isInactive(): bool
    {
        return $this->status->isInactive();
    }
}
