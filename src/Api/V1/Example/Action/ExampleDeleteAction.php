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

// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final readonly class ExampleDeleteAction
{
    private const ALLOWED_KEYS = ['id', 'lock_version'];

    public function __construct(
        private ExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
    ): ResponseInterface
    {
        $id = $currentRoute->getArgument('id');
        $parsedBody = $request->getParsedBody();

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
                httpCode: Status::NOT_FOUND,
            );
        }

        $response = $this->applicationService->delete(
            id: (int) $id,
            lockVersion: isset($parsedBody['lock_version']) ? (int) $parsedBody['lock_version'] : null,
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.deleted',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
