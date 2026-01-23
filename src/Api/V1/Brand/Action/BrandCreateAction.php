<?php

declare(strict_types=1);

namespace App\Api\V1\Brand\Action;

// Application Layer
use App\Application\Brand\BrandApplicationService;

// API Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\Brand\Validation\BrandInputValidator;
use App\Application\Brand\Command\CreateBrandCommand;

// Shared Layer
use App\Shared\Enums\RecordStatus;
use App\Shared\Request\RawParams;
use App\Shared\Validation\ValidationContext;
use App\Shared\ValueObject\Message;
use App\Shared\Security\InputSanitizer;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class BrandCreateAction 
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __construct(
        private BrandInputValidator $brandInputValidator,
        private BrandApplicationService $brandApplicationService,
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

        $this->brandInputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = new CreateBrandCommand(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info'),
            syncMdb: $params->get('sync_mdb')
        );

        $resource = $this->brandApplicationService->getResource();
        $brandResponse = $this->brandApplicationService->create(command: $command);

        return $this->responseFactory->success(
            data: $brandResponse->toArray(),
            translate: new Message(
                key: 'resource.created',
                params: [
                    'resource' => $resource
                ]
            )
        );
    }
}
