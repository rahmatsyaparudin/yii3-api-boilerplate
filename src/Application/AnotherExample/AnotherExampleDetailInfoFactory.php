<?php

declare(strict_types=1);

namespace App\Application\AnotherExample;

use App\Domain\Shared\ValueObject\ResourceStatus;
use App\Shared\Exception\NotFoundException;
use App\Shared\ValueObject\Message;

// Application DTO
use App\Application\AnotherExample\Dto\AnotherExampleDetailInfo;

// Domain Repository
use App\Domain\Example\Repository\ExampleRepositoryInterface;

final readonly class AnotherExampleDetailInfoFactory
{
    public function __construct(
        private ExampleRepositoryInterface $exampleRepository,
    ) {}

    public function buildFromPrimitives(
        ?int $exampleId,
    ): AnotherExampleDetailInfo {
        $example = null;

        if ($exampleId !== null) {
            $example = $this->exampleRepository->findById(
                id: $exampleId,
                status: ResourceStatus::active()->value()
            );

            if ($example === null) {
                throw new NotFoundException(
                    translate: Message::create(
                        key: 'resource.not_found',
                        params: [
                            'resource' => 'example',
                            'field' => 'id',
                            'value' => $exampleId
                        ]
                    )
                );
            }
        }

        return new AnotherExampleDetailInfo(
            example: $example?->toPersistence(),
        );
    }
}
