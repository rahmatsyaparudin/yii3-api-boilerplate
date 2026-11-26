<?php

declare(strict_types=1);

namespace App\Api\V1\SyncExample\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\SyncExample\Validation\SyncExampleInputValidator;
// API Layer
use App\Application\Shared\Core\Factory\SearchCriteriaFactory;
use App\Application\SyncExample\SyncExampleApplicationService;
// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\Request\RequestParams;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class SyncExampleDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'status', 'sync_mdb', 'sync_flag', 'origin_id', 'source_store_id', 'uuid', 'doc_role'];
    private const ALLOWED_SORT = [
        'id'     => 'id',
        'name'   => 'name',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private SyncExampleInputValidator $inputValidator,
        private SyncExampleApplicationService $applicationService,
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
            )->with('status', RecordStatus::DRAFT->value);

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
