<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

use App\Shared\Exception\OptimisticLockException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;
use App\Shared\ValueObject\LockVersionConfig;

trait ManagesPersistence
{
    protected LockVersionConfig $lockVersionConfig;

    /**
     * Set LockVersionConfig (for dependency injection)
     */
    public function setLockVersionConfig(LockVersionConfig $lockVersionConfig): void
    {
        $this->lockVersionConfig = $lockVersionConfig;
    }

    /**
     * Kondisi standar hanya berdasarkan ID (tanpa Lock)
     */
    private function buildSimpleCondition(object $entity): array
    {
        return ['id' => $entity->getId()];
    }

    /**
     * Kondisi dengan Lock (untuk Update)
     */
    private function buildLockCondition(object $entity, int $currentLockVersion): array
    {
        $condition = ['id' => $entity->getId()];
        if ($this->isOptimisticLockEnabled($entity)) {
            $condition['lock_version'] = $currentLockVersion;
        }
        return $condition;
    }

    /**
     * Menangani kegagalan (tetap bisa digunakan untuk Delete maupun Update)
     */
    private function handlePersistenceFailure(object $entity, bool $checkLock = true): void
    {
        $resourceName = defined(get_class($entity) . '::RESOURCE') ? $entity::RESOURCE : 'resource';

        // Jika kita ingin mengecek lock dan lock aktif
        if ($checkLock && $this->isOptimisticLockEnabled($entity)) {
            throw new OptimisticLockException(
                translate: new Message(
                    key: 'optimistic.lock.failed',
                    params: ['resource' => $resourceName]
                )
            );
        }

        throw new NotFoundException(
            translate: new Message(
                key: 'resource.not_found',
                params: [
                    'resource' => $resourceName,
                    'id' => $entity->getId()
                ]
            )
        );
    }

    /**
     * Check if optimistic locking is enabled for this entity
     * Uses centralized LockVersionConfig
     */
    protected function isOptimisticLockEnabled(object $entity): bool
    {
        $className = (new \ReflectionClass($entity))->getShortName();
        return $this->lockVersionConfig->isEnabledForRepository($className);
    }
}