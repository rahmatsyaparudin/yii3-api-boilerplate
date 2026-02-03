<?php

declare(strict_types=1);

namespace App\Application\Example\Dto;

use App\Domain\Example\Entity\Example;

final readonly class ExampleResponse
{
    /**
     * Kita definisikan properti yang HANYA boleh dilihat oleh klien/API.
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public ?int $sync_mdb,
        public array $detail_info,
        public int $lock_version,
    ) {}

    /**
     * Static Factory Method: Mengubah Entity menjadi DTO.
     * Di sini kita melakukan transformasi format data.
     */
    public static function fromEntity(Example $example): self
    {
        return new self(
            id: $example->getId(),
            name: $example->getName(),
            status: $example->getStatus()->value(),
            sync_mdb: $example->getSyncMdb(),
            detail_info: $example->getDetailInfo()->toArray(),
            lock_version: $example->getLockVersion()->value(),
        );
    }

    /**
     * Helper untuk mengubah objek menjadi array agar bisa di-encode ke JSON.
     */
    public function toArray(): array
    {
        return get_object_vars($this);
    }
}