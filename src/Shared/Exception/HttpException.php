<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use RuntimeException;

abstract class HttpException extends RuntimeException
{
    public function __construct(
        string $message,
        private readonly int $httpStatusCode,
        private readonly array $params = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatusCode, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }

    public function getParams(): array
    {
        return $this->params;
    }
}
