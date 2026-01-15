<?php

declare(strict_types=1);

namespace App\Shared\Request;

/**
 * Raw Request Parameters
 * 
 * Value object for raw request parameters
 * Following DDD best practices for value objects
 */
final readonly class RawParams
{
    public function __construct(
        private array $params = []
    ) {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    public function toArray(): array
    {
        return $this->params;
    }

    public function all(): array
    {
        return $this->params;
    }

    public function with(string $key, mixed $value): self
    {
        return new self([...$this->params, $key => $value]);
    }

    public function merge(array $data): self
    {
        return new self([...$this->params, ...$data]);
    }

    public function __get(string $name): mixed
    {
        return $this->params[$name] ?? null;
    }

    public function __isset(string $name): bool
    {
        return isset($this->params[$name]);
    }

    public function __debugInfo(): array
    {
        return $this->params;
    }
}
