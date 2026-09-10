<?php

declare(strict_types=1);

namespace App\Application\AnotherExample\Command;

final readonly class UpdateAnotherExampleCommand
{
    public function __construct(
        public int $id,
        public ?string $name,
        public ?int $status,
        public int $exampleId,
        public ?array $detailInfo,
        public ?int $lockVersion,
    ) {}

    public static function create(
        int $id,
        int $exampleId,
        ?string $name = null,
        ?int $status = null,
        ?array $detailInfo = null,
        ?int $lockVersion = null,
    ): self {
        return new self(
            id: $id,
            name: $name,
            status: $status,
            exampleId: $exampleId,
            detailInfo: $detailInfo,
            lockVersion: $lockVersion,
        );
    }
}
