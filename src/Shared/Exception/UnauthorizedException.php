<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class UnauthorizedException extends HttpException
{
    public function __construct(string $message = 'Unauthorized', ?array $params = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, Status::UNAUTHORIZED, $params, $previous);
    }
}
