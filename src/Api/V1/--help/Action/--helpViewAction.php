<?php

declare(strict_types=1);

namespace App\Api\V1\--help\Action;

// Application Layer
use App\Application\--help\--helpApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;

// API Layer
use App\Api\Shared\ResponseFactory;

// Shared Layer
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final readonly class --helpViewAction
{
    public function __construct(
        private --helpApplicationService $--helpApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
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

        $--helpResponse = $this->--helpApplicationService->get((int) $id);

        return $this->responseFactory->success(
            data: $--helpResponse->toArray(),
            translate: new Message(
                key: 'resource.details_retrieved',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
