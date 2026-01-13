<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\BrandService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandDeleteAction
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

        $this->service->delete($id);

        return $this->responseFactory->success(null, messageKey: 'success.deleted');
    }
}
