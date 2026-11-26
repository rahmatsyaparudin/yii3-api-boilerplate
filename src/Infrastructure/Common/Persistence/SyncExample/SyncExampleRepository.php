<?php

declare(strict_types=1);

namespace App\Infrastructure\Common\Persistence\SyncExample;

// Domain Layer
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\LockVersion;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;
use App\Domain\Shared\Core\ValueObject\SyncIdentity;
use App\Domain\Shared\Core\ValueObject\SyncMdb;
use App\Domain\SyncExample\Entity\SyncExample;
use App\Domain\SyncExample\Repository\SyncExampleRepositoryInterface;
// Infrastructure Layer
use App\Application\Shared\Core\Factory\SyncOutboxFactory;
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

/**
 * SyncExample Repository using Yiisoft/Db Query Builder.
 *
 * Reference implementation: persists the full sync column set
 * (uuid, source_store_id, doc_role, origin_id, sync_flag, sync_mdb,
 * lock_version) and demonstrates the outbox pattern via
 * insertWithOutbox() — record + sync_queue rows in one transaction.
 */
final class SyncExampleRepository implements SyncExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use HasMongoDBSync;
    use ManagesPersistence;

    public const TABLE_NAME  = 'sync_example';
    public const SEQUENCE_ID = 'sync_example_id_seq';

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
        return SyncExample::RESOURCE;
    }

    public function findById(int $id, ?int $status = null): ?SyncExample
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

        return $this->reconstitute($row);
    }

    public function findByName(string $name, ?int $status = null): ?SyncExample
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

        return $this->reconstitute($row);
    }

    /**
     * Lookup by global record identity — dipakai sync applier karena id
     * lokal tidak berkorelasi lintas store.
     */
    public function findByUuid(string $uuid): ?SyncExample
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where([SyncIdentity::fieldUuid() => $uuid])
            ->andWhere($this->scopeWhereNotDeleted())
            ->one();

        if (!$row) {
            return null;
        }

        return $this->reconstitute($row);
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
                'status',
                'detail_info',
                SyncMdb::field(),
                SyncFlag::fieldSyncFlag(),
                SyncFlag::fieldOriginId(),
                SyncIdentity::fieldSourceStoreId(),
                SyncIdentity::fieldUuid(),
                SyncIdentity::fieldDocRole(),
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
                SyncMdb::field(),
                SyncFlag::fieldSyncFlag(),
                SyncFlag::fieldOriginId(),
                SyncIdentity::fieldSourceStoreId(),
                SyncIdentity::fieldDocRole(),
            ]
        );

        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query,
                conditions: ['name' => $filter['name']],
            );
        }

        if (!empty($filter[SyncIdentity::fieldUuid()])) {
            $query->andWhere([SyncIdentity::fieldUuid() => $filter[SyncIdentity::fieldUuid()]]);
        }

        $total = (int) (clone $query)->count();

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

    public function insert(SyncExample $entity): SyncExample
    {
        return $this->insertWithOutbox($entity, []);
    }

    public function insertWithOutbox(SyncExample $entity, array $queueRows): SyncExample
    {
        return $this->db->transaction(function () use ($entity, $queueRows) {
            $this->db->createCommand()
                ->insert(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(
                        entity: $entity,
                        lockVersion: LockVersion::create()->value()
                    )
                )
                ->execute();

            foreach ($queueRows as $row) {
                $this->db->createCommand()
                    ->insert(SyncOutboxFactory::TABLE, $row)
                    ->execute();
            }

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            $newEntity = SyncExample::reconstitute(
                id: $newId,
                name: $entity->getName(),
                status: $entity->getStatus(),
                detailInfo: $entity->getDetailInfo(),
                syncFlag: $entity->getSyncFlag(),
                syncIdentity: $entity->getSyncIdentity(),
                lockVersion: LockVersion::create(),
            );

            $this->syncMongoDB(
                entity: $newEntity,
                schemaClass: MdbSyncExampleSchema::class
            );

            return $newEntity;
        });
    }

    public function update(SyncExample $entity): SyncExample
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
                schemaClass: MdbSyncExampleSchema::class
            );

            return $entity;
        });
    }

    public function delete(SyncExample $entity): SyncExample
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
                schemaClass: MdbSyncExampleSchema::class
            );

            return $deletedEntity;
        });
    }

    public function restore(int $id): ?SyncExample
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

        $entity = $this->reconstitute($row);

        $entity->restore();

        return $this->update(
            entity: $entity,
        );
    }

    private function reconstitute(array $row): SyncExample
    {
        return SyncExample::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: isset($row[SyncMdb::field()]) ? SyncMdb::fromInt((int) $row[SyncMdb::field()]) : null,
            syncFlag: SyncFlag::fromArray($row),
            syncIdentity: SyncIdentity::fromArray($row),
            lockVersion: LockVersion::fromInt($row[LockVersion::field()]),
        );
    }

    private function mapEntityToTable(SyncExample $entity, int $lockVersion): array
    {
        return [
            'name'                        => $entity->getName(),
            'status'                      => $entity->getStatus()->value(),
            'detail_info'                 => $entity->getDetailInfo()->toArray(),
            SyncMdb::field()              => $entity->getSyncMdbValue(),
            SyncFlag::fieldSyncFlag()     => $entity->getSyncFlagValue(),
            SyncFlag::fieldOriginId()     => $entity->getOriginId(),
            SyncIdentity::fieldSourceStoreId() => $entity->getSourceStoreId(),
            SyncIdentity::fieldUuid()     => $entity->getUuid(),
            SyncIdentity::fieldDocRole()  => $entity->getDocRoleValue(),
            LockVersion::field()          => $lockVersion,
        ];
    }
}
