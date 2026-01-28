<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Action;

use App\Application\Example\ExampleApplicationService;
use App\Api\Shared\ResponseFactory;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Yiisoft Interfaces
use Yiisoft\Router\CurrentRoute;

/**
 * Example Restore Action
 * 
 * Restores a soft-deleted example back to active status
 */
final readonly class ExampleRestoreAction
{
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
                status: 400
            );
        }

        $exampleResponse = $this->exampleApplicationService->restore(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $exampleResponse->toArray(),
            translate: new Message(
                key: 'resource.restored',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
