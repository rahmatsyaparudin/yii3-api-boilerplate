<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Brand;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandQueryServiceInterface;
use App\Domain\Shared\ValueObject\Status;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Brand Query Service
 * 
 * Pure query implementation - no business logic
 */
final class BrandQueryService implements BrandQueryServiceInterface
{
    public function __construct(
        private ConnectionInterface $db
    ) {}

    public function list(array $params = []): array
    {
        $page = $params['page'] ?? 1;
        $limit = $params['limit'] ?? 20;
        $sortBy = $params['sort_by'] ?? 'name';
        $sortDir = $params['sort_dir'] ?? 'asc';
        $filter = $params['filter'] ?? [];

        $query = (new Query($this->db))
            ->from(['b' => 'brand'])
            ->select(['b.*']);

        // Apply filters
        foreach ($filter as $key => $value) {
            match ($key) {
                'search' => $query->andWhere(['or',
                    ['like', 'b.name', $value],
                    ['like', 'b.detail_info', $value]
                ]),
                'ids' => $query->andWhere(['in', 'b.id', $value]),
                default => $query->andWhere(["b.{$key}" => $value])
            };
        }

        // Get total count
        $total = (clone $query)->count();

        // Apply sorting
        $direction = $sortDir === 'asc' ? SORT_ASC : SORT_DESC;
        $query->orderBy(["b.{$sortBy}" => $direction]);

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $query->offset($offset)->limit($limit);

        return [
            'items' => $query->all(),
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    public function count(array $filter = []): int
    {
        $query = (new Query($this->db))
            ->from('brand');

        foreach ($filter as $key => $value) {
            match ($key) {
                'search' => $query->andWhere(['or',
                    ['like', 'name', $value],
                    ['like', 'detail_info', $value]
                ]),
                'ids' => $query->andWhere(['in', 'id', $value]),
                default => $query->andWhere(["{$key}" => $value])
            };
        }

        return $query->count();
    }

    public function search(string $searchTerm, ?int $limit = null): array
    {
        $query = (new Query($this->db))
            ->from(['b' => 'brand'])
            ->select(['b.*'])
            ->andWhere(['or',
                ['like', 'b.name', $searchTerm],
                ['like', 'b.detail_info', $searchTerm]
            ]);

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->all();
    }
}
