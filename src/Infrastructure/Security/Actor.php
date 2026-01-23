<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Shared\Contract\ActorInterface;

final class Actor implements ActorInterface
{
    public function __construct(
        public readonly int $id = 0,
        public string $username = 'system',
        public string $dept = '',
        private array $roles = []
    ) {
    }

    public function getId(): int { return $this->id; }
    public function getUsername(): string { return $this->username; }
    public function getDept(): string { return $this->dept; }

    public function hasRole(string $app, string $role): bool
    {
        return \in_array($role, $this->roles[$app]['roles'] ?? [], true);
    }

    public function isAdmin(string $app): bool
    {
        return (bool) ($this->roles[$app]['admin'] ?? false);
    }

    public function isSuperAdmin(string $app): bool
    {
        return (bool) ($this->roles[$app]['superadmin'] ?? false);
    }
}
