<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class TooManyRequestsException extends HttpException
{
    public function __construct(?array $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $translate = $translate ?? ['key' => 'too_many_requests', 'params' => []];
        parent::__construct(Status::TOO_MANY_REQUESTS, $translate, $errors, $previous);
    }
}
