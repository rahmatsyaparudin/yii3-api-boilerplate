<?php

declare(strict_types=1);

/**
 * Common middleware stack.
 *
 * This file is intended for additional/common PSR-15 middleware that should
 * run on every request. Add the class names of your middleware here.
 *
 * Always-used middleware DI definitions are registered in
 * config/common/di/middleware-di.php. Only add middleware to this stack if it
 * should actually be executed for the project.
 *
 * Example:
 *
 *   use App\Presentation\Core\Http\Middleware\ExampleMiddleware;
 *
 *   return [
 *       ExampleMiddleware::class,
 *   ];
 */

return [];
