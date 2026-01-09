<?php

declare(strict_types=1);

use App\Domain\Common\Audit\ChangeLogFactory;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Security\CurrentUser;
use Psr\Clock\ClockInterface;

return [
    ClockInterface::class => SystemClock::class,
    
    ChangeLogFactory::class => static function (ClockInterface $clock, CurrentUser $currentUser) {
        return new ChangeLogFactory($clock, $currentUser);
    },
];
