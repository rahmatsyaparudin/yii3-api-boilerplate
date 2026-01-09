<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class BadRequestException extends HttpException
{
    public function __construct(string $message = 'Bad request', ?array $params = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, Status::BAD_REQUEST, $params, $previous);
    }
}
