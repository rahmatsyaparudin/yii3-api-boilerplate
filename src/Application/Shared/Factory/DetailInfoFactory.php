<?php

declare(strict_types=1);

namespace App\Application\Shared\Factory;

use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Infrastructure\Security\CurrentUser;

final readonly class DetailInfoFactory
{
    public function __construct(
        private DateTimeProviderInterface $dateTime,
        private CurrentUser $currentUser
    ) {}

    public function create(array $payload = []): DetailInfo
    {
        return DetailInfo::createWithAudit(
            dateTime: $this->dateTime,
            user: $this->currentUser->getActor()->username,
            payload: $payload
        );
    }

    public function update(DetailInfo $detailInfo, array $payload = []): DetailInfo
    {
        $username = $this->currentUser->getActor()->username;
        $oldData = $detailInfo->toArray();
        
        $changeLog = $oldData['change_log'] ?? [];
        
        unset($oldData['change_log']);
        $mergedPayload = array_merge($oldData, $payload);
        
        return DetailInfo::updateWithAudit(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $mergedPayload
        );
    }

    public function delete(DetailInfo $detailInfo, array $payload = []): DetailInfo
    {
        $username = $this->currentUser->getActor()->username;
        $oldData = $detailInfo->toArray();
        
        $changeLog = $oldData['change_log'] ?? [];
        
        unset($oldData['change_log']);
        $mergedPayload = array_merge($oldData, $payload);
        
        return DetailInfo::updateWithAudit(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $mergedPayload
        );
    }

    public function restore(DetailInfo $detailInfo, array $payload = []): DetailInfo
    {
        $username = $this->currentUser->getActor()->username;
        $oldData = $detailInfo->toArray();
        
        $changeLog = $oldData['change_log'] ?? [];
        
        unset($oldData['change_log']);
        $mergedPayload = array_merge($oldData, $payload);
        
        return DetailInfo::restoreWithAudit(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $mergedPayload
        );
    }
}