<?php

declare(strict_types=1);

namespace App\Infrastructure\Common\Persistence\AnotherExample;

// Domain Layer
use App\Domain\AnotherExample\Entity\AnotherExample;
use App\Domain\AnotherExample\Repository\AnotherExampleRepositoryInterface;
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\LockVersion;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\Shared\Core\ValueObject\SyncMdb;
// Infrastructure Layer
use App\Infrastructure\Core\Concerns\HasCoreFeatures;
use App\Infrastructure\Core\Concerns\HasMongoDBSync;
use App\Infrastructure\Core\Concerns\ManagesPersistence;
use App\Infrastructure\Core\Database\MongoDB\MongoDBService;
use App\Infrastructure\Core\Security\CurrentUserAwareInterface;
// Shared Layer
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;
use App\Shared\Core\Query\QueryConditionApplier;
// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

// use MongoDB\Collection;

/**
 * AnotherExample Repository using Yiisoft/Db Query Builder.
 *
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class AnotherExampleRepository implements AnotherExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use HasMongoDBSync;
    use ManagesPersistence;

    public const TABLE_NAME     = 'another_example';
    public const SEQUENCE_ID    = 'another_example_id_seq';
    private const LIKE_OPERATOR = 'ilike';

    public function __construct(
        private ConnectionInterface $db,
        private QueryConditionApplier $queryConditionApplier,
        private MongoDBService $mongoDBService,
    ) {
        $this->initMongoDBSync(
            service: $mongoDBService,
            collection: self::TABLE_NAME
        );
    }

    public function getResource(): string
    {
        return AnotherExample::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?AnotherExample
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

        return AnotherExample::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            exampleId: (int) $row['example_id'],
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    public function findByName(string $name, ?int $status = null): ?AnotherExample
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

        return AnotherExample::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            exampleId: (int) $row['example_id'],
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        )->updateSyncMdb($row[SyncMdb::field()] ?? null);
    }

    public function existsByName(string $name, ?int $status = null): bool
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
                'example_id',
                'status',
                'detail_info',
                SyncMdb::field(),
                LockVersion::field(),
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
                'example_id',
                SyncMdb::field(),
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

        $rows = \iterator_to_array($this->streamRows(
            query: $query,
            jsonKeys: []
        ));

        return new PaginatedResult(
            data: $rows,
            total: $total,
            page: $criteria->page,
            pageSize: $criteria->pageSize,
            filter: $criteria->filter,
            sort: [
                'by'  => $criteria->sortBy,
                'dir' => $criteria->sortDir,
            ]
        );
    }

    public function insert(AnotherExample $entity): AnotherExample
    {
        return $this->db->transaction(function () use ($entity) {
            $this->db->createCommand()
                ->insert(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(
                        entity: $entity,
                        lockVersion: LockVersion::create()->value()
                    )
                )
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            $newEntity = AnotherExample::reconstitute(
                id: $newId,
                name: $entity->getName(),
                exampleId: $entity->getExampleId(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbAnotherExampleSchema::class
            );

            return $newEntity;
        });
    }

    public function update(AnotherExample $entity): AnotherExample
    {
        return $this->db->transaction(function () use ($entity) {
            $currentLock = $entity->getLockVersion();
            $newLock     = $this->upgradeEntityLockVersion($entity);

            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(
                        entity: $entity,
                        lockVersion: $newLock->value()
                    ),
                    $this->buildLockCondition(
                        entity: $entity,
                        currentLockVersion: $currentLock->value()
                    )
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure($entity);
            }

            $this->syncMongoDB(
                entity: $entity,
                schemaClass: MdbAnotherExampleSchema::class
            );

            return $entity;
        });
    }

    public function delete(AnotherExample $entity): AnotherExample
    {
        return $this->db->transaction(function () use ($entity) {
            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->getDeletedState(),
                    $this->buildSimpleCondition($entity)
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure($entity, false);
            }

            $deletedEntity = $entity->markAsDeleted();

            $this->syncMongoDB(
                entity: $deletedEntity,
                schemaClass: MdbAnotherExampleSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?AnotherExample
    {
        // 1. Find deleted record
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereDeleted())
            ->one();

        if (!$row) {
            return null;
        }

        $entity = AnotherExample::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            exampleId: (int) $row['example_id'],
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()])
        );

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function mapEntityToTable(AnotherExample $entity, int $lockVersion): array
    {
        return [
            'name'               => $entity->getName(),
            'example_id'         => $entity->getExampleId(),
            'status'             => $entity->getStatus()->value(),
            'detail_info'        => $entity->getDetailInfo()->toArray(),
            SyncMdb::field()     => $entity->getSyncMdbValue(),
            LockVersion::field() => $lockVersion,
        ];
    }
}
