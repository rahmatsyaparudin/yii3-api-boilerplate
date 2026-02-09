<?php

declare(strict_types=1);

namespace App\Application\Example\Dto;

use App\Domain\Example\Entity\Example;

final readonly class ExampleResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Example $example): self
    {
        return new self(
            id: $example->getId(),
            name: $example->getName(),
            status: $example->getStatus()->value(),
            detail_info: $example->getDetailInfo()->toArray(),
            sync_mdb: $example->getSyncMdbValue(),
            lock_version: $example->getLockVersion()->value(),
        );
    }

    public function toArray(): array
    {
        return get_object_vars($this);
    }
}