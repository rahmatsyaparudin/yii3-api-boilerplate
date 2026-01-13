<?php

declare(strict_types=1);

namespace App\Domain\Common\Audit;

final class ChangeLog
{
    public function __construct(
        public readonly \DateTimeImmutable $createdAt,
        public readonly string $createdBy,
        public readonly ?\DateTimeImmutable $updatedAt = null,
        public readonly ?string $updatedBy = null,
        public readonly ?\DateTimeImmutable $deletedAt = null,
        public readonly ?string $deletedBy = null,
    ) {
    }

    public static function create(Actor $actor, \DateTimeImmutable $now): self
    {
        return new self(
            createdAt: $now,
            createdBy: $actor->username,
        );
    }

    public function markUpdated(Actor $actor, \DateTimeImmutable $now): self
    {
        return new self(
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            updatedAt: $now,
            updatedBy: $actor->username,
            deletedAt: $this->deletedAt,
            deletedBy: $this->deletedBy,
        );
    }

    public function markDeleted(Actor $actor, \DateTimeImmutable $now): self
    {
        return new self(
            createdAt: $this->createdAt,
            createdBy: $this->createdBy,
            updatedAt: $this->updatedAt,
            updatedBy: $this->updatedBy,
            deletedAt: $now,
            deletedBy: $actor->username,
        );
    }
}
