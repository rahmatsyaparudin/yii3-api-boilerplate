<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\Service\BrandService;
use App\Domain\Brand\Application\BrandInputValidator;
use App\Domain\Brand\Application\BrandValidator;
use App\Shared\Validation\ValidationContext;
use App\Shared\Constants\StatusEnum;
use App\Shared\Helper\FilterHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;

final readonly class BrandUpdateAction
{
    private const ALLOWED_KEYS = ['name'];

    public function __construct(
        private BrandService $service,
        private ResponseFactory $responseFactory,
        private BrandInputValidator $inputValidator,
        private BrandValidator $brandValidator,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $payload = $request->getAttribute('payload');

        if ($payload === null) {
            return $this->responseFactory->fail('Request parameters not found', httpCode: Status::BAD_REQUEST);
        }

        $params = FilterHelper::onlyAllowed($payload->getRawParams(), ['id', 'name', 'status']);

        $this->inputValidator->validate(
            ValidationContext::UPDATE,
            $params
        );

        $this->brandValidator->validateForUpdate(
            data: $params
        );

        $brand = $this->service->update(
            name: $params->name ?? null,
            status: $params->status ?? null,
            id: (int) $params->id,
        );

        return $this->responseFactory->success(
            data: $brand,
            translate: [
                'key' => 'resource.updated',
                'params' => [
                    'resource' => 'Brand',
                ]
            ]
        );
    }
}
