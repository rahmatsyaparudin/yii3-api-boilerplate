<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Example;

// Domain Layer
use App\Domain\Example\Entity\Example;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbExampleSchema
{
    public static function toArray(Example $entity): array
    {
        return [
            'name' => $entity->getName(),
            'status' => $entity->getStatus()->value(),
            'detail_info' => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at' => new UTCDateTime(),
        ];
    }
}