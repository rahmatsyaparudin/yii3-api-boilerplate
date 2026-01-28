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
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Security\CurrentUserAwareInterface;
use App\Shared\Exception\OptimisticLockException;

/**
 * Brand Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class BrandRepository implements BrandRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    
    private const TABLE = 'brand';
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
            ->andWhere(
                $this->scopeWhereNotDeleted(),
            )
            ->one();

        return $row ? Brand::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        ) : null;
    }

    public function restore(int $id): ?Brand
    {
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['id' => $id])
            ->andWhere(
                $this->scopeWhereDeleted(),
            )
            ->one();

        return $row ? Brand::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        ) : null;
    }

    public function findByName(string $name): ?Brand
    {
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->one();

        return $row ? Brand::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
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

    public function delete(Brand $brand): Brand
    {
        $this->db->createCommand()
            ->update(
                self::TABLE,
                $this->getDeletedState(), 
                [
                    'id' => $brand->getId(),
                ]
            )
            ->execute();

        return $brand->markAsDeleted();
    }

    public function existsByName(string $name): bool
    {
        return (new Query($this->db))
            ->from(self::TABLE)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->exists();
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
                'lock_version',
            ])
            ->from(self::TABLE)
            ->where($this->scopeWhereNotDeleted());

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
            $row['detail_info'] = DetailInfo::fromJson($row['detail_info'])->toArray();
            yield $row;
        }
    }

    private function insert(Brand $brand): Brand 
    {
        $this->db->createCommand()
            ->insert(self::TABLE, [
                'name' => $brand->getName(),
                'status' => $brand->getStatus()->value(),
                'detail_info' => $brand->getDetailInfo()->toArray(),
                'sync_mdb' => $brand->getSyncMdb(),
                'lock_version' => 1, 
            ])
            ->execute();

        $newId = (int) $this->db->getLastInsertID('brand_id_seq');

        return Brand::reconstitute(
            id: $newId,
            name: $brand->getName(),
            status: $brand->getStatus(),
            detailInfo: $brand->getDetailInfo(),
            syncMdb: $brand->getSyncMdb(),
            lockVersion: 1
        );
    }

    private function update(Brand $brand): Brand
    {
        // Get current and new lock versions
        $currentLockVersion = $brand->getLockVersion();
        $newLockVersion = $currentLockVersion->increment();
        
        $result = $this->db->createCommand()
            ->update(self::TABLE, [
                'name' => $brand->getName(),
                'status' => $brand->getStatus()->value(),
                'detail_info' => $brand->getDetailInfo()->toArray(),
                'sync_mdb' => $brand->getSyncMdb(),
                'lock_version' => $newLockVersion->getValue(),
            ], [
                'id' => $brand->getId(),
                'lock_version' => $currentLockVersion->getValue()
            ])
            ->execute();
            
        // Check if update was successful (optimistic locking)
        if ($result === 0) {
            throw new OptimisticLockException(
                translate: new Message(
                    key: 'optimistic.lock.failed',
                    params: [
                        'resource' => Brand::RESOURCE,
                    ]
                )
            );
        }
        
        // Update the entity's lock version
        $brand->upgradeVersion();
            
        return $brand;
    }
}
