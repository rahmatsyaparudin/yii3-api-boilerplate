<?php

declare(strict_types=1);

namespace App\Api\V1\Product\Action;

// Application Layer
use App\Application\Product\ProductApplicationService;

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
 * Product Restore Action
 * 
 * Restores a soft-deleted product back to active status
 */
final readonly class ProductRestoreAction
{
    public function __construct(
        private ProductApplicationService $productApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
    ): ResponseInterface
    {
        $id = $currentRoute->getArgument('id');

        $resource = $this->productApplicationService->getResource();
        
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

        $productResponse = $this->productApplicationService->restore(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $productResponse->toArray(),
            translate: new Message(
                key: 'resource.restored',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
