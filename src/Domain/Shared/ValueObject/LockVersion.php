<?php

declare(strict_types=1);

namespace App\Domain\Shared\ValueObject;

use App\Shared\Exception\OptimisticLockException;
use App\Shared\ValueObject\Message;

/**
 * Lock Version Value Object for Optimistic Locking
 * 
 * Provides type-safe handling of version numbers for optimistic locking
 * with automatic increment functionality and validation.
 */
final readonly class LockVersion
{
    public const DEFAULT_VALUE = 1;
    private int $value;

    public function __construct(int $value = self::DEFAULT_VALUE)
    {
        $this->validate($value);
        $this->value = $value;
    }

    /**
     * Create a new lock version (starts at 1)
     */
    public static function create(): self
    {
        return new self(self::DEFAULT_VALUE);
    }

    /**
     * Create from database value
     */
    public static function fromInt(int $value): self
    {
        return new self($value);
    }

    /**
     * Create increment version
     */
    public function increment(): self
    {
        return new self($this->value + 1);
    }

    /**
     * Get current value
     */
    public function getValue(): int
    {
        return $this->value;
    }

    /**
     * Check if this is initial version (1)
     */
    public function isInitial(): bool
    {
        return $this->value === self::DEFAULT_VALUE;
    }

    /**
     * Compare with another lock version
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    /**
     * Check if this version is greater than another
     */
    public function isGreaterThan(self $other): bool
    {
        return $this->value > $other->value;
    }

    /**
     * Get as integer for database storage
     */
    public function toInt(): int
    {
        return $this->value;
    }

    /**
     * Get as string for display
     */
    public function toString(): string
    {
        return (string) $this->value;
    }

    /**
     * Validate lock version value
     */
    private function validate(int $value): void
    {
        if ($value < 0) {
            throw new OptimisticLockException(
                translate: new Message(
                    key: 'lock_version.invalid_negative',
                    params: ['value' => $value]
                )
            );
        }
    }

    /**
     * Create from database nullable value
     */
    public static function fromNullable(?int $value): self
    {
        return new self($value ?? self::DEFAULT_VALUE);
    }
}
