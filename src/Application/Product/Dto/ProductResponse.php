<?php

declare(strict_types=1);

namespace App\Application\Product\Dto;

use App\Domain\Product\Entity\Product;

final readonly class ProductResponse
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
    public static function fromEntity(Product $product): self
    {
        return new self(
            id: $product->getId(),
            name: $product->getName(),
            status: $product->getStatus()->value(),
            sync_mdb: $product->getSyncMdb() !== null,
            detail_info: $product->getDetailInfo()->toArray(),
            lock_version: $product->getLockVersion()->value(),
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