<?php

declare(strict_types=1);

namespace App\Api\V1\System\ServiceToken\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\System\ServiceToken\Validation\ServiceTokenInputValidator;
// API Layer
use App\Application\System\ServiceToken\ServiceTokenApplicationService;
// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Service Token Update API Action.
 *
 * Supports `regenerate: true` — rotates the secret; the new plaintext
 * is returned once in `data.token` and the old bearer dies instantly.
 */
final class ServiceTokenUpdateAction
{
    private const ALLOWED_KEYS = ['username', 'name', 'abilities', 'whitelist_host', 'expires_at', 'regenerate', 'status'];

    public function __construct(
        private ServiceTokenInputValidator $inputValidator,
        private ServiceTokenApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute,
    ): ResponseInterface {
        /** @var \App\Shared\Core\Request\RequestParams|null $payload */
        $id       = $currentRoute->getArgument('id');
        $payload  = $request->getAttribute('payload');
        $resource = $this->applicationService->getResource();

        if ($id === null) {
            return $this->responseFactory->fail(
                translate: Message::create(
                    key: 'route.parameter_missing',
                    params: [
                        'resource'  => $resource,
                        'parameter' => 'id',
                    ]
                ),
                httpCode: Status::NOT_FOUND
            );
        }

        $payload->ensureExists(
            resource: $resource
        );

        $params = $payload->getRawParams()
            ->onlyAllowed(
                allowedKeys: self::ALLOWED_KEYS
            )->with('id', $id)
            ->sanitize();

        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::UPDATE,
        );

        $response = $this->applicationService->update(
            id: (int) $id,
            input: $params->toArray()
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.updated',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
