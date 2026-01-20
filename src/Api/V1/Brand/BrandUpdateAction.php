<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\Application\BrandInput;
use App\Domain\Brand\Service\BrandService;
use App\Domain\Brand\Application\BrandInputValidator;
use App\Domain\Brand\Application\BrandValidator;
use App\Shared\Validation\ValidationContext;
use App\Shared\Constants\StatusEnum;
use App\Shared\Helper\FilterHelper;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

final readonly class BrandUpdateAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'status'];

    public function __construct(
        private BrandService $service,
        private ResponseFactory $responseFactory,
        private BrandInputValidator $inputValidator,
        private BrandValidator $brandValidator,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
    ): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams|null $payload */
        $payload = $request->getAttribute('payload');
        
        $id = $currentRoute->getArgument('id');

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: [
                    'key' => 'route.parameter_missing',
                    'params' => [
                        'resource' => 'Brand',
                        'parameter' => 'id',
                    ]
                ],
                httpCode: Status::NOT_FOUND
            );
        }

        $params = FilterHelper::onlyAllowed(
            filters: $payload->getRawParams(), 
            allowedKeys: self::ALLOWED_KEYS
        )->with('id', $id);

        $this->inputValidator->validate(
            ValidationContext::UPDATE,
            $params
        );

        $this->brandValidator->validateForUpdate(
            data: $params
        );

        $input = new BrandInput(
            name: $params->name,
            status: $params->status ? (int) $params->status : null,
            detailInfo: [],
        );

        $brand = $this->service->update(
            id: intval($id), 
            input: $input
        );

        return $this->responseFactory->success(
            data: $brand->toArray(),
            translate: [
                'key' => 'resource.updated',
                'params' => [
                    'resource' => 'Brand',
                ]
            ]
        );
    }
}
