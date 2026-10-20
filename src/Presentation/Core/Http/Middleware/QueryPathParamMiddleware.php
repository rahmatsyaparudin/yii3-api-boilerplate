<?php

declare(strict_types=1);

namespace App\Presentation\Core\Http\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Strips path-like keys from the query string.
 *
 * Some web server rewrite configs (e.g. nginx try_files with PATH_INFO
 * passthrough) turn the request path into a query parameter:
 *
 * The bogus key is merged into request parameters and fails strict
 * validation (RawParams::onlyAllowed) on every endpoint that uses it.
 * Legitimate parameter names never start with '/', so they are dropped.
 */
final class QueryPathParamMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $query = $request->getQueryParams();
        $filtered = [];

        foreach ($query as $key => $value) {
            if (!\is_string($key) || !\str_starts_with($key, '/')) {
                $filtered[$key] = $value;
            }
        }

        if (\count($filtered) !== \count($query)) {
            $request = $request->withQueryParams($filtered);
        }

        return $handler->handle($request);
    }
}
