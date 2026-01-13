<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class UnauthorizedException extends HttpException
{
    public function __construct(?array $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $translate = $translate ?? ['key' => 'unauthorized', 'params' => []];
        parent::__construct(Status::UNAUTHORIZED, $translate, $errors, $previous);
    }
}
