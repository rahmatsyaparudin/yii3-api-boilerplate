<?php

declare(strict_types=1);

use App\Domain\Shared\Audit\AuditServiceInterface;
use App\Domain\Shared\Contract\CurrentUserInterface;
use App\Infrastructure\Audit\DatabaseAuditService;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    CurrentUserInterface::class => \App\Infrastructure\Security\CurrentUser::class,

    AuditServiceInterface::class => static fn (
        ConnectionInterface $db,
        CurrentUserInterface $currentUser
    ) => new DatabaseAuditService($db, $currentUser->getActor()),
];
