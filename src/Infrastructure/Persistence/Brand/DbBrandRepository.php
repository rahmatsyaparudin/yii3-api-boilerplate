<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Brand;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\ValueObject\BrandId;
use App\Domain\Brand\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Shared\Request\RawParams;
use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;
use App\Shared\Db\QueryFilterHelper;
use App\Shared\Exception\NotFoundException;
use App\Shared\Helper\DetailInfoHelper;
use App\Shared\Json\JsonFieldNormalizer;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Infrastructure Layer - Brand Repository Implementation
 * 
 * This class implements the BrandRepositoryInterface using Yii3 DB
 * and follows DDD best practices for repository pattern.
 */
final readonly class DbBrandRepository implements BrandRepositoryInterface
{
    private const TABLE = 'brand';
    private const RESOURCE = 'Brand';
    private const ALLOWED_SORT = [
        'id'     => 'id',
        'name'   => 'name',
        'status' => 'status',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private JsonFieldNormalizer $jsonFieldNormalizer,
        private DetailInfoHelper $detailInfoHelper,
    ) {
    }

    /**
     * {@inheritdoc}
     */
    public function list(?RawParams $params = null, ?PaginationParams $pagination = null, ?SortParams $sort = null): array
    {
        $pagination ??= new PaginationParams();
        $params ??= new RawParams();
        $sort ??= new SortParams();
        
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'status',
                'detail_info',
                'sync_mdb',
            ])
            ->from(self::TABLE)
            ->orderBy($this->buildOrderBy($sort->by, $sort->dir))
            ->limit($pagination->page_size)
            ->offset($pagination->getOffset());

        $this->applyFilters(
            query: $query, 
            filters: $params,
        );

        $rows = $query->all();

        return $this->jsonFieldNormalizer->normalize(
            data: $rows, 
            jsonFields: ['detail_info'],
        );
    }

    /**
     * {@inheritdoc}
     */
    public function count(?RawParams $params = null): int
    {
        $params ??= new RawParams();
        
        $query = (new Query($this->db))
            ->from(self::TABLE);

        $this->applyFilters(
            query: $query, 
            filters: $params,
        );

        return (int) $query->count();
    }

    

    /**
     * {@inheritdoc}
     */
    public function findById(int $id): ?array
    {
        $row = (new Query($this->db))
            ->select([
                'id',
                'name',
                'status',
                'detail_info',
                'sync_mdb',
            ])
            ->from(self::TABLE)
            ->where(['id' => $id])
            ->one();

        if ($row === false || $row === null) {
            return null;
        }

        return $this->jsonFieldNormalizer->normalize(
            data: $row, 
            jsonFields: ['detail_info']
        );
    }

    /**
     * {@inheritdoc}
     */
    public function findByName(string $name): ?array
    {
        $row = (new Query($this->db))
            ->select(['id', 'name', 'status', 'detail_info', 'sync_mdb'])
            ->from(self::TABLE)
            ->where(['LOWER(name)' => strtolower($name)])
            ->one();

        return $row ? $this->jsonFieldNormalizer->normalize(
            data: $row, 
            jsonFields: ['detail_info']
        ) : null;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $name, int $status, array $detailInfo = [], ?int $syncMdb = null): array
    {
        // Prepare detail_info with audit trail
        $finalDetailInfo = $this->prepareDetailInfo([], $detailInfo);

        $this->db->createCommand()
            ->insert(self::TABLE, [
                'name'        => $name,
                'status'      => $status,
                'detail_info' => $finalDetailInfo,
                'sync_mdb'    => $syncMdb,
            ])
            ->execute();

        $id = (int) $this->db->getLastInsertID('brand_id_seq');

        $brand = $this->findById(
            id: $id
        );

        if ($brand === null) {
            throw new NotFoundException(
                translate: [
                    'key' => 'db.not_found',
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id,
                    ]
                ]
            );
        }

        return $brand;
    }

    /**
     * {@inheritdoc}
     */
    public function update(int $id, ?string $name = null, ?int $status = null, array $detailInfo = [], ?int $syncMdb = null): array
    {
        // Build update data
        $updateData = array_filter([
            'name'        => $name,
            'status'      => $status,
            'sync_mdb'    => $syncMdb,
        ], fn($value) => $value !== null);
        
        // Get existing data
        $existingBrand = $this->findById(id: $id);

        if ($existingBrand === null) {
            throw new NotFoundException(
                translate: [
                    'key' => 'db.not_found', 
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id
                    ]
                ]
            );
        }

        // Prepare detail_info with audit trail
        $finalDetailInfo = $this->prepareDetailInfo($existingBrand['detail_info'], $detailInfo);

        // Early return if no changes
        if (empty($updateData)) {
            return $existingBrand;
        }

        $updateData['detail_info'] = $finalDetailInfo;

        // Execute update
        $affected = $this->db->createCommand()
            ->update(self::TABLE, $updateData, ['id' => $id])
            ->execute();

        if ($affected === 0) {
            throw new NotFoundException(
                translate: [
                    'key' => 'db.not_found', 
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id
                    ]
                ]
            );
        }

        // Return updated data
        return array_merge($existingBrand, $updateData);
    }

    /**
     * {@inheritdoc}
     */
    public function delete(int $id): void
    {
        $affected = $this->db->createCommand()
            ->delete(self::TABLE, ['id' => $id])
            ->execute();

        if ($affected === 0) {
            throw new NotFoundException(
                translate: [
                    'key' => 'db.not_found',
                    'params' => [
                        'resource' => self::RESOURCE,
                        'field' => 'ID',
                        'value' => $id,
                    ]
                ]
            );
        }
    }

    /**
     * Apply filters to the query
     */
    private function applyFilters(Query $query, RawParams $filters): void
    {
        // Apply LIKE filter for name
        QueryFilterHelper::andFilterLike(
            db: $this->db, 
            query: $query, 
            conditions: [
                'name' => $filters->get('name') ? "%{$filters->get('name')}%" : null,
            ]
        );

        // Apply exact filters
        QueryFilterHelper::andFilterWhere(
            query: $query, 
            conditions: [
                'id'       => $filters->get('id'),
                'status'   => $filters->get('status'),
                'sync_mdb' => $filters->get('sync_mdb'),
            ]
        );
    }

    /**
     * Prepare detail_info with audit trail
     */
    private function prepareDetailInfo(array $existingDetailInfo, array $newDetailInfo): array
    {
        // Start with existing data
        $finalDetailInfo = $existingDetailInfo;
        
        // Merge new data if provided
        if (!empty($newDetailInfo)) {
            $finalDetailInfo = array_merge($finalDetailInfo, $newDetailInfo);
        }
        
        // Update audit trail based on whether this is create or update
        if (empty($existingDetailInfo)) {
            // Create case
            $changeLog = $this->detailInfoHelper->createChangeLog();
            $finalDetailInfo['change_log'] = $changeLog['change_log'];
        } else {
            // Update case
            $changeLog = $this->detailInfoHelper->updateChangeLog($finalDetailInfo);
            $finalDetailInfo['change_log'] = $changeLog['change_log'];
        }
        
        return $finalDetailInfo;
    }

    /**
     * Build ORDER BY clause
     */
    private function buildOrderBy(?string $sortBy, string $sortDir): array
    {
        $column    = self::ALLOWED_SORT[$sortBy ?? 'id'] ?? 'id';
        $direction = \strtolower($sortDir) === 'desc' ? SORT_DESC : SORT_ASC;

        return [$column => $direction];
    }
}
