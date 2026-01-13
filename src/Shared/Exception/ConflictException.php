<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class ConflictException extends HttpException
{
    public function __construct(?array $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $translate = $translate ?? ['key' => 'resource.conflict', 'params' => []];
        parent::__construct(Status::CONFLICT, $translate, $errors, $previous);
    }
}
