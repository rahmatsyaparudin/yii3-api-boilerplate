<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

// Application Layer
use App\Application\Example\ExampleApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Example\Validation\ExampleInputValidator;
use App\Application\Example\Command\CreateExampleCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ExampleCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __construct(
        private ExampleInputValidator $exampleInputValidator,
        private ExampleApplicationService $exampleApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->exampleInputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = new CreateExampleCommand(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            syncMdb: $params->get('sync_mdb')
        );

        $resource = $this->exampleApplicationService->getResource();
        $exampleResponse = $this->exampleApplicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $exampleResponse->toArray(),
            translate: new Message(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
