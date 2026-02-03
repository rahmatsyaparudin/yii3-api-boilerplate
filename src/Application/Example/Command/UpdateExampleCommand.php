<?php

declare(strict_types=1);

namespace App\Application\Example\Command;

final readonly class UpdateExampleCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}
}