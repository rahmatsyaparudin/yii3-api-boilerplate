<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Example;

// Domain Layer
use App\Domain\Example\Entity\Example;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\ValueObject\DetailInfo;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Database\MongoService;
use App\Infrastructure\Persistence\Example\MdbExampleSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\OptimisticLockException;
use App\Shared\Query\QueryConditionApplier;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

// use MongoDB\Collection;

/**
 * Example Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class ExampleRepository implements ExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;

    private const SEQUENCE_ID = 'example_id_seq';

    private $collection;
    
    private const TABLE = 'example';
    private const LIKE_OPERATOR = 'ilike';

    public function __construct(
        private QueryConditionApplier $queryConditionApplier,
        private ConnectionInterface $db,
        private MongoService $mongoService,
    ) {
        $this->collection = $this->mongoService->getCollection(Example::RESOURCE);
    }

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

    private function syncToMongo(Example $example): void
    {
        $this->collection->updateOne(
            ['id' => $example->getId()],
            ['$set' => MdbExampleSchema::toArray($example)],
            ['upsert' => true]
        );
    }

    public function save(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            $exists = (new Query($this->db))
                ->from(self::TABLE)
                ->where(['id' => $example->getId()])
                ->exists();
            
            $result = $exists ? $this->update($example) : $this->insert($example);

            $this->syncToMongo($result);

            return $result;
        });
    }

    public function delete(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            // 1. Update di PostgreSQL
            $this->db->createCommand()
                ->update(
                    self::TABLE,
                    $this->getDeletedState(), 
                    [
                        'id' => $example->getId(),
                    ]
                )
                ->execute();

            $deletedExample = $example->markAsDeleted();

            $this->syncToMongo($deletedExample);

            return $deletedExample;
        });
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

        $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

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
                'lock_version' => $newLockVersion->value(),
            ], [
                'id' => $example->getId(),
                'lock_version' => $currentLockVersion->value()
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
