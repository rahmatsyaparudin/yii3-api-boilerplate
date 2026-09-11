<?php

declare(strict_types=1);

namespace App\Application\AnotherExample;

use App\Application\AnotherExample\Dto\AnotherExampleDetailInfo;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Shared\ValueObject\ResourceStatus;
// Application DTO
use App\Shared\Core\Exception\NotFoundException;
// Domain Repository
use App\Shared\Core\ValueObject\Message;

final readonly class AnotherExampleDetailInfoFactory
{
    public function __construct(
        private ExampleRepositoryInterface $exampleRepository,
    ) {
    }

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
                throw new NotFoundException(translate: Message::create(key: 'resource.not_found', params: ['resource' => 'example', 'field' => 'id', 'value' => $exampleId]));
            }
        }

        return new AnotherExampleDetailInfo(
            example: $example?->toPersistence(),
        );
    }
}
