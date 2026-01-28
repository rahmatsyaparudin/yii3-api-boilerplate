<?php

declare(strict_types=1);

namespace App\Application\Shared\Factory;

use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\Contract\DateTimeProviderInterface;
use App\Infrastructure\Security\CurrentUser;
use App\Shared\Exception\ServiceException;
use App\Shared\ValueObject\Message;
use Yiisoft\Http\Status;

final class DetailInfoFactory
{
    private ?DetailInfo $current = null;

    public function __construct(
        private DateTimeProviderInterface $dateTime,
        private CurrentUser $currentUser
    ) {}

    public function create(array $detailInfo = []): self
    {
        $this->current = DetailInfo::createdLog(
            dateTime: $this->dateTime,
            user: $this->currentUser->getActor()->username,
            payload: $detailInfo
        );

        return $this;
    }

    public function update(DetailInfo $detailInfo, array $payload = []): self
    {
        $username = $this->currentUser->getActor()->username;
        $oldData = $detailInfo->toArray();
        
        $changeLog = $oldData['change_log'] ?? [];
        
        unset($oldData['change_log']);
        $mergedPayload = array_merge($oldData, $payload);
        
        $this->current = DetailInfo::updatedLog(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $mergedPayload
        );
        
        return $this;
    }

    public function delete(DetailInfo $detailInfo, array $payload = []): self
    {
        $username = $this->currentUser->getActor()->username;
        $oldData = $detailInfo->toArray();
        
        $changeLog = $oldData['change_log'] ?? [];
        
        unset($oldData['change_log']);
        $mergedPayload = array_merge($oldData, $payload);
        
        $this->current = DetailInfo::deletedLog(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $mergedPayload
        );
        
        return $this;
    }

    public function restore(DetailInfo $detailInfo, array $payload = []): self
    {
        $username = $this->currentUser->getActor()->username;
        $oldData = $detailInfo->toArray();
        
        $changeLog = $oldData['change_log'] ?? [];
        
        unset($oldData['change_log']);
        $mergedPayload = array_merge($oldData, $payload);
        
        $this->current = DetailInfo::restoredLog(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $mergedPayload
        );
        
        return $this;
    }

    public function withEmptyApproval(): self
    {
        $this->ensureInstanceExists();

        $this->current = $this->current->with([
            'approved_at' => null,
            'approved_by' => null,
        ]);

        return $this;
    }

    public function withEmptyRejection(): self
    {
        $this->ensureInstanceExists();

        $this->current = $this->current->with([
            'rejected_at' => null,
            'rejected_by' => null,
        ]);

        return $this;
    }

    public function withApproved(): self
    {
        $this->ensureInstanceExists();
        $this->ensureNotDeleted();

        $username = $this->currentUser->getActor()->username;
        $data = $this->current->toArray();
        $changeLog = $data['change_log'] ?? [];
        
        $this->current = DetailInfo::approvedLog(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $data
        );

        return $this;
    }

    public function withRejected(): self
    {
        $this->ensureInstanceExists();

        $username = $this->currentUser->getActor()->username;
        $data = $this->current->toArray();
        $changeLog = $data['change_log'] ?? [];
        
        $this->current = DetailInfo::rejectedLog(
            dateTime: $this->dateTime,
            user: $username,
            currentLog: $changeLog,
            payload: $data
        );

        return $this;
    }

    public function build(): DetailInfo
    {
        $result = $this->current;
        $this->current = null;
        return $result;
    }

    private function ensureInstanceExists(): void
    {
        if (!$this->current) {
            throw new ServiceException(
                translate: new Message(
                    key: "factory.detail_info.uninitialized_state",
                    params: [
                        'methods' => 'create() or update()'
                    ]
                ),
                code: Status::INTERNAL_SERVER_ERROR
            );
        }
    }

    private function ensureNotDeleted(): void
    {
        $data = $this->current->toArray();
        $isDeleted = !empty($data['change_log']['deleted_at']);

        if ($isDeleted) {
            throw new ServiceException(
                translate: new Message(
                    key: "resource.modification_denied_on_deleted",
                    params: [
                        'resource' => 'Resource',
                        'status' => 'Deleted',
                    ]
                ),
                code: Status::INTERNAL_SERVER_ERROR
            );
        }
    }
}