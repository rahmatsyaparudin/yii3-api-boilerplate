<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;
use App\Shared\ValueObject\Message;

final class ValidationException extends HttpException
{
    public function __construct(?array $errors = null, Message|string $translate = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'validation.failed');
        parent::__construct(Status::UNPROCESSABLE_ENTITY, $message, $errors, $previous);
    }
}
