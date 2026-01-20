<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\Service\BrandService;
use App\Domain\Brand\Application\BrandInput;
use App\Domain\Brand\Application\BrandInputValidator;
use App\Domain\Brand\Application\BrandValidator;
use App\Domain\Shared\ValueObject\Status;
use App\Shared\Constants\StatusEnum;
use App\Shared\Helper\FilterHelper;
use App\Shared\Request\RawParams;
use App\Shared\Validation\ValidationContext;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class BrandCreateAction
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
        /** @var \App\Shared\Request\RequestParams $request */
        $request = $request->getAttribute('payload');

        $params = FilterHelper::onlyAllowed(
            filters: $request->getRawParams(),
            allowedKeys: self::ALLOWED_KEYS
        )->with('status', StatusEnum::DRAFT->value);

        $this->inputValidator->validate(
            context: ValidationContext::CREATE,
            data: $params
        );

        $this->brandValidator->validateForCreation(
            data: $params
        );

        $input = new BrandInput(
            name: $params->name,
            status: $params->status,
            detailInfo: [],
            syncMdb: null
        );

        $brand = $this->service->create($input);

        return $this->responseFactory->success(
            data: $brand->toArray(),
            translate: [
                'key' => 'resource.created',
                'params' => [
                    'resource' => 'Brand'
                ]
            ]
        );
    }
}
