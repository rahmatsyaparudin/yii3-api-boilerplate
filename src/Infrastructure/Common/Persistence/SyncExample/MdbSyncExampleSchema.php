<?php

declare(strict_types=1);

namespace App\Infrastructure\Common\Persistence\SyncExample;

// Domain Layer
use App\Domain\SyncExample\Entity\SyncExample;
// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbSyncExampleSchema
{
    public static function toArray(SyncExample $entity): array
    {
        return [
            'name'            => $entity->getName(),
            'status'          => $entity->getStatus()->value(),
            'detail_info'     => $entity->getDetailInfo()->toArray(),
            'sync_flag'       => $entity->getSyncFlagValue(),
            'origin_id'       => $entity->getOriginId(),
            'source_store_id' => $entity->getSourceStoreId(),
            'uuid'            => $entity->getUuid(),
            'doc_role'        => $entity->getDocRoleValue(),
            'lock_version'    => $entity->getLockVersion()->value(),
            'sync_at'         => new UTCDateTime(),
        ];
    }
}
