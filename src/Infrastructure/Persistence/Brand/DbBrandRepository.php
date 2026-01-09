<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Brand;

use App\Domain\Brand\BrandRepositoryInterface;
use App\Shared\Json\JsonFieldNormalizer;
use App\Shared\Exception\NotFoundException;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;
use App\Shared\Db\QueryFilterHelper;

final readonly class DbBrandRepository implements BrandRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private JsonFieldNormalizer $jsonFieldNormalizer,
    ) {}

    public function list(int $limit, int $offset, array $filters = [], ?string $sortBy = null, string $sortDir = 'asc'): array
    {
        $query = (new Query($this->db))
            ->select([
                'id',
                'name',
                'status',
                'detail_info',
                'sync_mdb',
            ])
            ->from('brand')
            ->orderBy($this->buildOrderBy($sortBy, $sortDir))
            ->limit($limit)
            ->offset($offset);

        $query = QueryFilterHelper::andFilterLike($this->db, $query, [
            'name' => $filters['name'] ?? null,
        ]);

        $query = QueryFilterHelper::andFilterWhere($query, [
            'id' => $filters['id'] ?? null,
            'status' => $filters['status'] ?? null,
            'sync_mdb' => $filters['sync_mdb'] ?? null,
        ]);

        $rows = $query->all();

        return $this->jsonFieldNormalizer->normalizeRows($rows, ['detail_info']);
    }

    public function count(array $filters = []): int
    {
        $query = (new Query($this->db))
            ->from('brand');

        $query = QueryFilterHelper::andFilterLike($this->db, $query, [
            'name' => $filters['name'] ?? null,
        ]);

        $query = QueryFilterHelper::andFilterWhere($query, [
            'id' => isset($filters['id']) && $filters['id'] !== '' ? (int) $filters['id'] : null,
            'status' => isset($filters['status']) && $filters['status'] !== '' ? (int) $filters['status'] : null,
            'sync_mdb' => isset($filters['sync_mdb']) && $filters['sync_mdb'] !== '' ? (int) $filters['sync_mdb'] : null,
        ]);

        return (int) $query->count();
    }

    private function buildOrderBy(?string $sortBy, string $sortDir): array
    {
        $allowedSort = [
            'id' => 'id',
            'name' => 'name',
            'status' => 'status',
        ];

        $column = $allowedSort[$sortBy ?? 'id'] ?? 'id';
        $direction = strtolower($sortDir) === 'desc' ? SORT_DESC : SORT_ASC;

        return [$column => $direction];
    }

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
            ->from('brand')
            ->where(['id' => $id])
            ->one();

        if ($row === false || $row === null) {
            return null;
        }

        return $this->jsonFieldNormalizer->normalizeRow($row, ['detail_info']);
    }

    public function create(string $name, int $status, array $detailInfo = [], int|null $syncMdb = null): array
    {
        $this->db->createCommand()
            ->insert('brand', [
                'name' => $name,
                'status' => $status,
                'detail_info' => $detailInfo,
                'sync_mdb' => $syncMdb,
            ])
            ->execute();

        $id = (int) $this->db->getLastInsertID('brand_id_seq');

        $brand = $this->findById($id);
        if ($brand === null) {
            throw new NotFoundException('Brand not found');
        }

        return $brand;
    }

    public function update(int $id, string $name, int $status, array $detailInfo = [], int|null $syncMdb = null): array
    {
        $affected = $this->db->createCommand()
            ->update('brand', [
                'name' => $name,
                'status' => $status,
                'detail_info' => $detailInfo,
                'sync_mdb' => $syncMdb,
            ], ['id' => $id])
            ->execute();

        if ($affected === 0) {
            throw new NotFoundException('Brand not found');
        }

        $brand = $this->findById($id);
        if ($brand === null) {
            throw new NotFoundException('Brand not found');
        }

        return $brand;
    }

    public function delete(int $id): void
    {
        $affected = $this->db->createCommand()
            ->delete('brand', ['id' => $id])
            ->execute();

        if ($affected === 0) {
            throw new NotFoundException('Brand not found');
        }
    }
}
