<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

// Domain Layer
use App\Domain\Shared\ValueObject\LockVersion;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\OptimisticLockException;

trait OptimisticLock
{
    private LockVersion $lockVersion;

    public function verifyLockVersion(int $version): void
    {
        if (!$this->isOptimisticLockEnabled()) {
            return; // Skip verification if disabled
        }

        if (!$this->getLockVersion()->equals(LockVersion::fromInt($version))) {
            throw new OptimisticLockException(
                translate: new Message(
                    key: 'optimistic.lock.failed',
                    params: [
                        'resource' => $this->getResource(),
                        'version' => $version,
                    ]
                )
            );
        }
    }

    public function getLockVersion(): LockVersion
    {
        return $this->lockVersion;
    }

    /**
     * Digunakan oleh Repository saat memulihkan data dari PostgreSQL
     */
    public function withLockVersion(int $version): self
    {
        $clone = clone $this;
        $clone->lockVersion = LockVersion::fromInt($version);
        return $clone;
    }

    public function upgradeLockVersion(): void
    {
        if ($this->isOptimisticLockEnabled()) {
            $this->lockVersion = $this->lockVersion->increment();
        }
    }

    /**
     * Initialize lock version for new entities
     */
    protected function initializeLockVersion(): void
    {
        // Property is already initialized in constructor
    }

    /**
     * Check if optimistic locking is enabled for this entity
     * Simple global check only - no entity override
     */
    protected function isOptimisticLockEnabled(): bool
    {
        try {
            // Try to get from container (if available)
            $container = \Yiisoft\Yii\Yii::getContainer();
            $params = $container->get('params');
            return $params['app/optimisticLock']['enabled'] ?? true;
        } catch (\Throwable $e) {
            // Fallback to environment variable or default
            return filter_var($_ENV['app.optimistic_lock.enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);
        }
    }

    /**
     * Get default lock version from configuration
     * Uses LockVersion::DEFAULT_VALUE
     */
    protected function getDefaultLockVersion(): int
    {
        return LockVersion::DEFAULT_VALUE;
    }
}