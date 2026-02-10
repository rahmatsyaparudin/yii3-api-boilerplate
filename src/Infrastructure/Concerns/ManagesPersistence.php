<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

use App\Shared\Exception\OptimisticLockException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;
use App\Shared\ValueObject\LockVersionConfig;
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\LockVersion;
use Yiisoft\Db\Query\Query;


trait ManagesPersistence
{
    protected LockVersionConfig $lockVersionConfig;
    private LockVersion $lockVersion;

    public function getResource(): string
    {
        return str_ireplace('Repository', '', (new \ReflectionClass($this))->getShortName());
    }

    private function streamRows(Query $query, array $jsonKeys = []): iterable
    {
        foreach ($query->each(100, $this->db) as $row) {
            /** @var array<string, mixed> $row */

            // 1. Domain Logic encapsulated in Value Object
            $row['detail_info'] = DetailInfo::fromJson($row['detail_info'] ?? '')->toArray();

            // 2. Generic Logic encapsulated in a helper or VO
            foreach ($jsonKeys as $key) {
                $row[$key] = $this->castToArray($row[$key] ?? null);
            }

            yield $row;
        }
    }

    /**
     * Encapsulates the messy "double-encoded" JSON logic
     */
    private function castToArray(mixed $value): array
    {
        if (!is_string($value)) {
            return is_array($value) ? $value : [];
        }

        $data = json_decode($value, true);

        // Handle the double-encoded case: "{\"key\": \"val\"}"
        if (is_string($data)) {
            $data = json_decode($data, true);
        }

        return is_array($data) ? $data : [];
    }

    protected function isOptimisticLockEnabled(object $entity): bool
    {
        $className = (new \ReflectionClass($entity))->getShortName();
        return $this->lockVersionConfig->isEnabledForRepository($className);
    }

    protected function hasOptimisticLockEnabled(string $className): bool
    {
        return $this->lockVersionConfig->isEnabledForRepository($className);
    }

    public function setLockVersionConfig(LockVersionConfig $lockVersionConfig): void
    {
        $this->lockVersionConfig = $lockVersionConfig;
    }

    public function verifyLockVersion(object $entity, ?int $version): void
    {
        if ($version === null || !$this->hasOptimisticLockEnabled($this->getResource())) {
            return;
        }

        if (!$entity->getLockVersion()->equals(LockVersion::fromInt($version))) {
            throw new OptimisticLockException(
                translate: Message::create(
                    key: 'optimistic.lock.failed',
                    params: [
                        'resource' => defined('static::RESOURCE') ? static::RESOURCE : 'resource',
                        'version' => $version,
                    ]
                )
            );
        }
    }

    public function upgradeEntityLockVersion(object $entity): LockVersion
    {
        if ($this->hasOptimisticLockEnabled($this->getResource())) {
            $entity->upgradeLockVersion();
        }

        return $entity->getLockVersion();
    }

    private function handlePersistenceFailure(object $entity, bool $checkLock = true): void
    {
        $resourceName = defined(get_class($entity) . '::RESOURCE') ? $entity::RESOURCE : 'resource';

        // Jika kita ingin mengecek lock dan lock aktif
        if ($checkLock && $this->isOptimisticLockEnabled($entity)) {
            throw new OptimisticLockException(
                translate: Message::create(
                    key: 'optimistic.lock.failed',
                    params: ['resource' => $resourceName]
                )
            );
        }

        throw new NotFoundException(
            translate: Message::create(
                key: 'resource.not_found',
                params: [
                    'resource' => $resourceName,
                    'id' => $entity->getId()
                ]
            )
        );
    }

    private function buildSimpleCondition(object $entity): array
    {
        return ['id' => $entity->getId()];
    }

    private function buildLockCondition(object $entity, int $currentLockVersion): array
    {
        $condition = ['id' => $entity->getId()];
        if ($this->isOptimisticLockEnabled($entity)) {
            $condition[LockVersion::field()] = $currentLockVersion;
        }
        return $condition;
    } 
}