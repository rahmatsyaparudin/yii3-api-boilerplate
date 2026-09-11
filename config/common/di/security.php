<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Domain\Shared\Core\Security\AuthorizerInterface;
use App\Infrastructure\Core\Security\AccessChecker;
use App\Infrastructure\Core\Security\Actor;
use App\Infrastructure\Core\Security\CurrentUser;
use App\Infrastructure\Core\Security\PermissionChecker;
// Domain Layer
use App\Infrastructure\Core\Security\RbacAuthorizer;

// Vendor Layer

// @var array $params

return [
    CurrentUser::class => [
        '__construct()' => [
            'allowGodMode' => $params['app/config']['allow_god_mode'] ?? false,
        ],
    ],
    Actor::class         => static fn (CurrentUser $currentUser) => $currentUser->getActor(),
    AccessChecker::class => static function (CurrentUser $currentUser) {
        $accessMap = require \dirname(__DIR__) . '/access.php';

        return new AccessChecker($currentUser, $accessMap);
    },

    PermissionChecker::class => [
        '__construct()' => [
            require __DIR__ . '/../access.php',
        ],
    ],

    AuthorizerInterface::class => RbacAuthorizer::class,
];
