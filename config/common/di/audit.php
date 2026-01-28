<?php

declare(strict_types=1);

// Vendor Layer
use Yiisoft\Db\Connection\ConnectionInterface;

// Shared Layer
use App\Domain\Shared\Audit\AuditServiceInterface;
use App\Domain\Shared\Contract\CurrentUserInterface;

// Infrastructure Layer
use App\Infrastructure\Audit\DatabaseAuditService;
use App\Infrastructure\Security\CurrentUser;

return [
    CurrentUserInterface::class => CurrentUser::class,

    AuditServiceInterface::class => static fn (
        ConnectionInterface $db,
        CurrentUserInterface $currentUser
    ) => new DatabaseAuditService($db, $currentUser->getActor()),
];
