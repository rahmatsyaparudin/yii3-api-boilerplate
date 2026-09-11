<?php

declare(strict_types=1);

namespace App\Domain\Shared\Core\Audit;

// Domain Layer
use App\Domain\Shared\Core\Contract\ActorInterface;

interface AuditServiceInterface
{
    public function log(
        string $tableName,
        int $recordId,
        string $action,
        ?array $oldValues = null,
        ?array $newValues = null,
        ?ActorInterface $actor = null,
        ?string $ipAddress = null,
        ?string $userAgent = null
    ): void;
}
