<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

use App\Domain\Shared\ValueObject\LockVersion;
use App\Shared\ValueObject\Message;
use App\Shared\Exception\OptimisticLockException;

trait OptimisticLock
{
    private LockVersion $lockVersion;
    private bool $optimisticLockEnabled = true;

    /**
     * Verifikasi lock version
     */
    public function verifyLockVersion(int $version): void
    {
        if (!$this->isOptimisticLockEnabled()) {
            return;
        }

        if (!$this->getLockVersion()->equals(LockVersion::fromInt($version))) {
            throw new OptimisticLockException(
                translate: new Message(
                    key: 'optimistic.lock.failed',
                    params: [
                        'resource' => defined('static::RESOURCE') ? static::RESOURCE : 'resource',
                        'version' => $version,
                    ]
                )
            );
        }
    }

    public function isOptimisticLockEnabled(): bool
    {
        return $this->optimisticLockEnabled;
    }

    /**
     * Digunakan oleh Repository/Factory untuk menyuntikkan status konfigurasi
     */
    public function setOptimisticLockEnabled(bool $enabled): void
    {
        $this->optimisticLockEnabled = $enabled;
    }

    public function getLockVersion(): LockVersion
    {
        return $this->lockVersion ??= LockVersion::fromInt(LockVersion::DEFAULT_VALUE);
    }

    public function upgradeLockVersion(): void
    {
        if ($this->isOptimisticLockEnabled()) {
            $this->lockVersion = $this->getLockVersion()->increment();
        }
    }

    public function withLockVersion(int $version): self
    {
        $clone = clone $this;
        $clone->lockVersion = LockVersion::fromInt($version);
        return $clone;
    }
}