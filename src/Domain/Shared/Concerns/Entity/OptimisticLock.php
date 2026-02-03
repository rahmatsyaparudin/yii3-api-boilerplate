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
    private bool $optimisticLockEnabled = true;

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
     * Set via dependency injection
     */
    public function isOptimisticLockEnabled(): bool
    {
        return $this->optimisticLockEnabled;
    }

    /**
     * Set optimistic lock enabled status (for dependency injection)
     */
    public function setOptimisticLockEnabled(bool $enabled): void
    {
        $this->optimisticLockEnabled = $enabled;
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