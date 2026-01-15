<?php

declare(strict_types=1);

namespace App\Domain\Shared\Trait;

use App\Domain\Shared\ValueObject\Status;

/**
 * Status Delegation Trait
 * 
 * This trait provides delegation methods for status-based business rules.
 * It provides a convenient way for entities to delegate
 * status-related operations to their Status value object
 */
trait StatusDelegationTrait
{
    /**
     * Check if entity can be deleted
     * Delegates to Status value object business rules
     */
    public function canBeDeleted(): bool
    {
        return $this->status->canBeDeleted();
    }

    /**
     * Check if entity can be updated
     * Delegates to Status value object business rules
     */
    public function canBeUpdated(): bool
    {
        return $this->status->canBeUpdated();
    }

    /**
     * Check if entity is available for use
     * Delegates to Status value object business rules
     */
    public function isAvailableForUse(): bool
    {
        return $this->status->isAvailableForUse();
    }
}
