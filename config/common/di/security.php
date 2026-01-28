<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Security\Actor;
use App\Infrastructure\Security\AccessChecker;
use App\Infrastructure\Security\CurrentUser;
use App\Infrastructure\Security\CurrentUserAwareInterface;
use App\Infrastructure\Security\PermissionChecker;
use App\Infrastructure\Security\RbacAuthorizer;

// Domain Layer
use App\Domain\Shared\Security\AuthorizerInterface;

// Vendor Layer
use Yiisoft\Definitions\Reference;

// @var array $params

return [
    CurrentUser::class => CurrentUser::class,
    Actor::class => static fn (CurrentUser $currentUser) => $currentUser->getActor(),
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
