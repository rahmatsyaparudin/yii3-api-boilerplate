<?php

declare(strict_types=1);

namespace App\Api\V1\System\Token\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
// API Layer
use App\Api\V1\System\Token\Validation\ServiceTokenInputValidator;
use App\Application\System\Token\ServiceTokenApplicationService;
// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class ServiceTokenCreateAction
{
    private const ALLOWED_KEYS = ['username', 'name', 'abilities', 'whitelist_host', 'expires_at'];

    public function __construct(
        private ServiceTokenInputValidator $inputValidator,
        private ServiceTokenApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var \App\Shared\Core\Request\RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $resource = $this->applicationService->getResource();
        $response = $this->applicationService->create(
            input: $params->toArray()
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.created',
                params: [
                    'resource' => $resource,
                ]
            )
        );
    }
}
