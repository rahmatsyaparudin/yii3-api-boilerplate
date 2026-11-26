<?php

declare(strict_types=1);

/** @var array $params */

use App\Application\Shared\Core\Factory\SyncIdentityFactory;

// Core service DI configuration. Add project-wide service bindings here.
// Project-owned bindings may be merged from config/common/service.php and
// can override these defaults.
$projectBindings = \dirname(__DIR__) . '/service.php';

return array_merge([
    SyncIdentityFactory::class => [
        'class'         => SyncIdentityFactory::class,
        '__construct()' => [
            'params' => $params['app/sync'] ?? [],
        ],
    ],
], file_exists($projectBindings) ? require $projectBindings : []);
