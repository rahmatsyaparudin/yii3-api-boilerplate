<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
// API Layer
use App\Api\V1\Example\Validation\ExampleInputValidator;
use App\Application\Example\Command\CreateExampleCommand;
use App\Application\Example\ExampleApplicationService;
// Shared Layer
use App\Shared\Core\Context\ValidationContext;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ExampleCreateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __construct(
        private ExampleInputValidator $inputValidator,
        private ExampleApplicationService $applicationService,
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

        $command = CreateExampleCommand::create(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info')
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
