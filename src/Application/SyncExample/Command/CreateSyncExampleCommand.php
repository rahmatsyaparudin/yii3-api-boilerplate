<?php

declare(strict_types=1);

namespace App\Application\SyncExample\Command;

final readonly class CreateSyncExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?array $detailInfo,
        public ?int $originId,
        public ?int $syncFlag,
    ) {
    }

    public static function create(
        string $name,
        int $status,
        ?array $detailInfo = null,
        ?int $originId = null,
        ?int $syncFlag = null,
    ): self {
        return new self(
            name: $name,
            status: $status,
            detailInfo: $detailInfo,
            originId: $originId,
            syncFlag: $syncFlag,
        );
    }
}
