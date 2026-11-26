<?php

declare(strict_types=1);

namespace App\Domain\SyncExample\Entity;

// Shared Layer
use App\Domain\Shared\Core\Concerns\Entity\Descriptive;
use App\Domain\Shared\Core\Concerns\Entity\Identifiable;
// Domain Layer
use App\Domain\Shared\Core\Concerns\Entity\Stateful;
use App\Domain\Shared\Core\Enum\DocRole;
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\LockVersion;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\Shared\Core\ValueObject\SyncFlag;
use App\Domain\Shared\Core\ValueObject\SyncIdentity;
use App\Domain\Shared\Core\ValueObject\SyncMdb;
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

/**
 * Reference entity for the full sync column set — carries both the
 * legacy `SyncFlag` (origin_id + sync_flag) and the new `SyncIdentity`
 * (uuid + source_store_id + doc_role).
 */
final class SyncExample
{
    use Identifiable;
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'SyncExample';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
        private ?SyncFlag $syncFlag = null,
        private ?SyncIdentity $syncIdentity = null,
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
        ?SyncMdb $syncMdb = null,
        ?SyncFlag $syncFlag = null,
        ?SyncIdentity $syncIdentity = null,
    ): self {
        self::guardInitialStatus(
            status: $status,
            resource: self::RESOURCE
        );

        return new self(
            id: null,
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            syncFlag: $syncFlag,
            syncIdentity: $syncIdentity ?? SyncIdentity::local(),
            lockVersion: LockVersion::create()
        );
    }

    public function toPersistence(): array
    {
        return [
            'id'   => $this->id,
            'name' => $this->name,
        ];
    }

    public static function reconstitute(
        int $id,
        string $name,
        ResourceStatus $status,
        DetailInfo $detailInfo,
        ?SyncMdb $syncMdb = null,
        ?SyncFlag $syncFlag = null,
        ?SyncIdentity $syncIdentity = null,
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            syncFlag: $syncFlag,
            syncIdentity: $syncIdentity,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id'                          => $this->id,
            'name'                        => $this->name,
            'status'                      => $this->status->value(),
            'detail_info'                 => $this->detailInfo->toArray(),
            SyncMdb::field()              => $this->syncMdb?->value(),
            SyncFlag::fieldOriginId()     => $this->syncFlag?->getOriginId(),
            SyncFlag::fieldSyncFlag()     => $this->syncFlag?->getSyncFlag(),
            SyncIdentity::fieldUuid()     => $this->syncIdentity?->getUuid(),
            SyncIdentity::fieldSourceStoreId() => $this->syncIdentity?->getSourceStoreId(),
            SyncIdentity::fieldDocRole()  => $this->syncIdentity?->getDocRole()->value,
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

    public function getSyncIdentity(): ?SyncIdentity
    {
        return $this->syncIdentity;
    }

    public function getUuid(): ?string
    {
        return $this->syncIdentity?->getUuid();
    }

    public function getSourceStoreId(): ?int
    {
        return $this->syncIdentity?->getSourceStoreId();
    }

    public function getDocRole(): ?DocRole
    {
        return $this->syncIdentity?->getDocRole();
    }

    public function getDocRoleValue(): ?string
    {
        return $this->syncIdentity?->getDocRole()->value;
    }

    public function setSyncIdentity(SyncIdentity $syncIdentity): self
    {
        $this->syncIdentity = $syncIdentity;

        return $this;
    }

    public function updateSyncIdentity(?SyncIdentity $syncIdentity): void
    {
        $this->syncIdentity = $syncIdentity;
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
     * Place SyncExample-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */
}
