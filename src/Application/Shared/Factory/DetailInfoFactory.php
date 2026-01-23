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

    /**
     * Create DetailInfo with approval
     */
    public function createWithApproval(array $payload = []): DetailInfo
    {
        return $this->create($payload)->withApprovedExplicit($this->dateTime, $this->currentUser->getActor()->username);
    }

    /**
     * Create DetailInfo with rejection
     */
    public function createWithRejection(array $payload = []): DetailInfo
    {
        return $this->create($payload)->withRejectedExplicit($this->dateTime, $this->currentUser->getActor()->username);
    }

    /**
     * Create DetailInfo with custom field
     */
    public function createWithField(string $field, mixed $value, array $payload = []): DetailInfo
    {
        return $this->create($payload)->withFieldExplicit($field, $value, $this->dateTime, $this->currentUser->getActor()->username);
    }

    /**
     * Update DetailInfo with approval
     */
    public function updateWithApproval(DetailInfo $detailInfo, array $payload = []): DetailInfo
    {
        return $this->update($detailInfo, $payload)->withApprovedExplicit($this->dateTime, $this->currentUser->getActor()->username);
    }

    /**
     * Update DetailInfo with rejection
     */
    public function updateWithRejection(DetailInfo $detailInfo, array $payload = []): DetailInfo
    {
        return $this->update($detailInfo, $payload)->withRejectedExplicit($this->dateTime, $this->currentUser->getActor()->username);
    }

    /**
     * Update DetailInfo with custom field
     */
    public function updateWithField(DetailInfo $detailInfo, string $field, mixed $value, array $payload = []): DetailInfo
    {
        return $this->update($detailInfo, $payload)->withFieldExplicit($field, $value, $this->dateTime, $this->currentUser->getActor()->username);
    }
}