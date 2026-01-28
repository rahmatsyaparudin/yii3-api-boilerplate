<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Security\Actor;

/** @var array $params */
$params  = require __DIR__ . '/params.php';
$appCode = $params['app/config']['code'] ?? 'default';

$isKasir      = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isSpv        = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'spv');
$isAdmin      = static fn (Actor $actor): bool => $actor->isAdmin($appCode);
$isSuperAdmin = static fn (Actor $actor): bool => $actor->isSuperAdmin($appCode);

return [
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
];
