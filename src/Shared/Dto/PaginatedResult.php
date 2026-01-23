<?php

declare(strict_types=1);

namespace App\Shared\Dto;

final readonly class PaginatedResult
{
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $pageSize,
        public array $filter = [],
        public array $sort = []
    ) {
        if ($this->pageSize <= 0) {
            $this->pageSize = 10;
        }
    }

    public function getTotalPages(): int
    {
        return $this->total === 0 ? 1 : (int) ceil($this->total / $this->pageSize);
    }

    public function getMeta(): array
    {
        return [
            'filter' => $this->filter,
            'sort' => $this->sort,
            'pagination' => [
                'total' => $this->total,
                'display' => count($this->data),
                'page' => $this->page,
                'page_size' => $this->pageSize,
                // 'total_pages' => $this->getTotalPages(),
            ],
        ];
    }
}