<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Example;

// Domain Layer
use App\Domain\Example\Entity\Example;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbExampleSchema
{
    public static function toArray(Example $example): array
    {
        return [
            'name'         => $example->getName(),
            'status'       => $example->getStatus()->value(),
            'detail_info'  => $example->getDetailInfo()->toArray(),
            'lock_version' => $example->getLockVersion()->value(),
            'sync_at'      => new UTCDateTime(),
            '__v'          => '1.0', 
        ];
    }
}