<?php

declare(strict_types=1);

namespace App\Domain\Shared\Security;

interface AuthorizerInterface
{
    public function can(string $permission): bool;
}