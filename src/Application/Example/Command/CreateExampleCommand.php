<?php

declare(strict_types=1);

namespace App\Application\Example\Command;

final readonly class CreateExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?array $detailInfo,
    ) {}
}