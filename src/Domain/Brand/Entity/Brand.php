<?php

declare(strict_types=1);

namespace App\Domain\Brand\Entity;

use App\Domain\Shared\ValueObject\Status;
use App\Domain\Shared\Trait\StatusDelegationTrait;

final class Brand
{
    use StatusDelegationTrait;

    private array|null $detailInfoLazy = null;

    public function __construct(
        private int $id,
        private string $name,
        private Status $status,
        private string|array $detailInfo, // bisa string JSON atau array
        private ?int $syncMdb = null
    ) {
        // jika sudah array, simpan langsung
        if (is_array($detailInfo)) {
            $this->detailInfoLazy = $detailInfo;
        }
    }

    public function id(): int { return $this->id; }
    public function name(): string { return $this->name; }
    public function status(): Status { return $this->status; }
    public function syncMdb(): ?int { return $this->syncMdb; }

    // Lazy decode detailInfo
    public function detailInfo(): array
    {
        if ($this->detailInfoLazy !== null) {
            return $this->detailInfoLazy;
        }

        if (is_string($this->detailInfo)) {
            $this->detailInfoLazy = json_decode($this->detailInfo, true) ?? [];
        } else {
            $this->detailInfoLazy = [];
        }

        return $this->detailInfoLazy;
    }

    // ================= Factory Method =================
    public function toArray(): array
    {
        return [
            'id' => $this->id(),
            'name' => $this->name(),
            'status' => $this->status()->value(), // enum value
            'detail_info' => $this->detailInfo(),
            'sync_mdb' => $this->syncMdb(),
        ];
    }
    
    public static function fromArray(array $data): self
    {
        return new self(
            id: $data['id'],
            name: $data['name'],
            status: Status::from($data['status']), // convert ke ValueObject Status
            detailInfo: $data['detail_info'], // bisa JSON string dari DB
            syncMdb: $data['sync_mdb'] ?? null
        );
    }
}
