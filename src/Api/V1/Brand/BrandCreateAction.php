<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\BrandService;
use App\Domain\Brand\BrandValidator;
use App\Shared\Constants\StatusEnum;
use App\Shared\Helper\FilterHelper;
use App\Shared\Validation\ValidationContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandCreateAction
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

        $params           = FilterHelper::onlyAllowed($request->getParams(), ['name']);
        $params['status'] = StatusEnum::DRAFT->value;

        $this->brandValidator->validate(
            ValidationContext::CREATE,
            $params
        );

        $brand = $this->service->create(
            name: $params['name'],
            status: $params['status']
        );

        return $this->responseFactory->success(
            data: $brand,
            translate: [
                'key' => 'created',
                'params' => [
                    'resource' => 'Brand',
                ],
            ],
        );
    }
}
