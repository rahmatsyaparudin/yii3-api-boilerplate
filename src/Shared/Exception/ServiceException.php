<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;
use App\Shared\ValueObject\Message;

final class ServiceException extends HttpException
{
    public function __construct(?int $code = Status::OK, Message|string $translate = null, ?array $data = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'service.error');
        parent::__construct($code, $message, $data, $previous);
    }
}
