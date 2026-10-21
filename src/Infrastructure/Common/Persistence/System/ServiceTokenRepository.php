<?php

declare(strict_types=1);

namespace App\Infrastructure\Common\Persistence\System;

// Domain Layer
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\System\ServiceToken\Entity\ServiceToken;
use App\Domain\System\ServiceToken\Repository\ServiceTokenRepositoryInterface;
// Infrastructure Layer
use App\Infrastructure\Core\Concerns\HasCoreFeatures;
use App\Infrastructure\Core\Concerns\ManagesPersistence;
use App\Infrastructure\Core\Security\CurrentUserAwareInterface;
// Shared Layer
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\Query\QueryConditionApplier;
// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Service Token Repository using Yiisoft/Db Query Builder.
 *
 * Pure query implementation using Yiisoft/Db for database operations.
 * The `token` column (SHA-256 hash) is only ever read for lookups —
 * it is never selected into list/view responses.
 */
final class ServiceTokenRepository implements ServiceTokenRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use ManagesPersistence;

    public const TABLE_NAME  = 'service_tokens';
    public const SEQUENCE_ID = 'service_tokens_id_seq';

    private const COLUMNS = [
        'id',
        'user_id',
        'username',
        'name',
        'abilities',
        'whitelist_host',
        'last_used_at',
        'expires_at',
        'status',
        'detail_info',
        'created_at',
    ];

    public function __construct(
        private ConnectionInterface $db,
        private QueryConditionApplier $queryConditionApplier,
    ) {
    }

    public function getResource(): string
    {
        return ServiceToken::RESOURCE;
    }

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select(self::COLUMNS)
            ->from(self::TABLE_NAME)
            ->where($this->scopeWhereNotDeleted());

        $filter = $criteria->filter;

        $this->queryConditionApplier->filterByExactMatch(
            query: $query,
            filters: $filter,
            allowedColumns: [
                'id',
                'user_id',
                'status',
            ]
        );

        $this->queryConditionApplier->filterByLike(
            query: $query,
            filters: $filter,
            allowedColumns: [
                'username',
                'name',
            ]
        );

        $total = (clone $query)->count();

        $query->orderBy($criteria->getOrderClause())
            ->limit($criteria->pageSize)
            ->offset($criteria->calculateOffset());

        $rows = \iterator_to_array($this->streamRows(
            query: $query,
            jsonKeys: ['abilities', 'whitelist_host']
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

    public function findById(int $id): ?ServiceToken
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->select(['token', ...self::COLUMNS])
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function findActiveByTokenHash(string $tokenHash): ?ServiceToken
    {
        /** @var array<string, mixed>|false $row */
        $row = (new Query($this->db))
            ->select(['token', ...self::COLUMNS])
            ->from(self::TABLE_NAME)
            ->where([
                'token'  => $tokenHash,
                'status' => RecordStatus::ACTIVE->value,
            ])
            ->one();

        if (!$row) {
            return null;
        }

        return $this->hydrate($row);
    }

    public function existsUsername(string $username, ?int $ignoreId = null): bool
    {
        $query = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['username' => $username]);

        // Unique across ALL rows — deleted included. No status scope.
        if ($ignoreId !== null) {
            $query->andWhere(['<>', 'id', $ignoreId]);
        }

        return $query->exists();
    }

    public function insert(ServiceToken $serviceToken): ServiceToken
    {
        return $this->db->transaction(function () use ($serviceToken) {
            $this->db->createCommand()
                ->insert(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(entity: $serviceToken)
                )
                ->execute();

            $newId = (int) $this->db->getLastInsertID(self::SEQUENCE_ID);

            return $this->findById($newId);
        });
    }

    public function update(ServiceToken $serviceToken): ServiceToken
    {
        return $this->db->transaction(function () use ($serviceToken) {
            $result = $this->db->createCommand()
                ->update(
                    self::TABLE_NAME,
                    $this->mapEntityToTable(entity: $serviceToken),
                    $this->buildSimpleCondition(entity: $serviceToken)
                )
                ->execute();

            if ($result === 0) {
                $this->handlePersistenceFailure(entity: $serviceToken, checkLock: false);
            }

            return $serviceToken;
        });
    }

    public function touchLastUsed(int $id, string $lastUsedAt): void
    {
        $this->db->createCommand()
            ->update(
                self::TABLE_NAME,
                ['last_used_at' => $lastUsedAt],
                ['id' => $id]
            )
            ->execute();
    }

    private function hydrate(array $row): ServiceToken
    {
        return ServiceToken::reconstitute(
            id: (int) $row['id'],
            userId: (int) $row['user_id'],
            username: (string) $row['username'],
            name: (string) $row['name'],
            tokenHash: (string) $row['token'],
            abilities: $this->castToArray($row['abilities'] ?? null),
            whitelistHost: $this->castToArray($row['whitelist_host'] ?? null),
            lastUsedAt: $row['last_used_at'] !== null ? (string) $row['last_used_at'] : null,
            expiresAt: $row['expires_at'] !== null ? (string) $row['expires_at'] : null,
            status: ResourceStatus::from((int) $row['status']),
            detailInfo: DetailInfo::fromJson($row['detail_info'] ?? null),
            createdAt: $row['created_at'] !== null ? (string) $row['created_at'] : null,
        );
    }

    private function mapEntityToTable(ServiceToken $entity): array
    {
        return [
            'user_id'        => $entity->getUserId(),
            'username'       => $entity->getUsername(),
            'name'           => $entity->getName(),
            'token'          => $entity->getTokenHash(),
            'abilities'      => \json_encode($entity->getAbilities()),
            'whitelist_host' => $entity->getWhitelistHost(),
            'last_used_at'   => $entity->getLastUsedAt(),
            'expires_at'     => $entity->getExpiresAt(),
            'status'         => $entity->getStatus()->value(),
            'detail_info'    => $entity->getDetailInfo()->toArray(),
            'created_at'     => $entity->getCreatedAt(),
        ];
    }
}
