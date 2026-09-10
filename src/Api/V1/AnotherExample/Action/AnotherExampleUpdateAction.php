<?php

declare(strict_types=1);

namespace App\Api\V1\AnotherExample\Action;

// Application Layer
use App\Application\AnotherExample\AnotherExampleApplicationService;
use App\Application\AnotherExample\Command\UpdateAnotherExampleCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\AnotherExample\Validation\AnotherExampleInputValidator;

// Shared Layer
use App\Shared\Context\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * AnotherExample Update API Action
 */
final class AnotherExampleUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'example_id', 'lock_version', 'detail_info'];

    public function __construct(
        private AnotherExampleInputValidator $inputValidator,
        private AnotherExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $id = $currentRoute->getArgument('id');
        $payload = $request->getAttribute('payload');
        $resource = $this->applicationService->getResource();

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => $resource,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $payload->ensureExists(
            resource: $resource
        );

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = UpdateAnotherExampleCommand::create(
            id: (int) $id,
            name: $params->get('name'),
            status: $params->get('status'),
            exampleId: (int) $params->get('example_id'),
            detailInfo: $params->get('detail_info'),
            lockVersion: $params->get('lock_version'),
        );

        $response = $this->applicationService->update(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
