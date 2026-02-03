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
use App\Infrastructure\Concerns\HasMongoDBSync;
use App\Infrastructure\Concerns\ManagesPersistence;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\Example\MdbExampleSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\NotFoundException;
use App\Shared\Exception\OptimisticLockException;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

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
    use HasCoreFeatures, HasMongoDBSync, ManagesPersistence;

    public const TABLE_NAME = 'example';
    public const SEQUENCE_ID = 'example_id_seq';

    private const LIKE_OPERATOR = 'ilike';

    public function __construct(
        private QueryConditionApplier $queryConditionApplier,
        private ConnectionInterface $db,
        private MongoDBService $mongoDBService,
    ) {
        $this->initMongoDBSync($mongoDBService, self::TABLE_NAME);
    }

    public function findById(int $id, int|null $status = null): ?Example
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([
                'id' => $id,
            ])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return Example::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: (int) $row['lock_version']
        );
    }

    public function restore(int $id): ?Example
    {
        // 1. Find deleted record
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere(
                $this->scopeWhereDeleted(),
            )
            ->one();

        if (!$row) {
            return null;
        }

        // 2. Reconstitute entity with current data
        $entity = Example::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: (int) $row['lock_version']
        )->updateSyncMdb($row['sync_mdb'] ?? null);

        // 3. Restore entity status
        $entity->restore(); // This should change status from DELETED to DRAFT

        // 4. Use existing update method
        return $this->update($entity);
    }

    public function findByName(string $name, int|null $status = null): ?Example
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        if (!$row) {
            return null;
        }

        return Example::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: (int) $row['lock_version']
        )->updateSyncMdb($row['sync_mdb'] ?? null);
    }

    public function delete(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->getDeletedState(), 
                    $this->buildSimpleCondition($example)
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure($example, false);
            }

            $deletedExample = $example->markAsDeleted();

            $this->syncMongoDB($deletedExample, MdbExampleSchema::class);

            return $deletedExample;
        });
    }

    public function existsByName(string $name, int|null $status = null): bool
    {
        return (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
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
            ->from(self::TABLE_NAME)
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
            ->offset($criteria->calculateOffset());

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
            /** @var array<string, mixed> $row */
            $row['detail_info'] = DetailInfo::fromJson($row['detail_info'])->toArray();
            yield $row;
        }
    }

    public function insert(Example $example): Example 
    {
        return $this->db->transaction(function() use ($example) {
            // 1. Insert ke PostgreSQL
            $this->db->createCommand()
                ->insert(self::TABLE_NAME, [
                    'name' => $example->getName(),
                    'status' => $example->getStatus()->value(),
                    'detail_info' => $example->getDetailInfo()->toArray(),
                    'sync_mdb' => $example->getSyncMdb(),
                    'lock_version' => 1, 
                ])
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            // 2. Reconstitute dengan ID baru
            $newEntity = Example::reconstitute(
                id: $newId,
                name: $example->getName(),
                status: $example->getStatus(),
                detailInfo: $example->getDetailInfo(),
                lockVersion: 1
            );

            $this->syncMongoDB($newEntity, MdbExampleSchema::class);

            return $newEntity;
        });
    }

    public function update(Example $example): Example
    {
        return $this->db->transaction(function() use ($example) {
            $currentLock = $example->getLockVersion();
            $newLock = $currentLock->increment();
            
            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME, 
                    $this->mapEntityToTable($example, $newLock->value()), 
                    $this->buildLockCondition($example, $currentLock->value())
                )
                ->execute();
            
            if ($result === 0) {
                $this->handlePersistenceFailure($example);
            }
            
            $example->upgradeLockVersion();
            $this->syncMongoDB($example, MdbExampleSchema::class);
            
            return $example;
        });
    }

    private function mapEntityToTable(Example $example, int $newLockVersion): array
    {
        return [
            'name' => $example->getName(),
            'status' => $example->getStatus()->value(),
            'detail_info' => $example->getDetailInfo()->toArray(),
            'sync_mdb' => $example->getSyncMdb(), // State saat ini
            'lock_version' => $newLockVersion,
        ];
    }
}
