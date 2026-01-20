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
use App\Shared\Request\RawParams;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

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

        $params = new RawParams([
            'id' => $id,
        ]);

        $this->inputValidator->validate(
            ValidationContext::DELETE,
            $params
        );

        // Validate using entity business rules
        $this->brandValidator->validateForDelete($params);

        // Perform soft delete by updating status
        $brandData = $this->service->delete(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $brandData,
            translate: [
                'key' => 'resource.deleted',
                'params' => [
                    'resource' => 'Brand',
                ]
            ]
        );
    }
}
