<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\Shared\Presenter\AsIsPresenter;
use App\Api\V1\Brand\Validation\BrandInputValidator;

// Shared Layer
use App\Shared\Constants\StatusEnum;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Request\RequestParams;
use App\Shared\Validation\RequestValidator;
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BrandDataAction
{
    private const RESOURCE = 'Brand';
    private const ALLOWED_KEYS = ['id', 'name', 'status'];
    private const ALLOWED_SORT = [
        'id' => 'id', 
        'name' => 'name', 
        'status' => 'status',
    ];

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

        $filter = $payload->getFilter();
        $pagination = $payload->getPagination();
        $sort = $payload->getSort();

        $params = $this->requestValidator->onlyAllowed(
            filters: $filter,
            allowedKeys: self::ALLOWED_KEYS
        );

        $this->brandInputValidator->validate(
            data: $params,
            context: ValidationContext::SEARCH,
        );

        $criteria = SearchCriteria::fromPayload($payload, self::ALLOWED_SORT);

        $result = $this->brandApplicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: new Message(
                key: 'resource.list_retrieved', 
                params: [
                    'resource' => self::RESOURCE
                ]
            ),
            meta: $result->getMeta(),
        );
    }
}
