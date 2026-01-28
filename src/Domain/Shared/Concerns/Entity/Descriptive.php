<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

// Domain Layer
use App\Domain\Shared\ValueObject\DetailInfo;

trait Descriptive
{
    /**
     * Get the description/detail object
     */
    public function getDetailInfo(): DetailInfo
    {
        return $this->detailInfo;
    }

    /**
     * Update the descriptive information
     */
    public function updateDetailInfo(DetailInfo $detailInfo): void
    {
        $this->detailInfo = $detailInfo;
    }

    /**
     * Helper for persistence layer
     */
    public function getDetailInfoJson(): string
    {
        return $this->detailInfo->toJson();
    }
}