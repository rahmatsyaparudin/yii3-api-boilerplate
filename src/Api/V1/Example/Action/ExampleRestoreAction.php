<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

use App\Application\Brand\BrandApplicationService;
use App\Api\Shared\ResponseFactory;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

// Yiisoft Interfaces
use Yiisoft\Router\CurrentRoute;

/**
 * Brand Restore Action
 * 
 * Restores a soft-deleted brand back to active status
 */
final readonly class BrandRestoreAction
{
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
                status: 400
            );
        }

        $brandResponse = $this->brandApplicationService->restore(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $brandResponse->toArray(),
            translate: new Message(
                key: 'resource.restored',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
