<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Security\Actor;

/** @var array $params */
$params  = require __DIR__ . '/params.php';
$appCode = $params['app/config']['code'] ?? 'default';

$isKasir = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isSpv = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'spv');
$isAdmin = static fn (Actor $actor): bool => $actor->isAdmin($appCode);
$isSuperAdmin = static fn (Actor $actor): bool => $actor->isSuperAdmin($appCode);
$allowGodMode = static fn (Actor $actor): bool => ($params['app/config']['allow_god_mode'] ?? false) && $isSuperAdmin($actor);

return [
    // Global wildcard - grants access to everything (used for GodMode). To disable, set allow_god_mode to false in params.
    '*' => $allowGodMode,

    'example.index'  => static fn (Actor $actor): bool => true,
    'example.data'   => [
        $isSuperAdmin,
        $isKasir,
    ],
    'example.view'   => $isKasir,
    'example.create' => $isKasir,
    'example.update' => $isKasir,
    'example.delete' => $isKasir,
    'example.restore' => $isSuperAdmin,

    // AnotherExample Access Rules
    'another-example.index' => static fn (Actor $actor): bool => true,
    'another-example.data' => [
        $isSuperAdmin,
        $isKasir,
    ],
    'another-example.view' => $isKasir,
    'another-example.create' => $isKasir,
    'another-example.update' => $isKasir,
    'another-example.delete' => $isKasir,
    'another-example.restore' => $isSuperAdmin,
];