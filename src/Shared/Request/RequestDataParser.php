<?php

declare(strict_types=1);

namespace App\Shared\Request;

// PSR Interfaces
use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestDataParser
{
    private array $data;

    public function __construct(private ServerRequestInterface $request)
    {
        $this->data = $this->parse();
    }

    private function parse(): array
    {
        $query = $this->request->getQueryParams();
        $body  = $this->request->getParsedBody() ?? [];

        if (\is_object($body)) {
            $body = (array) $body;
        }

        // Body overrides query
        return \array_merge($query, $body);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    public function all(): array
    {
        return $this->data;
    }
}
