<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Example;

use App\Domain\Example\Entity\Example;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
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
 * Example Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class ExampleRepository implements ExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    
    private const TABLE = 'example';
    private const LIKE_OPERATOR = 'ilike';

    public function __construct(
        private QueryConditionApplier $queryConditionApplier,
        private ConnectionInterface $db,
    ) {}

    public function findById(int $id): ?Example
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

        return $row ? Example::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        ) : null;
    }

    public function restore(int $id): ?Example
    {
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['id' => $id])
            ->andWhere(
                $this->scopeWhereDeleted(),
            )
            ->one();

        return $row ? Example::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        ) : null;
    }

    public function findByName(string $name): ?Example
    {
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->one();

        return $row ? Example::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        ) : null;
    }

    public function save(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            $exists = (new Query($this->db))
                ->from(self::TABLE)
                ->where(['id' => $example->getId()])
                ->exists();
            
            return $exists ? $this->update($example) : $this->insert($example);
        });
    }

    public function delete(Example $example): Example
    {
        $this->db->createCommand()
            ->update(
                self::TABLE,
                $this->getDeletedState(), 
                [
                    'id' => $example->getId(),
                ]
            )
            ->execute();

        return $example->markAsDeleted();
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

    private function insert(Example $example): Example 
    {
        $this->db->createCommand()
            ->insert(self::TABLE, [
                'name' => $example->getName(),
                'status' => $example->getStatus()->value(),
                'detail_info' => $example->getDetailInfo()->toArray(),
                'sync_mdb' => $example->getSyncMdb(),
                'lock_version' => 1, 
            ])
            ->execute();

        $newId = (int) $this->db->getLastInsertID('example_id_seq');

        return Example::reconstitute(
            id: $newId,
            name: $example->getName(),
            status: $example->getStatus(),
            detailInfo: $example->getDetailInfo(),
            syncMdb: $example->getSyncMdb(),
            lockVersion: 1
        );
    }

    private function update(Example $example): Example
    {
        // Get current and new lock versions
        $currentLockVersion = $example->getLockVersion();
        $newLockVersion = $currentLockVersion->increment();
        
        $result = $this->db->createCommand()
            ->update(self::TABLE, [
                'name' => $example->getName(),
                'status' => $example->getStatus()->value(),
                'detail_info' => $example->getDetailInfo()->toArray(),
                'sync_mdb' => $example->getSyncMdb(),
                'lock_version' => $newLockVersion->getValue(),
            ], [
                'id' => $example->getId(),
                'lock_version' => $currentLockVersion->getValue()
            ])
            ->execute();
            
        // Check if update was successful (optimistic locking)
        if ($result === 0) {
            throw new OptimisticLockException(
                translate: new Message(
                    key: 'optimistic.lock.failed',
                    params: [
                        'resource' => Example::RESOURCE,
                    ]
                )
            );
        }
        
        // Update the entity's lock version
        $example->upgradeVersion();
            
        return $example;
    }
}
