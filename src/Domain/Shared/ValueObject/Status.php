<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Shared\Constants\StatusEnum;

/**
 * Status value object for domain entities.
 * 
 * This value object encapsulates status behavior and business rules.
 * It provides a rich domain model for status management instead of
 * working with primitive integers.
 */
final readonly class Status
{
    private function __construct(
        private StatusEnum $enum
    ) {
    }

    private const IMMUTABLE_STATUSES = [
        StatusEnum::ACTIVE->value,
        StatusEnum::COMPLETED->value,
        StatusEnum::DELETED->value,
    ];

    /**
     * Allowed status transitions for updates
     */
    private const ALLOWED_UPDATE_STATUS_LIST = [
        StatusEnum::DRAFT->value => [
            StatusEnum::INACTIVE->value,
            StatusEnum::ACTIVE->value,
            StatusEnum::DELETED->value,
            StatusEnum::MAINTENANCE->value,
        ],
        StatusEnum::ACTIVE->value => [
            StatusEnum::COMPLETED->value,
            StatusEnum::APPROVED->value,
            StatusEnum::REJECTED->value,
        ],
        StatusEnum::INACTIVE->value => [
            StatusEnum::ACTIVE->value,
            StatusEnum::DRAFT->value,
            StatusEnum::DELETED->value,
        ],
        StatusEnum::MAINTENANCE->value => [
            StatusEnum::INACTIVE->value,
            StatusEnum::ACTIVE->value,
            StatusEnum::DRAFT->value,
            StatusEnum::DELETED->value,
        ],
        StatusEnum::APPROVED->value => [
            StatusEnum::COMPLETED->value,
            StatusEnum::APPROVED->value,
            StatusEnum::REJECTED->value,
        ],
    ];

    // ====== FACTORY METHODS ======
    
    public static function draft(): self
    {
        return new self(StatusEnum::DRAFT);
    }

    public static function active(): self
    {
        return new self(StatusEnum::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(StatusEnum::INACTIVE);
    }

    public static function deleted(): self
    {
        return new self(StatusEnum::DELETED);
    }

    public static function completed(): self
    {
        return new self(StatusEnum::COMPLETED);
    }

    public static function maintenance(): self
    {
        return new self(StatusEnum::MAINTENANCE);
    }

    public static function approved(): self
    {
        return new self(StatusEnum::APPROVED);
    }

    public static function rejected(): self
    {
        return new self(StatusEnum::REJECTED);
    }

    public static function from(int|string $input): self
    {
        // StatusEnum::from() bisa otomatis handle int atau string
        $enum = StatusEnum::from($input);

        return new self($enum);
    }

    /**
     * Check if status allows updates
     */
    public function allowsTransitionTo(): bool
    {
        return isset(self::ALLOWED_UPDATE_STATUS_LIST[$this->value()]);
    }

    public function canBeUpdated(): bool
    {
        return !in_array($this->value(), self::IMMUTABLE_STATUSES, true);
    }

    /**
     * Check if status allows deletion
     */
    public function canBeDeleted(): bool
    {
        return !$this->isActive();
    }

    /**
     * Check if status is available for use
     */
    public function isAvailableForUse(): bool
    {
        return $this->isActive();
    }

    /**
     * Check if status allows transition to new status
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus->value(), self::ALLOWED_UPDATE_STATUS_LIST[$this->value()] ?? [], true);
    }

    /**
     * Check if status is locked (no further transitions allowed)
     */
    public function isLocked(): bool
    {
        return match ($this->enum) {
            StatusEnum::COMPLETED, 
            StatusEnum::DELETED, 
            StatusEnum::REJECTED => true,
            default => false,
        };
    }

    /**
     * Check if status is valid for entity creation
     */
    public function isValidForCreation(): bool
    {
        return $this->isActive() || $this->isDraft();
    }

    // ====== STATE CHECKERS ======

    public function isActive(): bool
    {
        return $this->enum === StatusEnum::ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->enum === StatusEnum::DRAFT;
    }

    public function isInactive(): bool
    {
        return $this->enum === StatusEnum::INACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->enum === StatusEnum::COMPLETED;
    }

    public function isDeleted(): bool
    {
        return $this->enum === StatusEnum::DELETED;
    }

    // ====== COMPARISON & CONVERSION ======

    public function equals(Status $other): bool
    {
        return $this->enum === $other->enum;
    }

    public function value(): int
    {
        return $this->enum->value;
    }

    public function name(): string
    {
        return $this->enum->name;
    }

    public function label(): string
    {
        return $this->enum->label();
    }

    /**
     * Get status label directly from integer value
     * Static method for convenience without creating Status object
     */
    public static function getLabel(int|string $value): string
    {
        $status = self::from($value);
        return $status->label();
    }

    public function toArray(): array
    {
        return [
            'value' => $this->value(),
            'name' => $this->name(),
            'label' => $this->label(),
        ];
    }

    public function __toString(): string
    {
        return $this->name();
    }
}
