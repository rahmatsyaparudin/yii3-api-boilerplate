<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\--help;

// Domain Layer
use App\Domain\--help\Entity\--help;
use App\Domain\--help\Repository\--helpRepositoryInterface;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\ValueObject\DetailInfo;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\--help\Mdb--helpSchema;
use App\Infrastructure\Security\CurrentUserAwareInterface;

// Shared Layer
use App\Shared\Dto\PaginatedResult;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Exception\OptimisticLockException;
use App\Shared\Query\QueryConditionApplier;
use App\Shared\ValueObject\Message;

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

// use MongoDB\Collection;

/**
 * --help Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class --helpRepository implements --helpRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;

    private const SEQUENCE_ID = '--help_id_seq';
    private const TABLE = '--help';
    private const LIKE_OPERATOR = 'ilike';
    
    private ?object $collection = null;

    public function __construct(
        private QueryConditionApplier $queryConditionApplier,
        private ConnectionInterface $db,
        private MongoDBService $mongoDBService,
    ) {
        $this->db->getSchema()->getTableSchema(self::TABLE);
        $this->collection = $this->mongoDBService->getCollection(self::TABLE);
    }

    public function findById(int $id): ?--help
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where([
                'id' => $id,
            ])
            ->andWhere(
                $this->scopeWhereNotDeleted(),
            )
            ->one();

        if (!$row) {
            return null;
        }

        return --help::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        );
    }

    public function restore(int $id): ?--help
    {
        // 1. Find deleted record
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['id' => $id])
            ->andWhere(
                $this->scopeWhereDeleted(),
            )
            ->one();

        if (!$row) {
            return null;
        }

        // 2. Reconstitute entity with current data
        $entity = --help::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        );

        // 3. Restore entity status
        $entity->restore(); // This should change status from DELETED to DRAFT

        // 4. Use existing update method
        return $this->update($entity);
    }

    public function findByName(string $name): ?--help
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE)
            ->where(['name' => $name])
            ->andWhere($this->scopeWhereNotDeleted())
            ->one();

        if (!$row) {
            return null;
        }

        return --help::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        );
    }

    private function syncToMongo(--help $--help): void
    {
        if ($this->collection !== null) {
            $this->collection->updateOne(
                ['id' => $--help->getId()],
                ['$set' => Mdb--helpSchema::toArray($--help)],
                ['upsert' => true]
            );
        }
    }

    public function delete(--help $--help): --help
    {
        return $this->db->transaction(function() use ($--help) {
            // 1. Update di PostgreSQL
            $this->db->createCommand()
                ->update(
                    self::TABLE,
                    $this->getDeletedState(), 
                    [
                        'id' => $--help->getId(),
                    ]
                )
                ->execute();

            $deleted--help = $--help->markAsDeleted();

            $this->syncToMongo($deleted--help);

            return $deleted--help;
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

    public function insert(--help $--help): --help 
    {
        return $this->db->transaction(function() use ($--help) {
            // 1. Insert ke PostgreSQL
            $this->db->createCommand()
                ->insert(self::TABLE, [
                    'name' => $--help->getName(),
                    'status' => $--help->getStatus()->value(),
                    'detail_info' => $--help->getDetailInfo()->toArray(),
                    'sync_mdb' => $--help->getSyncMdb(),
                    'lock_version' => 1, 
                ])
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            // 2. Reconstitute dengan ID baru
            $newEntity = --help::reconstitute(
                id: $newId,
                name: $--help->getName(),
                status: $--help->getStatus(),
                detailInfo: $--help->getDetailInfo(),
                syncMdb: $--help->getSyncMdb(),
                lockVersion: 1
            );

            // 3. Sync ke MongoDB (jika diperlukan)
            $this->syncToMongo($newEntity);

            return $newEntity;
        });
    }

    public function update(--help $--help): --help
    {
        return $this->db->transaction(function() use ($--help) {
            // Get current and new lock versions
            $currentLockVersion = $--help->getLockVersion();
            $newLockVersion = $currentLockVersion->increment();
            
            $result = $this->db->createCommand()
                ->update(self::TABLE, [
                    'name' => $--help->getName(),
                    'status' => $--help->getStatus()->value(),
                    'detail_info' => $--help->getDetailInfo()->toArray(),
                    'sync_mdb' => $--help->getSyncMdb(),
                    'lock_version' => $newLockVersion->value(),
                ], [
                    'id' => $--help->getId(),
                    'lock_version' => $currentLockVersion->value()
                ])
                ->execute();
            
            // Check if update was successful (optimistic locking)
            if ($result === 0) {
                throw new OptimisticLockException(
                    translate: new Message(
                        key: 'optimistic.lock.failed',
                        params: [
                            'resource' => --help::RESOURCE,
                        ]
                    )
                );
            }
            
            // Update the entity's lock version
            $--help->upgradeLockVersion();
            
            // Sync updated entity to MongoDB
            $this->syncToMongo($--help);
            
            return $--help;
        });
    }
}
