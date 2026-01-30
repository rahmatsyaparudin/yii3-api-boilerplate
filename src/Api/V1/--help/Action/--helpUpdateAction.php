<?php

declare(strict_types=1);

namespace App\Api\V1\--help\Action;

// Application Layer
use App\Application\--help\--helpApplicationService;
use App\Application\--help\Command\Update--helpCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\--help\Validation\--helpInputValidator;

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
 * --help Update API Action
 */
final class --helpUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'lock_version'];

    public function __construct(
        private --helpInputValidator $--helpInputValidator,
        private --helpApplicationService $--helpApplicationService,
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

        $resource = $this->--helpApplicationService->getResource();

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

        if ($payload === null) {
            return $this->responseFactory->fail(
                translate: new Message(
                    key: 'validation.invalid_payload',
                    params: [
                        'resource' => $resource,
                    ]
                ),
                httpCode: Status::BAD_REQUEST
            );
        }

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->--helpInputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = new Update--helpCommand(
            id: (int) $id,
            name: $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            syncMdb: $params->get('sync_mdb'),
            lockVersion: $params->get('lock_version'),
        );

        $--helpResponse = $this->--helpApplicationService->update(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $--helpResponse->toArray(),
            translate: new Message(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
