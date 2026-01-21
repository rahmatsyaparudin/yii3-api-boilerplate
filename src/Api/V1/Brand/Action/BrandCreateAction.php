<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Brand\Validation\BrandInputValidator;

// Shared Layer
use App\Shared\Constants\StatusEnum;
use App\Shared\Request\RawParams;
use App\Shared\Validation\RequestValidator;
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BrandCreateAction 
{
    private const RESOURCE = 'Brand';
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __construct(
        private RequestValidator $requestValidator,
        private BrandInputValidator $brandInputValidator,
        private BrandApplicationService $brandApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $rawParams = $payload->getRawParams();

        $params = $this->requestValidator->onlyAllowed(
            filters: $rawParams,
            allowedKeys: self::ALLOWED_KEYS
        )->with('status', StatusEnum::DRAFT->value);

        $this->brandInputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $brand = $this->brandApplicationService->create($params->toArray());

        return $this->responseFactory->success(
            data: $brand->toArray(),
            translate: new Message(
                key: 'resource.created',
                params: [
                    'resource' => self::RESOURCE
                ]
            )
        );
    }
}
