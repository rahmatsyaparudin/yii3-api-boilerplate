<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

final readonly class Message
{
    public function __construct(
        public string $key,
        public array $params = [],
        public ?string $domain = null
    ) {}

    public function getKey(): string
    {
        return $this->key;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function getDomain(): ?string
    {
        return $this->domain;
    }
}