<?php

declare(strict_types=1);

namespace App\Domain\Example\Entity;

// Shared Layer
use App\Domain\Shared\Core\Concerns\Entity\Descriptive;
use App\Domain\Shared\Core\Concerns\Entity\Identifiable;
// Domain Layer
use App\Domain\Shared\Core\Concerns\Entity\Stateful;
use App\Domain\Shared\Core\ValueObject\DetailInfo;
use App\Domain\Shared\Core\ValueObject\LockVersion;
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Domain\Shared\Core\ValueObject\SyncMdb;
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\ValueObject\Message;

final class Example
{
    use Identifiable;
    use Stateful;
    use Descriptive;

    public const RESOURCE = 'Example';

    private LockVersion $lockVersion;

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private ResourceStatus $status,
        private DetailInfo $detailInfo,
        private ?SyncMdb $syncMdb = null,
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
        ?LockVersion $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
            syncMdb: $syncMdb,
            lockVersion: $lockVersion ?? LockVersion::create()
        );
    }

    public function toArray(): array
    {
        return [
            'id'                 => $this->id,
            'name'               => $this->name,
            'status'             => $this->status->value(),
            'detail_info'        => $this->detailInfo->toArray(),
            SyncMdb::field()     => $this->syncMdb?->value(),
            LockVersion::field() => $this->lockVersion->value(),
        ];
    }

    public function restore(): void
    {
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(translate: Message::create(key: 'resource.not_deleted', domain: 'validation', params: ['resource' => self::RESOURCE, 'id' => $this->id]));
        }

        $this->status = ResourceStatus::restored();
    }

    /*
     * Place Example-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */
}
