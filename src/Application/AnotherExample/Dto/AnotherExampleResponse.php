<?php

declare(strict_types=1);

namespace App\Application\AnotherExample\Dto;

use App\Domain\AnotherExample\Entity\AnotherExample;

final readonly class AnotherExampleResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $example_id,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(AnotherExample $entity): self
    {
        return new self(
            id: $entity->getId(),
            name: $entity->getName(),
            example_id: $entity->getExampleId(),
            status: $entity->getStatus()->value(),
            detail_info: $entity->getDetailInfo()->toArray(),
            sync_mdb: $entity->getSyncMdbValue(),
            lock_version: $entity->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}
