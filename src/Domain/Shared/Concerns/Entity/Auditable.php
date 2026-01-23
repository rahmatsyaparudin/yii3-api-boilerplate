<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

use App\Domain\Shared\Contract\DateTimeProviderInterface;

trait Auditable
{
    /**
     * Menggabungkan payload dengan struktur audit log standar.
     * Digunakan biasanya saat pembuatan awal (static factory).
     */
    public static function createAuditStamp(
        DateTimeProviderInterface $dateTime,
        string $user,
        array $payload = []
    ): array {
        return array_merge($payload, [
            'change_log' => [
                'created_at' => $dateTime->iso8601(),
                'created_by' => $user,
                'updated_at' => null,
                'updated_by' => null,
                'deleted_at' => null,
                'deleted_by' => null,
            ]
        ]);
    }

    /**
     * Mengupdate array audit yang sudah ada.
     */
    public static function updateAuditStamp(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['updated_at'] = $dateTime->iso8601();
        $currentLog['updated_by'] = $user;

        return $currentLog;
    }

    public static function deleteAuditStamp(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['deleted_at'] = $dateTime->iso8601();
        $currentLog['deleted_by'] = $user;

        return $currentLog;
    }

    public static function restoreAuditStamp(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['updated_at'] = $dateTime->iso8601();
        $currentLog['updated_by'] = $user;
        $currentLog['deleted_at'] = null;
        $currentLog['deleted_by'] = null;

        return $currentLog;
    }
}