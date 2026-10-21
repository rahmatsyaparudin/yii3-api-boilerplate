<?php

declare(strict_types=1);

namespace App\Api\V1\System\ServiceToken\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\System\ServiceToken\Validation\ServiceTokenInputValidator;
// API Layer
use App\Application\Shared\Core\Factory\SearchCriteriaFactory;
use App\Application\System\ServiceToken\ServiceTokenApplicationService;
// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Request\RequestParams;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ServiceTokenDataAction
{
    private const ALLOWED_KEYS = ['id', 'user_id', 'username', 'name', 'status'];
    private const ALLOWED_SORT = [
        'id'           => 'id',
        'user_id'      => 'user_id',
        'username'     => 'username',
        'name'         => 'name',
        'last_used_at' => 'last_used_at',
        'expires_at'   => 'expires_at',
        'created_at'   => 'created_at',
        'status'       => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private ServiceTokenInputValidator $inputValidator,
        private ServiceTokenApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $filter = $payload->getFilter()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            );

        $this->inputValidator->validate(
            data: $filter,
            context: ValidationContext::SEARCH,
        );

        $criteria = $this->factory->createFromRequest(
            params: $payload,
            allowedSort: self::ALLOWED_SORT
        );

        $resource = $this->applicationService->getResource();
        $result   = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: Message::create(
                key: 'resource.list_retrieved',
                params: [
                    'resource' => $resource,
                ]
            ),
            meta: $result->getMeta(),
        );
    }
}
