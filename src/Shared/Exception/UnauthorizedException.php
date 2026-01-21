<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;
use App\Shared\ValueObject\Message;

final class UnauthorizedException extends HttpException
{
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'http.unauthorized');
            
        parent::__construct(Status::UNAUTHORIZED, $message, $errors, $previous);
    }
}
