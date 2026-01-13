<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class NotFoundException extends HttpException
{
    public function __construct(?array $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $translate = $translate ?? ['key' => 'not_found', 'params' => []];
        parent::__construct(Status::NOT_FOUND, $translate, $errors, $previous);
    }
}
