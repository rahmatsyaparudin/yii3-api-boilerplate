<?php

declare(strict_types=1);

namespace App\Infrastructure\Concerns;

use App\Shared\Exception\OptimisticLockException;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

trait ManagesPersistence
{
    private ?bool $optimisticLockEnabled = null;

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
        if (method_exists($entity, 'isOptimisticLockEnabled') && $entity->isOptimisticLockEnabled()) {
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
        if ($checkLock && method_exists($entity, 'isOptimisticLockEnabled') && $entity->isOptimisticLockEnabled()) {
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
     * Set optimistic lock enabled status (for dependency injection)
     */
    public function setOptimisticLockEnabled(bool $enabled): void
    {
        $this->optimisticLockEnabled = $enabled;
    }

    /**
     * Get optimistic lock enabled status
     */
    public function isOptimisticLockEnabled(): bool
    {
        return $this->optimisticLockEnabled ?? true;
    }
}