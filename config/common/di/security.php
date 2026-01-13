<?php

declare(strict_types=1);

use App\Domain\Common\Audit\Actor;
use App\Infrastructure\Security\AccessChecker;
use App\Infrastructure\Security\CurrentUser;

// @var array $params

return [
    CurrentUser::class => CurrentUser::class,

    Actor::class => static fn (CurrentUser $currentUser) => $currentUser->getActor(),

    AccessChecker::class => static function (CurrentUser $currentUser) {
        $accessMap = require \dirname(__DIR__) . '/access.php';

        return new AccessChecker($currentUser, $accessMap);
    },
];
