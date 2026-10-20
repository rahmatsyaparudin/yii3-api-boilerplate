<?php

declare(strict_types=1);

namespace App\Api\V1\System\Token\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
use App\Api\V1\System\Token\Validation\ServiceTokenInputValidator;
// API Layer
use App\Application\System\Token\ServiceTokenApplicationService;
// Shared Layer
use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Request\RawParams;
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
// Vendor Layer
use Yiisoft\Http\Status;
use Yiisoft\Router\CurrentRoute;

/**
 * Service Token Delete API Action.
 *
 * Soft delete — the bearer stops working on the very next request;
 * the row stays for audit.
 */
final readonly class ServiceTokenDeleteAction
{
    public function __construct(
        private ServiceTokenInputValidator $inputValidator,
        private ServiceTokenApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(
        ServerRequestInterface $request,
        CurrentRoute $currentRoute
    ): ResponseInterface {
        $id       = $currentRoute->getArgument('id');
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
                httpCode: Status::NOT_FOUND,
            );
        }

        $this->inputValidator->validate(
            data: new RawParams(['id' => (int) $id]),
            context: ValidationContext::DELETE,
        );

        $response = $this->applicationService->delete(
            id: (int) $id
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.deleted',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
