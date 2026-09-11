<?php

declare(strict_types=1);

// Vendor Layer
use App\Domain\Shared\Core\Audit\AuditServiceInterface;
// Shared Layer
use App\Domain\Shared\Core\Contract\CurrentUserInterface;
use App\Infrastructure\Core\Audit\DatabaseAuditService;
// Infrastructure Layer
use App\Infrastructure\Core\Security\CurrentUser;
use Yiisoft\Db\Connection\ConnectionInterface;

return [
    CurrentUserInterface::class => CurrentUser::class,

    AuditServiceInterface::class => static fn (
        ConnectionInterface $db,
        CurrentUserInterface $currentUser
    ) => new DatabaseAuditService($db, $currentUser->getActor()),
];
