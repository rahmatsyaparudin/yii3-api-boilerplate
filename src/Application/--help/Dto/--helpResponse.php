<?php

declare(strict_types=1);

namespace App\Application\--help\Dto;

use App\Domain\--help\Entity\--help;

final readonly class --helpResponse
{
    /**
     * Kita definisikan properti yang HANYA boleh dilihat oleh klien/API.
     */
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public bool $sync_mdb,
        public array $detail_info,
        public int $lock_version,
    ) {}

    /**
     * Static Factory Method: Mengubah Entity menjadi DTO.
     * Di sini kita melakukan transformasi format data.
     */
    public static function fromEntity(--help $--help): self
    {
        return new self(
            id: $--help->getId(),
            name: $--help->getName(),
            status: $--help->getStatus()->value(),
            sync_mdb: $--help->getSyncMdb() !== null,
            detail_info: $--help->getDetailInfo()->toArray(),
            lock_version: $--help->getLockVersion()->value(),
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