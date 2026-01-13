<?php

declare(strict_types=1);

namespace App\Shared\Helper;

use App\Infrastructure\Security\CurrentUser;
use App\Shared\Contract\TimestampProviderInterface;

final class DetailInfoHelper
{
    public function __construct(
        private CurrentUser $currentUser,
        private TimestampProviderInterface $timestampProvider,
    ) {
    }

    public function createChangeLog(): array
    {
        $timestamp = $this->timestampProvider->utcNow();
        $actor     = $this->currentUser->getActor();
        $createdBy = $actor?->username ?? 'system';

        return [
            'change_log' => [
                'created_at' => $timestamp,
                'created_by' => $createdBy,
                'deleted_at' => null,
                'deleted_by' => null,
                'updated_at' => null,
                'updated_by' => null,
            ],
        ];
    }

    public function updateChangeLog(array $existingDetailInfo): array
    {
        $timestamp = $this->timestampProvider->utcNow();
        $actor     = $this->currentUser->getActor();
        $updatedBy = $actor?->username ?? 'system';

        $changeLog = $existingDetailInfo['change_log'] ?? [];

        return [
            'change_log' => \array_merge($changeLog, [
                'updated_at' => $timestamp,
                'updated_by' => $updatedBy,
            ]),
        ];
    }

    public function deleteChangeLog(array $existingDetailInfo): array
    {
        $timestamp = $this->timestampProvider->utcNow();
        $actor     = $this->currentUser->getActor();
        $deletedBy = $actor?->username ?? 'system';

        $changeLog = $existingDetailInfo['change_log'] ?? [];

        return [
            'change_log' => \array_merge($changeLog, [
                'deleted_at' => $timestamp,
                'deleted_by' => $deletedBy,
            ]),
        ];
    }

    public function mergeWithExisting(array $existingDetailInfo, array $newData): array
    {
        return \array_merge($existingDetailInfo, $newData);
    }

    public function getChangeLog(array $detailInfo): array
    {
        return $detailInfo['change_log'] ?? [];
    }

    public function hasChangeLog(array $detailInfo): bool
    {
        return isset($detailInfo['change_log']);
    }
}
