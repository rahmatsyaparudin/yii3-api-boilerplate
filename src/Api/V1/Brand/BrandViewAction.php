<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\BrandService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandViewAction
{
    public function __construct(
        private BrandService $service,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        if (!$id) {
            return $this->responseFactory->fail('Brand ID is required');
        }

        $brand = $this->service->get($id);

        return $this->responseFactory->success($brand, messageKey: 'success.details_retrieved', params: ['resource' => 'Brand']);
    }
}
