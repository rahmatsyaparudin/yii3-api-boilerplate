<?php

declare(strict_types=1);

namespace App\Application\SyncExample\Dto;

use App\Domain\SyncExample\Entity\SyncExample;

final readonly class SyncExampleResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public ?int $sync_flag,
        public ?int $origin_id,
        public ?int $source_store_id,
        public ?string $uuid,
        public ?string $doc_role,
        public int $lock_version,
    ) {
    }

    public static function fromEntity(SyncExample $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            sync_flag: $entity->getSyncFlagValue(),
            origin_id: $entity->getOriginId(),
            source_store_id: $entity->getSourceStoreId(),
            uuid: $entity->getUuid(),
            doc_role: $entity->getDocRoleValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}
