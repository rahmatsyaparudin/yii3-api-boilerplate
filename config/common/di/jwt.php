<?php

declare(strict_types=1);

use App\Infrastructure\Security\JwtService;

// @var array $params

return [
    JwtService::class => [
        '__construct()' => [
            'secret'   => $params['app/jwt']['secret'],
            'algo'     => $params['app/jwt']['algorithm'] ?? 'HS256',
            'issuer'   => $params['app/jwt']['issuer'] ?? null,
            'audience' => $params['app/jwt']['audience'] ?? null,
        ],
    ],
];
