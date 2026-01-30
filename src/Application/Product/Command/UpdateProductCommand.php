<?php

declare(strict_types=1);

namespace App\Application\Product\Command;

final readonly class UpdateProductCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?array $detailInfo,
        public ?bool $syncMdb,
        public ?int $lockVersion,
    ) {}
}