<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class ConflictException extends HttpException
{
    public function __construct(string $message = 'Conflict', ?array $params = [], ?\Throwable $previous = null)
    {
        parent::__construct($message, Status::CONFLICT, $params, $previous);
    }
}
