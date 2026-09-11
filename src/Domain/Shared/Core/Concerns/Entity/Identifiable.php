<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Concerns\Entity;

// Domain Layer
use App\Domain\Shared\Core\ValueObject\LockVersion;
use App\Domain\Shared\Core\ValueObject\SyncMdb;
// Shared Layer
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

trait Identifiable
{
    protected string $resource;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getResource(): string
    {
        return static::RESOURCE;
    }

    public function updateName(?string $newName): void
    {
        if ($newName === null) {
            return;
        }

        $newName = \trim($newName);
        if ($this->name === $newName) {
            return;
        }

        $this->ensureHasName($newName);
        $this->name = $newName;
    }

    protected function ensureHasName(string $name): void
    {
        if (empty($name)) {
            throw new BadRequestException(
                translate: new Message(
                    domain: 'validation', 
                    key: 'name_required', 
                    params: [
                        'resource' => $this->getResource()
                    ]
                )
            );
        }
    }

    public function getSyncMdb(): ?SyncMdb
    {
        return $this->syncMdb;
    }

    public function getSyncMdbValue(): ?int
    {
        return $this->syncMdb?->value();
    }

    public function setSyncMdb(SyncMdb $syncMdb): self
    {
        $this->syncMdb = $syncMdb;

        return $this;
    }

    public function updateSyncMdb(?SyncMdb $syncMdb): void
    {
        $this->syncMdb = $syncMdb;
    }

    public function getLockVersion(): LockVersion
    {
        return $this->lockVersion ??= LockVersion::fromInt(LockVersion::DEFAULT_VALUE);
    }

    public function upgradeLockVersion(): void
    {
        $this->lockVersion = $this->getLockVersion()->increment();
    }
}
