<?php

declare(strict_types=1);

use App\Domain\Common\Audit\AuditService;
use App\Infrastructure\Security\CurrentUser;
use Psr\Clock\ClockInterface;
use Yiisoft\Db\Connection;

return [
    AuditService::class => static fn (Connection $db) => new AuditService($db),
];
