<?php

declare(strict_types=1);

namespace App\Application\AnotherExample\Dto;

final readonly class AnotherExampleDetailInfo
{
    public function __construct(
        public ?array $example,
    ) {
    }

    public function toArray(): array
    {
        return [
            'example' => $this->example,
        ];
    }

    public function getExample(): ?array
    {
        return $this->example;
    }
}
