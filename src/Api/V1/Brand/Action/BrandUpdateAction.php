<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;

// Shared Layer
use App\Shared\Validation\RequestValidator;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Third Party Libraries
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Brand Update API Action
 * 
 * Uses BrandApplicationService for proper DDD architecture
 */
final class BrandUpdateAction
{
    private const RESOURCE = 'Brand';
    private const ALLOWED_KEYS = ['name', 'status'];

    public function __construct(
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

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: new Message(
                    key: 'route.parameter_missing',
                    params: [
                        'resource' => self::RESOURCE,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $params = RequestValidator::onlyAllowed(
            filters: $payload->getRawParams(), 
            allowedKeys: self::ALLOWED_KEYS
        );

        // Use BrandApplicationService for proper DDD architecture
        $brand = $this->brandApplicationService->update((int) $id, $params->toArray());

        return $this->responseFactory->success(
            data: $brand->toArray(),
            translate: new Message(
                key: 'resource.updated',
                params: [
                    'resource' => self::RESOURCE,
                ]
            ),
        );
    }
}
