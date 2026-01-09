<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class NotFoundException extends HttpException
{
    public function __construct(string $message = 'Not found', ?array $params = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, Status::NOT_FOUND, $params, $previous);
    }
}
