<?php

declare(strict_types=1);

// Shared Layer
use App\Shared\ApplicationParams;

/** @var array $params */

$env = $_ENV['APP_ENV'] ?? 'prod';
$environment = null;
if ($env === 'dev' || $env === 'development') {
    $environment = 'development';
}

return [
    ApplicationParams::class => [
        'class' => ApplicationParams::class,
        '__construct()' => [
            'name'        => $params['application']['name'] ?? 'My Project',
            'version'     => $params['application']['version'] ?? '1.0',
            'language'    => $params['application']['language'] ?? 'en',
            'environment' => $environment,
        ],
    ],
];
