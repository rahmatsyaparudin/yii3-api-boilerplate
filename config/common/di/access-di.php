<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Access\AccessChecker;
use Yiisoft\Access\Assignment\Assignment;
use Yiisoft\Access\Permission\Permission;
use Yiisoft\Access\Rule\RuleFactory;

// Shared Layer
use App\Infrastructure\Security\Rule\PermissionMapRule;

$permissionMap = require __DIR__ . '/../access.php';

return [
    Assignment::class => static fn () => new Assignment([
        'authenticated' => \array_map(
            static fn (string $permission) => new Permission($permission, 'permission.map'),
            \array_keys($permissionMap)
        ),
    ]),

    RuleFactory::class => static fn () => new RuleFactory([
        'permission.map' => static fn () => new PermissionMapRule($permissionMap),
    ]),

    AccessChecker::class => static fn ($c) => new AccessChecker(
        $c->get(Assignment::class),
        $c->get(RuleFactory::class),
    ),
];
