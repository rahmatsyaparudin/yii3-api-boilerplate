<?php

declare(strict_types=1);

namespace App\Shared\Request;

final readonly class SortParams
{
    public function __construct(
        public ?string $by = null,
        public string $dir = 'asc'
    ) {
    }

    public function getSortBy(): ?string
    {
        return $this->by;
    }

    public function getSortDir(): string
    {
        return $this->dir;
    }

    public function toArray(): array
    {
        return [
            'by' => $this->by,
            'dir' => $this->dir,
        ];
    }
}
