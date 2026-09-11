<?php

declare(strict_types=1);

namespace App\Api\V1\AnotherExample\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
// API Layer
use App\Api\V1\AnotherExample\Validation\AnotherExampleInputValidator;
use App\Application\AnotherExample\AnotherExampleApplicationService;
use App\Application\AnotherExample\Command\CreateAnotherExampleCommand;
// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class AnotherExampleCreateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'example_id', 'sync_mdb', 'origin_id', 'sync_flag', 'detail_info'];

    public function __construct(
        private AnotherExampleInputValidator $inputValidator,
        private AnotherExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Core\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateAnotherExampleCommand::create(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            exampleId: (int) $params->get('example_id'),
            detailInfo: $params->get('detail_info'),
            originId: $params->get('origin_id'),
            syncFlag: $params->get('sync_flag'),
        );

        $resource = $this->applicationService->getResource();
        $response = $this->applicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource,
                ]
            )
        );
    }
}
