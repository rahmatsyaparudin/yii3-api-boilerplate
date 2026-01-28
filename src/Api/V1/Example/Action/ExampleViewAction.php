<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

// Application Layer
use App\Application\Example\ExampleApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class ExampleViewAction
{
    public function __construct(
        private ExampleApplicationService $exampleApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        if (!$id) {
            return $this->responseFactory->fail(
                message: 'Example ID is required',
                httpCode: Status::NOT_FOUND
            );
        }

        // Use ExampleApplicationService for proper DDD architecture
        $example = $this->exampleApplicationService->get($id);

        return $this->responseFactory->success(
            data: $example->toArray(),
            message: 'resource.details_retrieved',
            params: [
                'resource' => 'Example'
            ]
        );
    }
}
