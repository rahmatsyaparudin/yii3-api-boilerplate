<?php

declare(strict_types=1);

// Shared Layer
use App\Shared\ApplicationParams;

/** @var array $params */

return [
    ApplicationParams::class => [
        'class' => ApplicationParams::class,
        '__construct()' => [
            'name'    => $params['application']['name'] ?? 'My Project',
            'version' => $params['application']['version'] ?? '1.0',
        ],
    ],
];
