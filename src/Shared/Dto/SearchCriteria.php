<?php

declare(strict_types=1);

namespace App\Shared\Dto;

use App\Shared\Request\RequestParams;

/**
 * Generic Search Criteria DTO
 * 
 * Reusable search criteria for any entity/domain
 * No dependencies on specific business logic
 */
final class SearchCriteria
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

    /**
     * Calculate offset from page and pageSize
     */
    public function calculateOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    /**
     * Get offset (calculated or manual)
     */
    public function getOffset(): int
    {
        return $this->offset ?? $this->calculateOffset();
    }

    /**
     * Get order clause for Yii Query Builder
     */
    public function getOrderClause(): array
    {
        $column = $this->allowedSort[$this->sortBy] ?? array_values($this->allowedSort)[0];
        $direction = strtolower($this->sortDir) === 'desc' ? SORT_DESC : SORT_ASC;

        return [$column => $direction];
    }

    public static function fromPayload(RequestParams $payload, array $allowedSort): self
    {
        $pagination = $payload->getPagination();
        $sort       = $payload->getSort();
        $filter     = $payload->getFilter();

        return new self(
            filter: $filter ? $filter->toArray() : [], 
            page: $pagination->page ?? 1,
            pageSize: $pagination->page_size ?? 15,
            sortBy: $sort->by ?? 'id',
            sortDir: $sort->dir ?? 'desc',
            allowedSort: $allowedSort
        );
    }
}
