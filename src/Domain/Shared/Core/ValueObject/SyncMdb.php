<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\ValueObject;

use App\Shared\Core\Enums\AppConstants;
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

final readonly class SyncMdb
{
    private const SYNCED     = null;
    private const NOT_SYNCED = 1;

    private function __construct(
        private ?int $value
    ) {
    }

    public static function field(): string
    {
        return AppConstants::SYNC_MONGODB;
    }

    public static function create(?int $value): self
    {
        if ($value !== self::SYNCED && $value !== self::NOT_SYNCED) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'sync_mdb.invalid_value', 
                    params: [
                        'allowed_values' => 'null, 1', 
                        'value' => $value
                    ]
                )
            );
        }

        return new self($value);
    }

    public static function fromInt(?int $value): self
    {
        return self::create($value);
    }

    public static function fromString(?string $value): self
    {
        if ($value === null || $value === '') {
            return new self(self::SYNCED);
        }

        if (!\is_numeric($value)) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'sync_mdb.invalid_format', 
                    params: [
                        'value' => $value
                    ]
                )
            );
        }

        return self::create((int) $value);
    }

    public static function pending(): self
    {
        return new self(self::NOT_SYNCED);
    }

    public static function synced(): self
    {
        return new self(self::SYNCED);
    }

    public function value(): ?int
    {
        return $this->value;
    }

    public function toInt(): ?int
    {
        return $this->value;
    }

    public function isNull(): bool
    {
        return $this->value === self::SYNCED;
    }

    public function isPending(): bool
    {
        return $this->value === self::NOT_SYNCED;
    }

    public function isSynced(): bool
    {
        return $this->value === self::SYNCED;
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }
}
