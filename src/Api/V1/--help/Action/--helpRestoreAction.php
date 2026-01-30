<?php

declare(strict_types=1);

namespace App\Api\V1\--help\Action;

// Application Layer
use App\Application\--help\--helpApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;

// Shared Layer
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Vendor Layer
use Yiisoft\Router\CurrentRoute;

/**
 * --help Restore Action
 * 
 * Restores a soft-deleted --help back to active status
 */
final readonly class --helpRestoreAction
{
    public function __construct(
        private --helpApplicationService $--helpApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
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
                httpCode: 400
            );
        }

        $--helpResponse = $this->--helpApplicationService->restore(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $--helpResponse->toArray(),
            translate: new Message(
                key: 'resource.restored',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
