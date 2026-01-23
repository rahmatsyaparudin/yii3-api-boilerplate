<?php

declare(strict_types=1);

namespace App\Application\Brand\Command;

final readonly class UpdateBrandCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?array $detailInfo,
        public ?bool $syncMdb,
    ) {}
}