<?php

declare(strict_types=1);

namespace App\Application\--help\Command;

final readonly class Create--helpCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?array $detailInfo,
        public ?bool $syncMdb = null,
    ) {}
}