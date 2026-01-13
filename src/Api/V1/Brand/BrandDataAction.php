<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\BrandService;
use App\Domain\Brand\BrandValidator;
use App\Shared\Helper\FilterHelper;
use App\Shared\Validation\ValidationContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandDataAction
{
    public function __construct(
        private BrandService $service,
        private ResponseFactory $responseFactory,
        private BrandValidator $brandValidator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $params */
        $request = $request->getAttribute('params');

        $params = FilterHelper::onlyAllowed($request->getParams(), ['id', 'name', 'status', 'sync_mdb']);

        $this->brandValidator->validate(
            ValidationContext::SEARCH,
            $params
        );

        $brands = $this->service->list(
            $request->getPageSize(),
            $request->getOffset(),
            $params,
            $request->getSortBy(),
            $request->getSortDir(),
        );

        return $this->responseFactory->success(
            $brands,
            messageKey: 'success.list_retrieved',
            params: ['resource' => 'Brand'],
            meta: $request->getMeta(),
        );
    }
}
