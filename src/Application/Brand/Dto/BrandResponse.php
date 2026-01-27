<?php

declare(strict_types=1);

namespace App\Application\Brand\Dto;

use App\Domain\Brand\Entity\Brand;

final readonly class BrandResponse
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
    public static function fromEntity(Brand $brand): self
    {
        return new self(
            id: $brand->getId(),
            name: $brand->getName(),
            status: $brand->getStatus()->value(),
            sync_mdb: $brand->getSyncMdb() !== null,
            detail_info: $brand->getDetailInfo()->toArray(),
            lock_version: $brand->getLockVersion()->getValue(),
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