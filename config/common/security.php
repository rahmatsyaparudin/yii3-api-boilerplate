<?php

declare(strict_types=1);

/**
 * Optional project-specific security overrides/additions.
 *
 * Definitions here are merged into config/common/di/security-di.php and
 * can override or extend the default security bindings.
 *
 * Default trusted hosts are configured via the environment variable
 * `app.trusted_hosts.allowedHosts` and exposed in `$params['app/trusted_hosts']`.
 *
 * Example using .env (params.php):
 *
 *   use App\Shared\Core\Middleware\ExampleMiddleware;
 *
 *   return [
 *       ExampleMiddleware::class => static function () use ($params) {
 *           return new ExampleMiddleware(
 *               allowedHosts: $params['app/trusted_hosts']['allowedHosts'] ?? [],
 *           );
 *       },
 *   ];
 *
 * Example using manual/hardcoded values:
 *
 *   use App\Shared\Core\Middleware\ExampleMiddleware;
 *
 *   return [
 *       ExampleMiddleware::class => static fn () => new ExampleMiddleware(
 *           allowedHosts: [
 *               'api.example.com',
 *               'example.com',
 *               '*.example.com',
 *           ],
 *       ),
 *   ];
 *
 * @var array $params
 */

return [];
