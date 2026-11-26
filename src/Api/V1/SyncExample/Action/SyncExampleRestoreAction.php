<?php

declare(strict_types=1);

namespace App\Api\V1\SyncExample\Action;

// Application Layer
use App\Api\Shared\ResponseFactory;
// API Layer
use App\Application\SyncExample\SyncExampleApplicationService;
// Shared Layer
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
// Vendor Layer
use Yiisoft\Router\CurrentRoute;

/**
 * SyncExample Restore Action.
 *
 * Restores a soft-deleted syncexample back to active status
 */
final readonly class SyncExampleRestoreAction
{
    public function __construct(
        private SyncExampleApplicationService $applicationService,
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
                httpCode: 400
            );
        }

        $response = $this->applicationService->restore(
            id: (int) $id,
        );

        return $this->responseFactory->success(
            data: $response->toArray(),
            translate: Message::create(
                key: 'resource.restored',
                params: [
                    'resource' => $resource,
                ]
            ),
        );
    }
}
