<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;
use App\Application\Brand\Command\UpdateBrandCommand;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Brand\Validation\BrandInputValidator;

// Shared Layer
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Third Party Libraries
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Brand Update API Action
 */
final class BrandUpdateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'lock_version'];

    public function __construct(
        private BrandInputValidator $brandInputValidator,
        private BrandApplicationService $brandApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $payload = $request->getAttribute('payload');
        
        $id = $currentRoute->getArgument('id');

        $resource = $this->brandApplicationService->getResource();

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

        $this->brandInputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $command = new UpdateBrandCommand(
            id: (int) $id,
            name: $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            syncMdb: $params->get('sync_mdb'),
            lockVersion: $params->get('lock_version'),
        );

        $brandResponse = $this->brandApplicationService->update(
            id: (int) $id,
            command: $command
        );

        return $this->responseFactory->success(
            data: $brandResponse->toArray(),
            translate: new Message(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
