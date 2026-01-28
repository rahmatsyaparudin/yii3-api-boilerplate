<?php

declare(strict_types=1);

// Infrastructure Layer
use App\Infrastructure\Monitoring\ErrorMonitoringMiddleware;
use App\Infrastructure\Monitoring\MetricsMiddleware;
use App\Infrastructure\Monitoring\RequestIdMiddleware;
use App\Infrastructure\Monitoring\StructuredLoggingMiddleware;
use App\Infrastructure\Security\AccessChecker;
use App\Infrastructure\Security\CurrentUser;
use App\Infrastructure\Security\HstsMiddleware;

// Shared Layer
use App\Shared\Middleware\AccessMiddleware;
use App\Shared\Middleware\CorsMiddleware;
use App\Shared\Middleware\RateLimitMiddleware;
use App\Shared\Middleware\RequestParamsMiddleware;
use App\Shared\Middleware\SecureHeadersMiddleware;

// PSR Interfaces
use Psr\Http\Message\ResponseFactoryInterface;

// Vendor Layer
use Yiisoft\Router\FastRoute\UrlMatcher;

// @var array $params

return [
    // Middleware global untuk semua route
    RequestParamsMiddleware::class => static function () use ($params) {
        $pagination = $params['app/pagination'] ?? [];

        return new RequestParamsMiddleware(
            defaultPageSize: (int) ($pagination['defaultPageSize'] ?? 50),
            maxPageSize: (int) ($pagination['maxPageSize'] ?? 200),
        );
    },

    CorsMiddleware::class => static fn (ResponseFactoryInterface $responseFactory) => new CorsMiddleware($params['app/cors'], $responseFactory),

    RateLimitMiddleware::class => static function () use ($params) {
        $rateLimit = $params['app/rateLimit'] ?? [];

        return new RateLimitMiddleware(
            maxRequests: (int) ($rateLimit['maxRequests'] ?? 100),
            windowSize: (int) ($rateLimit['windowSize'] ?? 60)
        );
    },
    SecureHeadersMiddleware::class => static function () use ($params) {
        $secureHeaders = $params['app/secureHeaders'] ?? [];

        return new SecureHeadersMiddleware($secureHeaders);
    },
    HstsMiddleware::class => static function () use ($params) {
        $hsts = $params['app/hsts'] ?? [];

        return new HstsMiddleware(
            maxAge: (int) ($hsts['maxAge'] ?? 31536000),
            includeSubDomains: (bool) ($hsts['includeSubDomains'] ?? true),
            preload: (bool) ($hsts['preload'] ?? false)
        );
    },

    RequestIdMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new RequestIdMiddleware($monitoring['request_id_header'] ?? 'X-Request-Id');
    },

    StructuredLoggingMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new StructuredLoggingMiddleware($monitoring['logging'] ?? []);
    },

    MetricsMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new MetricsMiddleware($monitoring['metrics'] ?? []);
    },

    ErrorMonitoringMiddleware::class => static function () use ($params) {
        $monitoring = $params['app/monitoring'] ?? [];

        return new ErrorMonitoringMiddleware($monitoring['error_monitoring'] ?? []);
    },

    AccessMiddleware::class => static fn (
        AccessChecker $accessChecker,
        CurrentUser $currentUser,
        UrlMatcher $urlMatcher,
    ) => new AccessMiddleware($accessChecker, $currentUser, $urlMatcher),
];
