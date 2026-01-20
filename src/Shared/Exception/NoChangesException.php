<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

final class NoChangesException extends HttpException
{
    public function __construct(?array $translate = null, ?array $data = null, ?\Throwable $previous = null)
    {
        $translate ??= ['key' => 'resource.conflict', 'params' => []];
        parent::__construct(Status::OK, $translate, null, $data, $previous);
    }
}
