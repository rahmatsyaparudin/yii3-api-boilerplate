<?php

declare(strict_types=1);

namespace App\Shared\Exception;

abstract class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $httpStatusCode,
        private readonly array $translate,
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
    
    public function getTranslate(): array
    {
        return $this->translate;
    }
    
    public function getDefaultMessageKey(): string
    {
        return $this->translate['key'] ?? 'error';
    }
    
    public function getTranslateParams(): array
    {
        return $this->translate['params'] ?? [];
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
