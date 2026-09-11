<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Concerns;

// Shared Layer
use App\Infrastructure\Core\Security\CurrentUser;
// Infrastructure Layer
use App\Shared\Core\Enums\RecordStatus;

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

    public function scopeWhereNotDeleted(?string $app = 'api'): ?array
    {
        if (isset($this->currentUser) && $this->currentUser->getActor()?->isSuperAdmin($app)) {
            return [];
        }

        return ['<>', 'status', RecordStatus::DELETED->value];
    }

    public function scopeByStatus(?int $status): array
    {
        if ($status === null) {
            return [];
        }

        return ['=', 'status', $status];
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
