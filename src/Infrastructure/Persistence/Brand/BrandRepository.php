<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Brand;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\ValueObject\DetailInfo;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Dto\PaginatedResult;

/**
 * Brand Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class BrandRepository implements BrandRepositoryInterface
{
    private const TABLE = 'brand';
    private const RESOURCE = 'Brand';
    private const LIKE_OPERATOR = 'ilike';

    public function __construct(
        private QueryConditionApplier $queryConditionApplier,
        private ConnectionInterface $db,
    ) {}

    public function findById(int $id): ?Brand
    {
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where([
                'id' => $id,
            ])
            ->andWhere(['<>', 'status', Status::deleted()->value()])
            ->one();

        return $row ? Brand::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: $this->createDetailInfo($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null
        ) : null;
    }

    public function findByName(string $name): ?Brand
    {
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['name' => $name])
            ->one();

        return $row ? Brand::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: $this->createDetailInfo($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null
        ) : null;
    }

    public function save(Brand $brand): Brand
    {
        return $this->db->transaction(function() use ($brand) {
            $exists = (new Query($this->db))
                ->from(self::TABLE)
                ->where(['id' => $brand->getId()])
                ->exists();
            
            return $exists ? $this->update($brand) : $this->insert($brand);
        });
    }

    public function delete(Brand $brand): void
    {
        $this->db->createCommand()
            ->delete(self::TABLE, ['id' => $brand->getId()])
            ->execute();
    }

    public function existsByName(string $name): bool
    {
        return (new Query($this->db))
            ->from(self::TABLE)
            ->where(['name' => $name])
            ->count() > 0;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'status',
                'detail_info',
                'sync_mdb',
            ])
            ->from(self::TABLE);

        $filter = $criteria->filter;

        $this->queryConditionApplier->filterByExactMatch(
            query: $query, 
            filters: $filter, 
            allowedColumns: [
                'id', 
                'status', 
                'sync_mdb'
            ]
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query, 
                operator: self::LIKE_OPERATOR,
                conditions: ['name' => $filter['name']]
            );
        }

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->getOffset());

       $rows = iterator_to_array($this->listAllGenerator($query));

        return new PaginatedResult(
            data: $rows,
            total: $total,
            page: $criteria->page,
            pageSize: $criteria->pageSize,
            filter: $criteria->filter,
            sort: [
                'by' => $criteria->sortBy,
                'dir' => $criteria->sortDir
            ]
        );
    }

    private function listAllGenerator(Query $query): iterable
    {
        foreach ($query->each(100, $this->db) as $row) {
            $row['detail_info'] = json_decode($row['detail_info'] ?? '{}', true);
            yield $row;
        }
    }

    public function list2(array $filter = [], int $page = 1, int $limit = 20, string $sortBy = 'name', string $sortDir = 'asc'): array
    {
        $query = (new Query($this->db))
            ->from(self::TABLE)
            ->select(['id', 'name', 'status', 'detail_info', 'sync_mdb']);

        // Apply filters
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

        // Get total count
        $total = (clone $query)->count();

        // Apply sorting
        $direction = $sortDir === 'asc' ? SORT_ASC : SORT_DESC;
        $query->orderBy([$sortBy => $direction]);

        // Apply pagination
        $offset = ($page - 1) * $limit;
        $query->offset($offset)->limit($limit);

        // Execute query and parse detail_info
        $rows = $query->all();
        $items = array_map(function ($row) {
            // Parse detail_info JSON to object structure
            if (isset($row['detail_info']) && is_string($row['detail_info'])) {
                $decoded = json_decode($row['detail_info'], true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $row['detail_info'] = $decoded;
                }
            }
            return $row;
        }, $rows);

        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'limit' => $limit
        ];
    }

    private function insert(Brand $brand): Brand 
    {
        $this->db->createCommand()
            ->insert(self::TABLE, [
                'name' => $brand->getName(),
                'status' => $brand->getStatus()->value(),
                'detail_info' => $brand->getDetailInfo()->toArray(),
                'sync_mdb' => $brand->getSyncMdb(),
            ])
            ->execute();

        $newId = (int) $this->db->getLastInsertID('brand_id_seq');

        return Brand::reconstitute(
            id: $newId,
            name: $brand->getName(),
            status: $brand->getStatus(),
            detailInfo: $brand->getDetailInfo(),
            syncMdb: $brand->getSyncMdb()
        );
    }

    private function update(Brand $brand): Brand
    {
        $this->db->createCommand()
            ->update(self::TABLE, [
                'name' => $brand->getName(),
                'status' => $brand->getStatus()->value(),
                'detail_info' => $brand->getDetailInfo()->toArray(),
                'sync_mdb' => $brand->getSyncMdb(),
            ], ['id' => $brand->getId()])
            ->execute();
            
        return $brand;
    }

    private function createDetailInfo(mixed $data): DetailInfo
    {
        // If data is already an array, create DetailInfo from array
        if (is_array($data)) {
            return DetailInfo::fromArray($data);
        }
        
        // If data is JSON string, parse it first
        if (is_string($data)) {
            $decoded = json_decode($data, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return DetailInfo::fromArray($decoded);
            }
        }
        
        // Fallback to empty array if invalid data
        return DetailInfo::fromArray([]);
    }
}
