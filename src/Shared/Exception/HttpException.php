<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;
use App\Shared\ValueObject\Message;

abstract class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $httpStatusCode,
        private readonly Message $translateMessage,
        private readonly ?array $errors = null,
        private readonly ?array $data = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct('', $httpStatusCode, $previous);
    }

    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
    
    public function getTranslateMessage(): Message
    {
        return $this->translateMessage;
    }
    
    public function getDefaultMessageKey(): string
    {
        return $this->translateMessage->getKey();
    }
    
    public function getTranslateParams(): array
    {
        return $this->translateMessage->getParams();
    }
    
    public function getErrors(): ?array
    {
        return $this->errors;
    }
    
    public function getData(): ?array
    {
        return $this->data;
    }
}
