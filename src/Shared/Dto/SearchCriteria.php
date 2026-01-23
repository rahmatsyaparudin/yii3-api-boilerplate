<?php

declare(strict_types=1);

namespace App\Shared\Dto;

final readonly class SearchCriteria
{
    public function __construct(
        public array $filter,
        public int $page,
        public int $pageSize = 10,
        public string $sortBy = 'id',
        public string $sortDir = 'asc',
        public ?int $offset = null,
        private array $allowedSort = ['id' => 'id'],
    ) {}

    public function calculateOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    public function getOffset(): int
    {
        return $this->offset ?? $this->calculateOffset();
    }

    public function getOrderClause(): array
    {
        $column = $this->allowedSort[$this->sortBy] ?? array_values($this->allowedSort)[0];
        $direction = strtolower($this->sortDir) === 'desc' ? SORT_DESC : SORT_ASC;

        return [$column => $direction];
    }
}
