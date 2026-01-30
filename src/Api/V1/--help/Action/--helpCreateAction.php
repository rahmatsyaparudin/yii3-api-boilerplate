<?php

declare(strict_types=1);

namespace App\Api\V1\--help\Action;

// Application Layer
use App\Application\--help\--helpApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\--help\Validation\--helpInputValidator;
use App\Application\--help\Command\Create--helpCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class --helpCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __construct(
        private --helpInputValidator $--helpInputValidator,
        private --helpApplicationService $--helpApplicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        $this->--helpInputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = new Create--helpCommand(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            syncMdb: $params->get('sync_mdb')
        );

        $resource = $this->--helpApplicationService->getResource();
        $--helpResponse = $this->--helpApplicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $--helpResponse->toArray(),
            translate: new Message(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
