<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class ForbiddenException extends HttpException
{
    public function __construct(string $message = 'Forbidden', ?array $params = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, Status::FORBIDDEN, $params, $previous);
    }
}
