<?php

declare(strict_types=1);

use App\Domain\Common\Audit\AuditService;
use App\Domain\Common\Audit\ChangeLogFactory;
use App\Infrastructure\Clock\SystemClock;
use App\Infrastructure\Security\CurrentUser;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection;

return [
    ClockInterface::class => SystemClock::class,

    ChangeLogFactory::class => static fn (ClockInterface $clock, CurrentUser $currentUser) => new ChangeLogFactory($clock, $currentUser),

    AuditService::class => static fn (Connection $db) => new AuditService($db),
];
