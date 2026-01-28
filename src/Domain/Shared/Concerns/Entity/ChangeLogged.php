<?php

declare(strict_types=1);

namespace App\Domain\Shared\Concerns\Entity;

// Domain Layer
use App\Domain\Shared\Contract\DateTimeProviderInterface;

trait ChangeLogged
{
    /**
     * Menggabungkan payload dengan struktur audit log standar.
     * Digunakan biasanya saat pembuatan awal (static factory).
     */
    public static function createdChangeLog(
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
    public static function updatedChangeLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['updated_at'] = $dateTime->iso8601();
        $currentLog['updated_by'] = $user;

        return $currentLog;
    }

    public static function deletedChangeLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['deleted_at'] = $dateTime->iso8601();
        $currentLog['deleted_by'] = $user;

        return $currentLog;
    }

    public static function restoredChangeLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['updated_at'] = $dateTime->iso8601();
        $currentLog['updated_by'] = $user;
        $currentLog['deleted_at'] = null;
        $currentLog['deleted_by'] = null;

        return $currentLog;
    }

    public static function approvedChangeLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['approved_at'] = $dateTime->iso8601();
        $currentLog['approved_by'] = $user;

        return $currentLog;
    }

    public static function rejectedChangeLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['rejected_at'] = $dateTime->iso8601();
        $currentLog['rejected_by'] = $user;

        return $currentLog;
    }

    public static function reviewedChangeLog(array $currentLog, DateTimeProviderInterface $dateTime, string $user): array
    {
        $currentLog['reviewed_at'] = $dateTime->iso8601();
        $currentLog['reviewed_by'] = $user;

        return $currentLog;
    }
}