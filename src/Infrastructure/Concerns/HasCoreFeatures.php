<?php

namespace App\Infrastructure\Concerns;

use App\Shared\Enums\RecordStatus;
use App\Infrastructure\Security\CurrentUser;

trait HasCoreFeatures
{
    private ?CurrentUser $currentUser;

    public function setCurrentUser(CurrentUser $currentUser): void
    {
        $this->currentUser = $currentUser;
    }

    public function scopeWhereNotDeleted(?string $app = 'api'): array|null
    {
        if (isset($this->currentUser) && $this->currentUser->getActor()?->isSuperAdmin($app)) {
            return null;
        }

        return ['<>', 'status', RecordStatus::DELETED->value];
    }

    public function scopeWhereDeleted(): array
    {
        return ['=', 'status', RecordStatus::DELETED->value];
    }

    public function syncToMdb(): void
    {
    }

    public function getDetailInfo(): array
    {
        return $this->detail_info ?? [];
    }

    public function getDeletedState(): array
    {
        return [
                'status' => RecordStatus::DELETED->value,
            ];
    }
}