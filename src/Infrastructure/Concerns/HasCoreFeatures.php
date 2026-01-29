<?php

namespace App\Infrastructure\Concerns;

// Shared Layer
use App\Shared\Enums\RecordStatus;

// Infrastructure Layer
use App\Infrastructure\Security\CurrentUser;

trait HasCoreFeatures
{
    private ?CurrentUser $currentUser = null;

    public function __construct()
    {
        // Property initialized to null
    }

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
        // This method should be implemented by the using class
        // Return empty array as default implementation
        return [];
    }

    public function getDeletedState(): array
    {
        return [
                'status' => RecordStatus::DELETED->value,
            ];
    }
}