<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Enum;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Peran/kepemilikan record dalam konteks sinkronisasi antar store.
 *
 * - LOCAL: record ditulis di store ini — user boleh mengedit.
 * - INTERSTORE: record datang lewat sync dan masih bisa diteruskan.
 * - COPY: salinan hasil sync — read-only untuk user, hanya sync applier yang menulis.
 */
enum DocRole: string
{
    case LOCAL      = 'LOCAL';
    case INTERSTORE = 'INTERSTORE';
    case COPY       = 'COPY';

    /**
     * Buat DocRole dari nilai mentah.
     *
     * @throws BadRequestException jika nilai di luar himpunan yang diizinkan.
     */
    public static function fromValue(string $value): self
    {
        return self::tryFrom($value) ?? throw new BadRequestException(
            translate: Message::create(
                domain: 'validation',
                key: 'doc_role.invalid_value',
                params: [
                    'allowed_values' => 'LOCAL, INTERSTORE, COPY',
                    'value' => $value,
                ]
            )
        );
    }

    public function label(): string
    {
        return match ($this) {
            self::LOCAL      => 'Local',
            self::INTERSTORE => 'Interstore',
            self::COPY       => 'Copy',
        };
    }
}
