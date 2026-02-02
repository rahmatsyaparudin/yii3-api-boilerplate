<?php

declare(strict_types=1);

use App\Shared\ValueObject\LockVersionConfig;

$globalEnabled = filter_var($_ENV['app.optimistic_lock.enabled'] ?? true, FILTER_VALIDATE_BOOLEAN);

return [
    LockVersionConfig::class => [
        '__construct()' => [
            'globalEnabled' => $globalEnabled,
            'validatorOverrides' => [
                'exampleinputvalidator' => filter_var($_ENV['app.optimistic_lock.example.enabled'] ?? $globalEnabled, FILTER_VALIDATE_BOOLEAN),
            ],
        ],
    ],
];