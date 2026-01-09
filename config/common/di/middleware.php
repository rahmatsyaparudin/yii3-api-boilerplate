<?php
declare(strict_types=1);

use App\Shared\Middleware\RequestParamsMiddleware;
use App\Shared\Middleware\CorsMiddleware;
use Psr\Http\Message\ResponseFactoryInterface;

/** @var array $params */

return [
    // Middleware global untuk semua route
    RequestParamsMiddleware::class => static function () use ($params) {
        $pagination = $params['app/pagination'] ?? [];

        return new RequestParamsMiddleware(
            defaultPageSize: (int) ($pagination['defaultPageSize'] ?? 50),
            maxPageSize: (int) ($pagination['maxPageSize'] ?? 200),
        );
    },

    CorsMiddleware::class => static function (ResponseFactoryInterface $responseFactory) use ($params) {
        return new CorsMiddleware($params['app/cors'], $responseFactory);
    },
];
