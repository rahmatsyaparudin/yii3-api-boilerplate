<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Enum;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Status satu baris antrian di tabel sync_queue.
 *
 * Satu record bisnis bisa punya banyak baris antrian (satu per tujuan).
 */
enum SyncQueueStatus: int
{
    case PENDING = 1;
    case SENT    = 2;
    case ACKED   = 3;
    case FAILED  = 4;

    /**
     * Buat SyncQueueStatus dari nilai mentah.
     *
     * @throws BadRequestException jika nilai di luar rentang yang diizinkan (1-4).
     */
    public static function fromValue(int $value): self
    {
        return self::tryFrom($value) ?? throw new BadRequestException(
            translate: Message::create(
                domain: 'validation',
                key: 'sync_queue.invalid_status',
                params: [
                    'allowed_values' => '1, 2, 3, 4',
                    'value' => $value,
                ]
            )
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::PENDING => 'Pending',
            self::SENT    => 'Sent',
            self::ACKED   => 'Acked',
            self::FAILED  => 'Failed',
        };
    }

    public function isPending(): bool
    {
        return $this === self::PENDING;
    }

    public function isDone(): bool
    {
        return $this === self::ACKED;
    }
}
