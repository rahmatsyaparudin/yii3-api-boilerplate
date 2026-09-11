<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Security;

// Infrastructure Layer

// Domain Layer
use App\Domain\Shared\Security\AuthorizerInterface;

final class RbacAuthorizer implements AuthorizerInterface
{
    public function __construct(
        private Actor $actor,
        private PermissionChecker $checker
    ) {
    }

    public function can(string $permission): bool
    {
        return $this->checker->can($this->actor, $permission);
    }
}
