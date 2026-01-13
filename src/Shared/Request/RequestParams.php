<?php

declare(strict_types=1);

namespace App\Shared\Request;

use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestParams
{
    private int $page;
    private int $pageSize;
    private int $offset;
    private bool $withTotal;
    private ?array $meta;
    private ?string $sortBy;
    private string $sortDir;
    private array $params;

    private const PAGING_META_KEYS = ['page', 'page_size', 'with_total', 'sort_by', 'sort_dir'];

    public function __construct(
        RequestDataParser $parser,
        int $defaultPageSize = 50,
        int $maxPageSize = 200
    ) {
        $this->page = \max(1, (int) $parser->get('page', 1));

        $this->pageSize = \max(
            1,
            \min($maxPageSize, (int) $parser->get('page_size', $defaultPageSize))
        );

        $this->offset = ($this->page - 1) * $this->pageSize;

        $this->withTotal = (string) $parser->get('with_total', '0') !== '0';

        $this->sortBy = $parser->get('sort_by') ?? 'id';

        $dir           = \strtolower((string) $parser->get('sort_dir', 'asc'));
        $this->sortDir = \in_array($dir, ['asc', 'desc'], true) ? $dir : 'desc';

        $this->params = \array_filter(
            $parser->all(),
            fn ($key) => !\in_array($key, self::PAGING_META_KEYS, true),
            ARRAY_FILTER_USE_KEY
        );

        $this->meta = [
            'pagination' => [
                'page'      => $this->page,
                'page_size' => $this->pageSize,
                'sort_by'   => $this->sortBy,
                'sort_dir'  => $this->sortDir,
            ],
        ];
    }

    // ========= GETTERS =========
    public function getPage(): int
    {
        return $this->page;
    }

    public function getPageSize(): int
    {
        return $this->pageSize;
    }

    public function getOffset(): int
    {
        return $this->offset;
    }

    public function withTotal(): bool
    {
        return $this->withTotal;
    }

    public function getMeta(): ?array
    {
        return $this->meta;
    }

    public function getSortBy(): ?string
    {
        return $this->sortBy;
    }

    public function getSortDir(): string
    {
        return $this->sortDir;
    }

    public function getParams(): array
    {
        return $this->params;
    }

    public function hasFilter(string $key): bool
    {
        return \array_key_exists($key, $this->params);
    }

    public static function fromRequest(ServerRequestInterface $request, string $attribute = 'params'): self
    {
        $params = $request->getAttribute($attribute);
        if (!$params instanceof self) {
            throw new \RuntimeException('RequestParams not found in request attribute.');
        }

        return $params;
    }
}
