<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\Service\BrandService;
use App\Domain\Brand\Application\BrandInputValidator;
use App\Domain\Brand\Application\BrandValidator;
use App\Shared\Helper\FilterHelper;
use App\Shared\Request\RawParams;
use App\Shared\Validation\ValidationContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandDataAction
{
    public function __construct(
        private BrandService $service,
        private ResponseFactory $responseFactory,
        private BrandInputValidator $inputValidator,
        private BrandValidator $brandValidator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $filter = $payload->getFilter(); 
        $pagination = $payload->getPagination();
        $sort = $payload->getSort();


        // Input validation (format validation)
        $this->inputValidator->validate(
            ValidationContext::SEARCH,
            $filter
        );

        // Business validation
        $validationPayload = new RawParams([
            'filter' => $filter->toArray(),
            'pagination' => $pagination->toArray(),
            'sort' => $sort->toArray(),
        ]);
        
        $this->brandValidator->validateForSearch(
            data: $validationPayload
        );

        $brands = $this->service->list(
            params: $filter,
            pagination: $pagination,
            sort: $sort,
        );

        return $this->responseFactory->success(
            data: $brands,
            translate: [
                'key' => 'success',
                'params' => [
                    'resource' => 'Brand'
                ]
            ],
            meta: [
                'pagination' => [
                    'page' => $pagination->page,
                    'page_size' => $pagination->page_size,
                    'total' => $brands['total'] ?? 0,
                ],
                'sort' => [
                    'by' => $sort->by,
                    'dir' => $sort->dir,
                ],
                'filter' => $filter->toArray(),
            ],
        );
    }
}
