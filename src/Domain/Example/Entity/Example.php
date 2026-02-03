<?php

declare(strict_types=1);

namespace App\Domain\Example\Entity;

// Domain Layer
use App\Domain\Shared\ValueObject\DetailInfo;
use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\ValueObject\LockVersion;
use App\Domain\Shared\Concerns\Entity\Stateful;
use App\Domain\Shared\Concerns\Entity\Identifiable;
use App\Domain\Shared\Concerns\Entity\Descriptive;
use App\Domain\Shared\Concerns\Entity\OptimisticLock;

// Shared Layer
use App\Shared\ValueObject\Message;
use App\Shared\Exception\BadRequestException;

final class Example
{
    use Identifiable, Stateful, Descriptive, OptimisticLock;

    public const RESOURCE = 'Example';

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

    public static function getResource(): string
    {
        return self::RESOURCE;
    }

    public static function create(
        string $name,
        Status $status,
        DetailInfo $detailInfo,
        ?int $syncMdb = null
    ): self {
        self::guardInitialStatus($status, self::RESOURCE);

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

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'status' => $this->status->value(),
            'detail_info' => $this->detailInfo->toArray(),
            'sync_mdb' => $this->syncMdb,
            'lock_version' => $this->lockVersion->value(),
        ];
    }
    
    public function restore(): void
    {
        // Restore entity from deleted state to draft
        if (!$this->status->isDeleted()) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'resource.not_deleted',
                    params: ['id' => $this->id]
                )
            );
        }
        
        $this->status = Status::draft();
    }

    /**
     * Place Example-specific business functions here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     */
    
}
