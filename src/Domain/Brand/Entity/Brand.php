<?php

declare(strict_types=1);

namespace App\Domain\Brand\Entity;

use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\Status;
use App\Shared\Exception\BadRequestException;
use App\Domain\Shared\Trait\EntityOperationsTrait;
use App\Domain\Shared\Trait\StatusDelegationTrait;
use App\Shared\ValueObject\Message;

final class Brand
{
    use StatusDelegationTrait, EntityOperationsTrait;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private Status $status,
        private DetailInfo $detailInfo,
        private ?int $syncMdb = null
    ) {
        $this->resource = 'Brand';
        $this->validateName($name);
        $this->validateStatus($status);
    }

    public static function create(
        string $name,
        Status $status,
        DetailInfo $detailInfo,
        ?int $syncMdb = null
    ): self {
        return new self(null, $name, $status, $detailInfo, $syncMdb);
    }

    public static function reconstitute(
        int $id,
        string $name,
        Status $status,
        DetailInfo $detailInfo,
        ?int $syncMdb = null
    ): self {
        // Create instance without validation for database-loaded entities
        return new self($id, $name, $status, $detailInfo, $syncMdb);
    }

    // Getters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getStatus(): Status
    {
        return $this->status;
    }

    public function getDetailInfo(): DetailInfo
    {
        return $this->detailInfo;
    }

    /**
     * Get detail info as JSON string
     */
    public function getDetailInfoJson(): string
    {
        return $this->detailInfo->toJson();
    }

    public function getSyncMdb(): ?int
    {
        return $this->syncMdb;
    }

    // Business Methods
    public function changeName(string $newName): void
    {
        if ($this->name === $newName) {
            return; // No change needed
        }

        $this->validateName($newName);
        $this->name = $newName;
    }

    public function changeStatus(Status $newStatus): void
    {
        if ($this->status === $newStatus) {
            return; // No change needed
        }

        $this->validateStatusTransition($newStatus);
        $this->status = $newStatus;
    }

    public function updateDetailInfo(DetailInfo $newDetailInfo): void
    {
        $this->detailInfo = $newDetailInfo;
    }

    public function updateSyncMdb(?int $syncMdb): void
    {
        $this->syncMdb = $syncMdb;
    }

    // Query Methods
    public function canChangeTo(Status $newStatus): bool
    {
        return $this->status->allowsTransitionTo($newStatus);
    }

    public function isActive(): bool
    {
        return $this->status->isActive();
    }

    public function isInactive(): bool
    {
        return $this->status->isInactive();
    }

    // Validation Methods
    private function validateName(string $name): void
    {
        // Name validation is handled in BrandInputValidator
        // Additional business rules can be added here
    }

    private function validateStatus(Status $status): void
    {
        if (!$status->isValidForCreation()) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'status.invalid_on_creation', 
                    domain: 'validation',
                    params: [
                        'resource' => $this->resource,
                    ],
                ),
            );
        }
    }

    private function validateStatusTransition(Status $newStatus): void
    {
        if (!$this->canChangeTo($newStatus)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'status.invalid_transition', 
                    domain: 'validation',
                    params: [
                        'resource' => $this->resource,
                        'from' => $this->status->name(), 
                        'to' => $newStatus->name()
                    ]
                ),
            );
        }
    }

    // Equality
    public function equals(Brand $other): bool
    {
        return $this->id === $other->getId();
    }

    // Infrastructure support (minimal)
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            'sync_mdb' => $this->syncMdb,
        ];
    }
}
