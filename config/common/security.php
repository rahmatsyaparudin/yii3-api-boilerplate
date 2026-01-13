<?php

declare(strict_types=1);

use Yiisoft\Security\TrustedHosts\TrustedHosts;

return [
    TrustedHosts::class => [
        // Host yang VALID untuk request masuk
        'allowedHosts' => [
            'api.example.com',
            'example.com',
            '*.example.com',
            // 'localhost',
        ],

        // Header proxy yang boleh dipercaya
        'allowedHeaders' => [
            'X-Forwarded-For',
            'X-Forwarded-Host',
            'X-Forwarded-Proto',
            'Forwarded',
        ],
    ],
];
