<?php

declare(strict_types=1);

namespace App\Infrastructure\Common\Persistence\AnotherExample;

// Domain Layer
use App\Domain\AnotherExample\Entity\AnotherExample;
// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbAnotherExampleSchema
{
    public static function toArray(AnotherExample $entity): array
    {
        return [
            'name'         => $entity->getName(),
            'example_id'   => $entity->getExampleId(),
            'status'       => $entity->getStatus()->value(),
            'detail_info'  => $entity->getDetailInfo()->toArray(),
            'lock_version' => $entity->getLockVersion()->value(),
            'sync_at'      => new UTCDateTime(),
        ];
    }
}
