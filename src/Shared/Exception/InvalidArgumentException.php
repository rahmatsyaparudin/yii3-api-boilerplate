<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Exception thrown when an argument is invalid
 * 
 * This exception is used for validation errors that occur
 * when arguments passed to methods are invalid, such as:
 * - Invalid entity state
 * - Business rule violations
 * - Domain invariant violations
 * - Invalid value object construction
 */
final class InvalidArgumentException extends HttpException
{
    public function __construct(string $message = 'Invalid argument provided', ?array $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $translate ??= ['key' => 'invalid.argument', 'params' => []];
        parent::__construct(Status::UNPROCESSABLE_ENTITY, $translate, $errors, $previous);
    }
}
