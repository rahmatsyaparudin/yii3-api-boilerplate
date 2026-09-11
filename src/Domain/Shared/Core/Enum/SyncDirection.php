<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Enum;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Arah sinkronisasi record antara master dan origin.
 */
enum SyncDirection: int
{
    case NONE             = 0;
    case MASTER_TO_ORIGIN = 1;
    case ORIGIN_TO_MASTER = 2;
    case BIDIRECTIONAL    = 3;

    /**
     * Buat SyncDirection dari nilai mentah.
     *
     * @throws BadRequestException jika nilai di luar rentang yang diizinkan (0-3).
     */
    public static function fromValue(int $value): self
    {
        return self::tryFrom($value) ?? throw new BadRequestException(
            translate: Message::create(
                domain: 'validation',
                key: 'sync_flag.invalid_direction',
                params: [
                    'allowed_values' => '0, 1, 2, 3',
                    'value' => $value,
                ]
            )
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::NONE             => 'None',
            self::MASTER_TO_ORIGIN => 'Master to Origin',
            self::ORIGIN_TO_MASTER => 'Origin to Master',
            self::BIDIRECTIONAL    => 'Bidirectional',
        };
    }
}
