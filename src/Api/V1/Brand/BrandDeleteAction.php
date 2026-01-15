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

final readonly class BrandDeleteAction
{
    private const ALLOWED_KEYS = ['id'];
    
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

        // if ($payload === null) {
        //     return $this->responseFactory->fail('Request parameters not found', httpCode: Status::BAD_REQUEST);
        // }

        $params = FilterHelper::onlyAllowed($payload->getRawParams(), self::ALLOWED_KEYS);

        $this->inputValidator->validate(
            ValidationContext::DELETE,
            $params
        );

        $this->brandValidator->validateForUpdate(
            data: $params
        );

        $brand = $this->service->update(
            name: $params->name,
            status: $params->status,
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
