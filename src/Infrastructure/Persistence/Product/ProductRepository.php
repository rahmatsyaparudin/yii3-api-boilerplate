<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Product;

// Domain Layer
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\ValueObject\DetailInfo;

// Infrastructure Layer
use App\Infrastructure\Concerns\HasCoreFeatures;
use App\Infrastructure\Database\MongoDB\MongoDBService;
use App\Infrastructure\Persistence\Product\MdbProductSchema;
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
 * Product Repository using Yiisoft/Db Query Builder
 * 
 * Pure query implementation using Yiisoft/Db for database operations
 */
final class ProductRepository implements ProductRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;

    private const SEQUENCE_ID = 'product_id_seq';
    private const TABLE = 'product';
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

    public function findById(int $id): ?Product
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

        return Product::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        );
    }

    public function restore(int $id): ?Product
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
        $entity = Product::reconstitute(
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

    public function findByName(string $name): ?Product
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

        return Product::reconstitute(
            id: (int) $row['id'],
            name: $row['name'],
            status: Status::from((int)$row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info']),
            syncMdb: $row['sync_mdb'] ?? null,
            lockVersion: (int) $row['lock_version']
        );
    }

    private function syncToMongo(Product $product): void
    {
        if ($this->collection !== null) {
            $this->collection->updateOne(
                ['id' => $product->getId()],
                ['$set' => MdbProductSchema::toArray($product)],
                ['upsert' => true]
            );
        }
    }

    public function delete(Product $product): Product
    {
        return $this->db->transaction(function() use ($product) {
            // 1. Update di PostgreSQL
            $this->db->createCommand()
                ->update(
                    self::TABLE,
                    $this->getDeletedState(), 
                    [
                        'id' => $product->getId(),
                    ]
                )
                ->execute();

            $deletedProduct = $product->markAsDeleted();

            $this->syncToMongo($deletedProduct);

            return $deletedProduct;
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

    public function insert(Product $product): Product 
    {
        return $this->db->transaction(function() use ($product) {
            // 1. Insert ke PostgreSQL
            $this->db->createCommand()
                ->insert(self::TABLE, [
                    'name' => $product->getName(),
                    'status' => $product->getStatus()->value(),
                    'detail_info' => $product->getDetailInfo()->toArray(),
                    'sync_mdb' => $product->getSyncMdb(),
                    'lock_version' => 1, 
                ])
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            // 2. Reconstitute dengan ID baru
            $newEntity = Product::reconstitute(
                id: $newId,
                name: $product->getName(),
                status: $product->getStatus(),
                detailInfo: $product->getDetailInfo(),
                syncMdb: $product->getSyncMdb(),
                lockVersion: 1
            );

            // 3. Sync ke MongoDB (jika diperlukan)
            $this->syncToMongo($newEntity);

            return $newEntity;
        });
    }

    public function update(Product $product): Product
    {
        return $this->db->transaction(function() use ($product) {
            // Get current and new lock versions
            $currentLockVersion = $product->getLockVersion();
            $newLockVersion = $currentLockVersion->increment();
            
            $result = $this->db->createCommand()
                ->update(self::TABLE, [
                    'name' => $product->getName(),
                    'status' => $product->getStatus()->value(),
                    'detail_info' => $product->getDetailInfo()->toArray(),
                    'sync_mdb' => $product->getSyncMdb(),
                    'lock_version' => $newLockVersion->value(),
                ], [
                    'id' => $product->getId(),
                    'lock_version' => $currentLockVersion->value()
                ])
                ->execute();
            
            // Check if update was successful (optimistic locking)
            if ($result === 0) {
                throw new OptimisticLockException(
                    translate: new Message(
                        key: 'optimistic.lock.failed',
                        params: [
                            'resource' => Product::RESOURCE,
                        ]
                    )
                );
            }
            
            // Update the entity's lock version
            $product->upgradeLockVersion();
            
            // Sync updated entity to MongoDB
            $this->syncToMongo($product);
            
            return $product;
        });
    }
}
