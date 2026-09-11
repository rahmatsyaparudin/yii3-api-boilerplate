<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Security\Actor;

/** @var array $params */
$params  = require __DIR__ . '/params.php';
$appCode = $params['app/config']['code'] ?? 'default';

// Role helpers. These closures decide whether the actor (current user) has a
// specific role or status for the configured app code. They are reused below
// when defining permission rules.
//
// $isKasir      - actor has the "kasir" role.
// $isSpv        - actor has the "spv" (supervisor) role.
// $isAdmin      - actor is an admin.
// $isSuperAdmin - actor is a super admin, the highest level.
//
// These names are examples. Adjust them to match your project's roles.
$isKasir      = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isSpv        = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'spv');
$isAdmin      = static fn (Actor $actor): bool => $actor->isAdmin($appCode);
$isSuperAdmin = static fn (Actor $actor): bool => $actor->isSuperAdmin($appCode);

// God mode - grants access to everything (used for GodMode). To disable, set allow_god_mode to false in params.
// This closure checks if god mode is enabled in the application configuration AND if the actor is a super admin.
// When both conditions are true, the actor bypasses all permission checks and has unrestricted access.
// WARNING: This should only be enabled in development/testing environments, never in production.
$allowGodMode = static fn (Actor $actor): bool => ($params['app/config']['allow_god_mode'] ?? false) && $isSuperAdmin($actor);

return [
    // Global wildcard - grants access to everything (used for GodMode). To disable, set allow_god_mode to false in params.
    '*' => $allowGodMode,

    'example.index' => static fn (Actor $actor): bool => true,
    'example.data'  => [
        $isSuperAdmin,
        $isKasir,
    ],
    'example.view'    => $isKasir,
    'example.create'  => $isKasir,
    'example.update'  => $isKasir,
    'example.delete'  => $isKasir,
    'example.restore' => $isSuperAdmin,

    // AnotherExample Access Rules
    'another-example.index' => static fn (Actor $actor): bool => true,
    'another-example.data'  => [
        $isSuperAdmin,
        $isKasir,
    ],
    'another-example.view'    => $isKasir,
    'another-example.create'  => $isKasir,
    'another-example.update'  => $isKasir,
    'another-example.delete'  => $isKasir,
    'another-example.restore' => $isSuperAdmin,
];
