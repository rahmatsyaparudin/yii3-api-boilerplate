<?php

declare(strict_types=1);

namespace App\Infrastructure\Security\Rule;

// Infrastructure Layer
use App\Infrastructure\Security\Actor;

// Vendor Layer
use Yiisoft\Access\Context\ContextInterface;
use Yiisoft\Access\Rule\RuleInterface;

final class PermissionMapRule implements RuleInterface
{
    public function __construct(
        private array $map
    ) {
    }

    public function execute(ContextInterface $context): bool
    {
        $actor      = $context->getUser();
        $permission = $context->getPermission();

        if (!$actor instanceof Actor || !$permission) {
            return false;
        }

        $rule = $this->map[$permission] ?? null;

        return $rule ? (bool) $rule($actor) : false;
    }
}
