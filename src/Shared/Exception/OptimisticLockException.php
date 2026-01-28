<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

final class OptimisticLockException extends HttpException
{
    public function __construct(Message|string $translate = null, ?array $data = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'optimistic.lock.failed');
        parent::__construct(Status::CONFLICT, $message, $data, $previous);
    }
}
