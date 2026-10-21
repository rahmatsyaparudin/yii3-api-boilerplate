<?php

declare(strict_types=1);

namespace App\Api\V1\System\Cache\Action;

// API Layer
use App\Api\Shared\ResponseFactory;
// Application Layer
use App\Application\System\Cache\CacheApplicationService;
// Shared Layer
use App\Shared\Core\ValueObject\Message;
// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Cache Purge API Action.
 *
 * Clears every cache store the app may have populated — PSR-16 cache,
 * Yii cache, FileCache (incl. DB schema cache), Redis when enabled,
 * OPcache, and realpath/stat cache. Per-store outcome is reported in
 * `data.results`; a store that is absent or disabled reports `skipped`,
 * never fails the request.
 */
final readonly class CachePurgeAction
{
    public function __construct(
        private CacheApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        return $this->responseFactory->success(
            data: $this->applicationService->purgeAll(),
            translate: Message::create(
                key: 'system.cache_cleared',
            ),
        );
    }
}
