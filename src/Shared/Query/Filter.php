<?php

declare(strict_types=1);

namespace App\Shared\Query;

/**
 * Generic Filter
 * 
 * Digunakan untuk semua domain filter operations
 * Menggantikan domain-specific Filter classes
 */
class Filter
{
    public function __construct(
        public ?int $id = null,
        public ?string $name = null,
        public ?int $status = null,
        public ?string $search = null,
        public ?array $ids = null,
        public array $additional = []
    ) {}

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'] ?? null,
            name: $data['name'] ?? null,
            status: $data['status'] ?? null,
            search: $data['search'] ?? null,
            ids: $data['ids'] ?? null,
            additional: array_diff_key($data, array_flip(['id', 'name', 'status', 'search', 'ids']))
        );
    }

    /**
     * Get filter criteria for query
     */
    public function getCriteria(): array
    {
        $criteria = [];

        if ($this->id !== null) {
            $criteria['id'] = $this->id;
        }

        if ($this->name !== null) {
            $criteria['name'] = $this->name;
        }

        if ($this->status !== null) {
            $criteria['status'] = $this->status;
        }

        if ($this->search !== null) {
            $criteria['search'] = $this->search;
        }

        if ($this->ids !== null) {
            $criteria['ids'] = $this->ids;
        }

        // Merge additional criteria
        $criteria = array_merge($criteria, $this->additional);

        return $criteria;
    }

    /**
     * Check if filter is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->getCriteria());
    }

    /**
     * Create filter with additional criteria
     */
    public function withAdditional(array $additional): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            status: $this->status,
            search: $this->search,
            ids: $this->ids,
            additional: array_merge($this->additional, $additional)
        );
    }

    /**
     * Get value by key (including additional)
     */
    public function get(string $key, mixed $default = null): mixed
    {
        switch ($key) {
            case 'id':
                return $this->id;
            case 'name':
                return $this->name;
            case 'status':
                return $this->status;
            case 'search':
                return $this->search;
            case 'ids':
                return $this->ids;
            default:
                return $this->additional[$key] ?? $default;
        }
    }

    /**
     * Check if has key (including additional)
     */
    public function has(string $key): bool
    {
        switch ($key) {
            case 'id':
                return $this->id !== null;
            case 'name':
                return $this->name !== null;
            case 'status':
                return $this->status !== null;
            case 'search':
                return $this->search !== null;
            case 'ids':
                return $this->ids !== null;
            default:
                return array_key_exists($key, $this->additional);
        }
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return array_merge([
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status,
            'search' => $this->search,
            'ids' => $this->ids,
        ], $this->additional);
    }
}
