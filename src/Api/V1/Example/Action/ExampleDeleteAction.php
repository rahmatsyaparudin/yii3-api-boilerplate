<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

// Application Layer
use App\Application\Example\ExampleApplicationService;

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

final readonly class ExampleDeleteAction
{
    private const ALLOWED_KEYS = ['id'];

    public function __construct(
        private ExampleApplicationService $exampleApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
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
                httpCode: Status::NOT_FOUND,
            );
        }

        $exampleResponse = $this->exampleApplicationService->delete(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $exampleResponse->toArray(),
            translate: new Message(
                key: 'resource.deleted',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
