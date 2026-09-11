<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Core\Security\ActorProvider;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Infrastructure\Core\Security\JwtService;
use App\Shared\Core\Middleware\JwtMiddleware;

// @var array $params

return [
    JwtService::class => [
        '__construct()' => [
            'secret'   => $params['app/jwt']['secret'] ?? '',
            'algo'     => $params['app/jwt']['algorithm'] ?? 'HS256',
            'issuer'   => $params['app/jwt']['issuer'] ?? null,
            'audience' => $params['app/jwt']['audience'] ?? null,
        ],
    ],

    JwtMiddleware::class => static fn (
        JwtService $jwtService,
        ActorProvider $actorProvider,
        CurrentUser $currentUser
    ) => new JwtMiddleware(
        jwtService: $jwtService,
        actorProvider: $actorProvider,
        currentUser: $currentUser,
        publicPaths: $params['app/jwt']['publicPaths'] ?? [],
    ),
];
