<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\--help;

// Domain Layer
use App\Domain\--help\Entity\--help;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class Mdb--helpSchema
{
    public static function toArray(--help $--help): array
    {
        return [
            'name'         => $--help->getName(),
            'status'       => $--help->getStatus()->value(),
            'detail_info'  => $--help->getDetailInfo()->toArray(),
            'lock_version' => $--help->getLockVersion()->value(),
            'sync_at'      => new UTCDateTime(),
        ];
    }
}