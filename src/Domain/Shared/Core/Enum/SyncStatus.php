<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Enum;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Status sinkronisasi record untuk kolom `sync_flag` (smallint).
 *
 * Representasi DB: null = synced, 1 = not synced. Default: 1.
 */
enum SyncStatus
{
    case SYNCED;
    case NOT_SYNCED;

    /**
     * Nilai yang disimpan ke kolom sync_flag.
     */
    public function dbValue(): ?int
    {
        return match ($this) {
            self::SYNCED     => null,
            self::NOT_SYNCED => 1,
        };
    }

    /**
     * Buat SyncStatus dari nilai mentah kolom sync_flag.
     *
     * @throws BadRequestException jika nilai di luar rentang yang diizinkan (null, 1).
     */
    public static function fromDbValue(?int $value): self
    {
        return match ($value) {
            null    => self::SYNCED,
            1       => self::NOT_SYNCED,
            default => throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation',
                    key: 'sync_flag.invalid_value',
                    params: [
                        'allowed_values' => 'null, 1',
                        'value' => $value,
                    ]
                )
            ),
        };
    }

    public function label(): string
    {
        return match ($this) {
            self::SYNCED     => 'Synced',
            self::NOT_SYNCED => 'Not Synced',
        };
    }

    public function isSynced(): bool
    {
        return $this === self::SYNCED;
    }

    public function isPending(): bool
    {
        return $this === self::NOT_SYNCED;
    }
}
