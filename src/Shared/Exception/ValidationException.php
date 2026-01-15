<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class ValidationException extends HttpException
{
    public function __construct(?array $errors = null, ?array $translate = null, ?\Throwable $previous = null)
    {
        $translate ??= ['key' => 'validation.failed', 'params' => []];
        parent::__construct(Status::UNPROCESSABLE_ENTITY, $translate, $errors, $previous);
    }
}
