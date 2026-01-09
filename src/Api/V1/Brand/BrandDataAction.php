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
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $params */
        $params = $request->getAttribute('params');

        $filters = FilterHelper::onlyAllowed($params->getFilters(), ['id', 'name', 'status', 'sync_mdb']);

        $this->brandValidator->validate(
            ValidationContext::SEARCH,
            $filters
        );

        $brands = $this->service->list(
            $params->getPageSize(),
            $params->getOffset(),
            $filters,
            $params->getSortBy(),
            $params->getSortDir(),
        );

        return $this->responseFactory->success(
            $brands,
            messageKey: 'success',
            meta: $params->getMeta(),
        );
    }
}
