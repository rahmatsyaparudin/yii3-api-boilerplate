<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

// Shared Layer
use App\Shared\Enums\RecordStatus;

/**
 * Status value object for domain entities.
 * 
 * This value object encapsulates status behavior and business rules.
 * It provides a rich domain model for status management instead of
 * working with primitive integers.
 */
final readonly class ResourceStatus
{
    private function __construct(
        private RecordStatus $enum
    ) {
    }

    public static function draft(): self
    {
        return new self(RecordStatus::DRAFT);
    }

    public static function active(): self
    {
        return new self(RecordStatus::ACTIVE);
    }

    public static function inactive(): self
    {
        return new self(RecordStatus::INACTIVE);
    }

    public static function restored(): self
    {
        return new self(RecordStatus::INACTIVE);
    }

    public static function deleted(): self
    {
        return new self(RecordStatus::DELETED);
    }

    public static function completed(): self
    {
        return new self(RecordStatus::COMPLETED);
    }

    public static function maintenance(): self
    {
        return new self(RecordStatus::MAINTENANCE);
    }

    public static function approved(): self
    {
        return new self(RecordStatus::APPROVED);
    }

    public static function rejected(): self
    {
        return new self(RecordStatus::REJECTED);
    }

    public static function from(int|string $value): self
    {
        $enum = RecordStatus::from($value);
        return new self($enum);
    }

    public static function tryFrom(int|string|null $value): ?self
    {
        if ($value === null || (is_string($value) && trim($value) === "")) {
            return null;
        }

        return self::from($value);
    }

    public function canBeUpdated(): bool
    {
        return !in_array($this->value(), RecordStatus::IMMUTABLE_STATUSES, true);
    }

    public function canBeDeleted(): bool
    {
        return !$this->isActive();
    }

    public function isAvailableForUse(): bool
    {
        return $this->isActive();
    }

    public function canTransitionTo(self $newStatus): bool
    {
        return in_array($newStatus->value(), RecordStatus::STATUS_TRANSITION_MAP[$this->value()] ?? [], true);
    }

    public function isLocked(): bool
    {
        return match ($this->enum) {
            RecordStatus::ACTIVE, 
            RecordStatus::COMPLETED, 
            RecordStatus::DELETED, 
            RecordStatus::REJECTED => true,
            default => false,
        };
    }

    public function isValidForCreation(): bool
    {
        return $this->isActive() || $this->isDraft();
    }

    // ====== STATE CHECKERS ======
    public function isActive(): bool
    {
        return $this->enum === RecordStatus::ACTIVE;
    }

    public function isDraft(): bool
    {
        return $this->enum === RecordStatus::DRAFT;
    }

    public function isInactive(): bool
    {
        return $this->enum === RecordStatus::INACTIVE;
    }

    public function isCompleted(): bool
    {
        return $this->enum === RecordStatus::COMPLETED;
    }

    public function isDeleted(): bool
    {
        return $this->enum === RecordStatus::DELETED;
    }

    public function equals(ResourceStatus $other): bool
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
