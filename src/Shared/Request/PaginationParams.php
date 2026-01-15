<?php

declare(strict_types=1);

namespace App\Shared\Request;

final readonly class PaginationParams
{
    public function __construct(
        public int $page = 1,
        public int $page_size = 50
    ) {
    }

    public function getLimit(): int
    {
        return $this->page_size;
    }

    public function getOffset(): int
    {
        return ($this->page - 1) * $this->page_size;
    }

    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'page_size' => $this->page_size,
        ];
    }
}
