<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class BadRequestException extends HttpException
{
    public function __construct(?array $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $translate = $translate ?? ['key' => 'bad_request', 'params' => []];
        parent::__construct(Status::BAD_REQUEST, $translate, $errors, $previous);
    }
}
