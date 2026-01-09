<?php

declare(strict_types=1);

use App\Domain\Common\Audit\Actor;
use App\Infrastructure\Security\CurrentUser;

return [
    CurrentUser::class => CurrentUser::class,
    
    Actor::class => static function (CurrentUser $currentUser) {
        return $currentUser->getActor();
    },
];
