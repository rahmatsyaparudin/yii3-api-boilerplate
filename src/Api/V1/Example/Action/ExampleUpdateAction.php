<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

// Application Layer
use App\Application\Example\ExampleApplicationService;
use App\Application\Example\Command\UpdateExampleCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Example\Validation\ExampleInputValidator;

// Shared Layer
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Example Update API Action
 */
final class ExampleUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'lock_version'];

    public function __construct(
        private ExampleInputValidator $exampleInputValidator,
        private ExampleApplicationService $exampleApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $payload = $request->getAttribute('payload');
        
        $id = $currentRoute->getArgument('id');

        $resource = $this->exampleApplicationService->getResource();

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: new Message(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => $resource,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->exampleInputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = new UpdateExampleCommand(
            id: (int) $id,
            name: $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            syncMdb: $params->get('sync_mdb'),
            lockVersion: $params->get('lock_version'),
        );

        $exampleResponse = $this->exampleApplicationService->update(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $exampleResponse->toArray(),
            translate: new Message(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
