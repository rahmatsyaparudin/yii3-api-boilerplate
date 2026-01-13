<?php

declare(strict_types=1);

use App\Api\Shared\ExceptionResponderFactory;
use App\Api\Shared\NotFoundMiddleware;
use App\Infrastructure\Monitoring\ErrorMonitoringMiddleware;
use App\Infrastructure\Monitoring\MetricsMiddleware;
use App\Infrastructure\Monitoring\RequestIdMiddleware;
use App\Infrastructure\Monitoring\StructuredLoggingMiddleware;
use App\Shared\Middleware\AccessMiddleware;
use App\Shared\Middleware\CorsMiddleware;
use App\Shared\Middleware\JwtMiddleware;
use App\Shared\Middleware\RateLimitMiddleware;
use App\Shared\Middleware\SecureHeadersMiddleware;
use App\Shared\Middleware\TrustedHostMiddleware;
use Yiisoft\DataResponse\Formatter\JsonDataResponseFormatter;
use Yiisoft\DataResponse\Formatter\XmlDataResponseFormatter;
use Yiisoft\DataResponse\Middleware\ContentNegotiator;
use Yiisoft\DataResponse\Middleware\FormatDataResponseAsJson;
use Yiisoft\Definitions\DynamicReference;
use Yiisoft\Definitions\Reference;
use Yiisoft\ErrorHandler\Middleware\ErrorCatcher;
use Yiisoft\Input\Http\HydratorAttributeParametersResolver;
use Yiisoft\Input\Http\RequestInputParametersResolver;
use Yiisoft\Middleware\Dispatcher\CompositeParametersResolver;
use Yiisoft\Middleware\Dispatcher\MiddlewareDispatcher;
use Yiisoft\Middleware\Dispatcher\ParametersResolverInterface;
use Yiisoft\Request\Body\RequestBodyParser;
use Yiisoft\Router\Middleware\Router;
use Yiisoft\Yii\Http\Application;

// @var array $params

return [
    Application::class => [
        '__construct()' => [
            'dispatcher' => DynamicReference::to([
                'class'             => MiddlewareDispatcher::class,
                'withMiddlewares()' => [
                    [
                        FormatDataResponseAsJson::class,
                        static fn () => new ContentNegotiator([
                            // API: Enable XML support when needed
                            // 'application/xml' => new XmlDataResponseFormatter(),
                            'application/json' => new JsonDataResponseFormatter(),
                        ]),
                        ErrorCatcher::class,
                        static fn (ExceptionResponderFactory $factory) => $factory->create(),
                        static fn () => new TrustedHostMiddleware(
                            $params['app/trusted_hosts']['allowedHosts'] ?? [],
                        ),
                        CorsMiddleware::class,
                        JwtMiddleware::class,
                        RequestIdMiddleware::class,
                        StructuredLoggingMiddleware::class,
                        MetricsMiddleware::class,
                        RateLimitMiddleware::class,
                        SecureHeadersMiddleware::class,
                        ErrorMonitoringMiddleware::class,
                        RequestBodyParser::class,
                        AccessMiddleware::class,
                        Router::class,
                        NotFoundMiddleware::class,
                    ],
                ],
            ]),
        ],
    ],

    ParametersResolverInterface::class => [
        'class'         => CompositeParametersResolver::class,
        '__construct()' => [
            Reference::to(HydratorAttributeParametersResolver::class),
            Reference::to(RequestInputParametersResolver::class),
        ],
    ],
];
