<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contract;

interface ActorInterface
{
    public function getId(): int;
    public function getUsername(): string;
    public function getDept(): string;
    public function hasRole(string $app, string $role): bool;
    public function isAdmin(string $app): bool;
    public function isSuperAdmin(string $app): bool;
}