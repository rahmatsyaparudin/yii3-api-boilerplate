<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;

// Shared Layer
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Third Party Libraries
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final readonly class BrandDeleteAction
{
    private const ALLOWED_KEYS = ['id'];

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
                httpCode: Status::NOT_FOUND,
            );
        }

        $brandResponse = $this->brandApplicationService->delete(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $brandResponse->toArray(),
            translate: new Message(
                key: 'resource.deleted',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
