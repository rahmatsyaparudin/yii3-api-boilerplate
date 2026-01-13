<?php

declare(strict_types=1);

use App\Domain\Common\Audit\Actor;

/** @var array $params */
$params  = require __DIR__ . '/params.php';
$appCode = $params['app/config']['code'] ?? 'default';

$isKasir      = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'kasir');
$isSpv        = static fn (Actor $actor): bool => $actor->hasRole($appCode, 'spv');
$isAdmin      = static fn (Actor $actor): bool => $actor->isAdmin($appCode);
$isSuperAdmin = static fn (Actor $actor): bool => $actor->isSuperAdmin($appCode);

return [
    'brand.index'  => static fn (Actor $actor): bool => true,
    'brand.data'   => $isKasir,
    'brand.view'   => $isKasir,
    'brand.create' => $isKasir,
    'brand.update' => $isKasir,
    'brand.delete' => $isKasir,
];
