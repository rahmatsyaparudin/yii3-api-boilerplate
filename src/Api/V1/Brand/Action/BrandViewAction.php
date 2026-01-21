<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandViewAction
{
    public function __construct(
        private BrandApplicationService $brandApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        if (!$id) {
            return $this->responseFactory->fail(
                message: 'Brand ID is required',
                httpCode: Status::NOT_FOUND
            );
        }

        // Use BrandApplicationService for proper DDD architecture
        $brand = $this->brandApplicationService->get($id);

        return $this->responseFactory->success(
            data: $brand->toArray(),
            message: 'resource.details_retrieved',
            params: [
                'resource' => 'Brand'
            ]
        );
    }
}
