<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

// Domain Layer
use App\Domain\Product\Entity\Product;

// Vendor Layer
use MongoDB\BSON\UTCDateTime;

final class MdbProductSchema
{
    public static function toArray(Product $product): array
    {
        return [
            'name'         => $product->getName(),
            'status'       => $product->getStatus()->value(),
            'detail_info'  => $product->getDetailInfo()->toArray(),
            'lock_version' => $product->getLockVersion()->value(),
            'sync_at'      => new UTCDateTime(),
        ];
    }
}