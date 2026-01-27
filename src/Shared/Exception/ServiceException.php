<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;
use App\Shared\ValueObject\Message;

final class ServiceException extends HttpException
{
    public function __construct(Message|string $translate = null, ?array $data = null, ?int $code = Status::OK, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'service.error');
        parent::__construct($code, $message, $data, $previous);
    }
}
