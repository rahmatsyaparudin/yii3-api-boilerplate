<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

final class TooManyRequestsException extends HttpException
{
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'http.too_many_requests');
            
        parent::__construct(Status::TOO_MANY_REQUESTS, $message, $errors, $previous);
    }
}
