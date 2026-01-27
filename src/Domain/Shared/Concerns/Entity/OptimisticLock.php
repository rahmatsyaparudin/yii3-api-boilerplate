<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

use App\Domain\Shared\ValueObject\LockVersion;
use App\Shared\ValueObject\Message;
use App\Shared\Exception\OptimisticLockException;

trait OptimisticLock
{
    private LockVersion $lockVersion;

    public function verifyLockVersion(int $version): void
    {
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
        return $this->lockVersion ?? LockVersion::create();
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

    public function upgradeVersion(): void
    {
        $this->lockVersion = $this->lockVersion->increment();
    }

    /**
     * Initialize lock version for new entities
     */
    protected function initializeLockVersion(): void
    {
        if (!isset($this->lockVersion)) {
            $this->lockVersion = LockVersion::create();
        }
    }
}