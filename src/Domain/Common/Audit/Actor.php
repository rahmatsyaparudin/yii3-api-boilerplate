<?php

declare(strict_types=1);

namespace App\Domain\Common\Audit;

final class Actor
{
    public function __construct(
        public int $id = 0,
        public string $username = 'system',
        public string $dept = '',
        public array $roles = []
    ) {
    }

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
