<?php

declare(strict_types=1);

namespace App\Domain\Brand\Application;

use App\Domain\Shared\ValueObject\StatusEnum;

final class BrandInput
{
    public array $detailInfo;

    public function __construct(
        public ?string $name = null,
        public int|StatusEnum|null $status = null,
        ?array $detailInfo = null,
        public ?int $syncMdb = null
    ) {
        $this->detailInfo = $detailInfo ?? [];
    }

    public function toUpdateArray(?array $detailInfo = []): array
    {
        $data = [];

        if ($this->name !== null) $data['name'] = $this->name;
        if ($this->status !== null) $data['status'] = $this->status instanceof StatusEnum ? $this->status->value : $this->status;
        if ($detailInfo) $data['detail_info'] = $detailInfo;

        return $data;
    }

    public function getDetailInfo(): array
    {
        return $this->detailInfo;
    }

    public function withMergedDetailInfo(array $extra): self
    {
        return new self(
            name: $this->name,
            status: $this->status,
            detailInfo: array_merge($this->getDetailInfo(), $extra),
            syncMdb: $this->syncMdb
        );
    }
}
