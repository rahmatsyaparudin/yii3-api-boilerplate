<?php

declare(strict_types=1);

namespace App\Api\V1\--help\Action;

// Application Layer
use App\Application\--help\--helpApplicationService;
use App\Application\Shared\Factory\SearchCriteriaFactory;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\--help\Validation\--helpInputValidator;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Dto\SearchCriteria;
use App\Shared\Request\RequestParams;
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class --helpDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'status'];
    private const ALLOWED_SORT = [
        'id' => 'id', 
        'name' => 'name', 
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private --helpInputValidator $--helpInputValidator,
        private --helpApplicationService $--helpApplicationService,
        private ResponseFactory $responseFactory,
        private \Yiisoft\Db\Connection\ConnectionInterface $db,
    ) {
    }   

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $filter = $payload->getFilter()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('status', RecordStatus::DRAFT->value);

        $this->--helpInputValidator->validate(
            data: $filter,
            context: ValidationContext::SEARCH,
        );

        // $this->db->getSchema()->getTableSchema('--help', true);

        $criteria = $this->factory->createFromRequest($payload, self::ALLOWED_SORT);

        $resource = $this->--helpApplicationService->getResource();
        $result = $this->--helpApplicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: new Message(
                key: 'resource.list_retrieved', 
                params: [
                    'resource' => $resource
                ]
            ),
            meta: $result->getMeta(),
        );
    }
}
