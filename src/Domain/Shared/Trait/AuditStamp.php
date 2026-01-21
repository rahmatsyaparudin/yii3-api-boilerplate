<?php

declare(strict_types=1);

namespace App\Domain\Shared\Trait;

use App\Domain\Shared\Contract\DateTimeProviderInterface;

trait AuditStamp
{
    /**
     * Update existing audit log
     */
    public static function updateAuditLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['updated_at'] = $dateTime->iso8601();
        $currentLog['updated_by'] = $user;

        return $currentLog;
    }

    /**
     * Create with audit - combines payload with audit log
     */
    public static function createAuditLog(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $payload = [] 
    ): array {
        // Build audit log directly
        $audit = [
            'change_log' => [
                'created_at' => $dateTime->iso8601(),
                'created_by' => $user,
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ]
        ];
        
        return array_merge($payload, $audit);
    }
}