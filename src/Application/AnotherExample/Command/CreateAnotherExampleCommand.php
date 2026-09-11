<?php

declare(strict_types=1);

namespace App\Application\AnotherExample\Command;

final readonly class CreateAnotherExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public int $exampleId,
        public ?array $detailInfo,
        public ?int $originId,
        public ?int $syncFlag,
    ) {
    }

    public static function create(
        string $name,
        int $status,
        int $exampleId,
        ?array $detailInfo = null,
        ?int $originId = null,
        ?int $syncFlag = null,
    ): self {
        return new self(
            name: $name,
            status: $status,
            exampleId: $exampleId,
            detailInfo: $detailInfo,
            originId: $originId,
            syncFlag: $syncFlag,
        );
    }
}
