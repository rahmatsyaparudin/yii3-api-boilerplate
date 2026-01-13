<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

final class Actor
{
    public function __construct(
        public readonly int $id,
        private array $roles
    ) {
    }

    public function isAdmin(string $app): bool
    {
        return \in_array("$app:admin", $this->roles, true);
    }
}
