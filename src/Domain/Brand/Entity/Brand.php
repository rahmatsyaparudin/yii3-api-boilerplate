<?php

declare(strict_types=1);

namespace App\Domain\Brand\Entity;

use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Trait\StatusDelegationTrait;

final class Brand
{
    use StatusDelegationTrait;

    public function __construct(
        private int $id,
        private string $name,
        private Status $status,
        private array $detailInfo,
        private ?int $syncMdb = null
    ) {
    }

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function status(): Status { return $this->status; }
    public function detailInfo(): array { return $this->detailInfo; }
    public function syncMdb(): ?int { return $this->syncMdb; }

    // ====== BUSINESS RULES ======
    // Business rules methods are now provided by BusinessRulesTrait
}
