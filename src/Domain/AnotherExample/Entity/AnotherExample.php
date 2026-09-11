<?php

declare(strict_types=1);

namespace App\Domain\AnotherExample\Entity;

// Shared Layer
use App\Domain\Shared\Core\Concerns\Entity\Descriptive;
use App\Domain\Shared\Core\Concerns\Entity\Identifiable;
// Domain Layer
use App\Domain\Shared\Core\Concerns\Entity\Stateful;
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\LockVersion;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;
use App\Domain\Shared\Core\ValueObject\SyncMdb;
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

final class AnotherExample
{
    use Identifiable;
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'AnotherExample';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private int $exampleId,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        private ?SyncFlag $syncFlag = null,
        ?LockVersion $lockVersion = null,
    ) {
        $this->resource    = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        int $exampleId,
        ?SyncMdb $syncMdb = null,
        ?SyncFlag $syncFlag = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            exampleId: $exampleId,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            syncFlag: $syncFlag,
            lockVersion: LockVersion::create()
        );
    }

    public function toPersistence(): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'example_id' => $this->exampleId,
        ];
    }

    public static function reconstitute(
        int $id,
        string $name,
        int $exampleId,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?SyncFlag $syncFlag = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            exampleId: $exampleId,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            syncFlag: $syncFlag,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'example_id'         => $this->exampleId,
            'status'             => $this->status->value(),
            'detail_info'        => $this->detailInfo->toArray(),
            SyncMdb::field()              => $this->syncMdb?->value(),
            SyncFlag::fieldOriginId()     => $this->syncFlag?->getOriginId(),
            SyncFlag::fieldSyncFlag()     => $this->syncFlag?->getSyncFlag(),
            LockVersion::field()          => $this->lockVersion->value(),
        ];
    }

    public function getSyncFlag(): ?SyncFlag
    {
        return $this->syncFlag;
    }

    public function getSyncFlagValue(): ?int
    {
        return $this->syncFlag?->getSyncFlag();
    }

    public function getOriginId(): ?int
    {
        return $this->syncFlag?->getOriginId();
    }

    public function setSyncFlag(SyncFlag $syncFlag): self
    {
        $this->syncFlag = $syncFlag;

        return $this;
    }

    public function updateSyncFlag(?SyncFlag $syncFlag): void
    {
        $this->syncFlag = $syncFlag;
    }

    public function getExampleId(): int
    {
        return $this->exampleId;
    }

    public function updateExampleId(int $exampleId): void
    {
        $this->exampleId = $exampleId;
    }

    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: Message::create(
                    domain: 'validation', 
                    key: 'resource.not_deleted', 
                    params: [
                        'resource' => self::RESOURCE, 
                        'id' => $this->id
                    ]
                )
            );
        }

        $this->status = ResourceStatus::restored();
    }

    /*
     * Place AnotherExample-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */
}
