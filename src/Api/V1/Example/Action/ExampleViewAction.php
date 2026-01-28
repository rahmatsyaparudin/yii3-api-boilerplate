<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

// Application Layer
use App\Application\Example\ExampleApplicationService;
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

final readonly class ExampleViewAction
{
    public function __construct(
        private ExampleApplicationService $exampleApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface
    {
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

        $exampleResponse = $this->exampleApplicationService->get((int) $id);

        return $this->responseFactory->success(
            data: $exampleResponse->toArray(),
            translate: new Message(
                key: 'resource.details_retrieved',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
