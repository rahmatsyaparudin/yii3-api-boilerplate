<?php

declare(strict_types=1);

namespace App\Domain\Brand\Entity;

use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;
use App\Domain\Shared\Concerns\Entity\OptimisticLock;
use App\Shared\ValueObject\Message;

final class Brand
{
    use Identifiable, Stateful, Descriptive, OptimisticLock;

    public const RESOURCE = 'Brand';

    protected function __construct(
        private readonly ?int $id,
        private string $name,
        private Status $status,
        private DetailInfo $detailInfo,
        private ?int $syncMdb = null,
        ?LockVersion $lockVersion = null
    ) {
        $this->resource = self::RESOURCE;
        $this->lockVersion = $lockVersion ?? LockVersion::create();
    }

    public static function create(
        string $name,
        Status $status,
        DetailInfo $detailInfo,
        ?int $syncMdb = null
    ): self {
        self::guardInitialStatus($status);

        return new self(null, $name, $status, $detailInfo, $syncMdb, LockVersion::create());
    }

    public static function reconstitute(
        int $id,
        string $name,
        Status $status,
        DetailInfo $detailInfo,
        ?int $syncMdb = null,
        int $lockVersion = LockVersion::DEFAULT_VALUE,
    ): self {
        // Create instance without validation for database-loaded entities
        return new self($id, $name, $status, $detailInfo, $syncMdb, LockVersion::fromInt($lockVersion));
    }

    public function getSyncMdb(): ?int
    {
        return $this->syncMdb;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            'sync_mdb' => $this->syncMdb,
            'lock_version' => $this->lockVersion->getValue(),
        ];
    }

    public function updateSyncMdb(?int $syncMdb): void
    {
        $this->syncMdb = $syncMdb;
    }

    /**
     * Place Brand-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */
    
}
