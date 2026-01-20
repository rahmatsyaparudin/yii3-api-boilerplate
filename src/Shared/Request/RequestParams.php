<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Simple Request Parameters
 * 
 * Simplified version focusing on essential functionality
 */
final readonly class RequestParams
{
    private const DEFAULT_PAGE_SIZE = 50;
    private const MAX_PAGE_SIZE = 200;
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_SORT_DIR = 'asc';

    private RawParams $rawParams;
    private RawParams $filter;
    private PaginationParams $pagination;
    private SortParams $sort;

    public function __construct(
        RequestDataParser $parser,
        int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        int $maxPageSize = self::MAX_PAGE_SIZE
    ) {
        $rawData = $parser->all();
        $this->rawParams = new RawParams($rawData);
        
        // Extract filter parameters
        $filterData = $rawData['filter'] ?? [];
        $this->filter = new RawParams($filterData);
        
        // Simple pagination
        $page = max(self::DEFAULT_PAGE, (int) ($rawData['page'] ?? self::DEFAULT_PAGE));
        $pageSize = max(1, min($maxPageSize, (int) ($rawData['page_size'] ?? $defaultPageSize)));
        $this->pagination = new PaginationParams(page: $page, page_size: $pageSize);
        
        // Simple sort
        $sortData = $rawData['sort'] ?? [];
        $this->sort = new SortParams(
            by: $sortData['by'] ?? null,
            dir: $sortData['dir'] ?? self::DEFAULT_SORT_DIR
        );
    }

    // ====== MAIN GETTERS ======
    public function getRawParams(): RawParams
    {
        return $this->rawParams;
    }

    public function getFilter(): RawParams
    {
        return $this->filter;
    }

    public function getPagination(): PaginationParams
    {
        return $this->pagination;
    }

    public function getSort(): SortParams
    {
        return $this->sort;
    }

    public function getPage(): int
    {
        return $this->pagination->page;
    }

    public function getPageSize(): int
    {
        return $this->pagination->page_size;
    }

    public function getOffset(): int
    {
        return $this->pagination->getOffset();
    }

    // ====== CONVENIENCE METHODS ======
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->rawParams->get($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->rawParams->has($key);
    }

    public function all(): array
    {
        return $this->rawParams->all();
    }

    public function withTotal(): bool
    {
        return ($this->rawParams->get('with_total') ?? '0') !== '0';
    }

    // ====== STATIC FACTORY ======
    public static function fromRequest(ServerRequestInterface $request, string $attribute = 'payload'): self
    {
        $params = $request->getAttribute($attribute);
        if (!$params instanceof self) {
            throw new \RuntimeException('RequestParams not found in request attribute.');
        }

        return $params;
    }

    // ====== STATIC CREATION ======
    public static function from(array $data, int $defaultPageSize = self::DEFAULT_PAGE_SIZE, int $maxPageSize = self::MAX_PAGE_SIZE): self
    {
        $parser = new class($data) {
            public function __construct(private array $data) {}
            public function all(): array { return $this->data; }
            public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
        };

        return new self($parser, $defaultPageSize, $maxPageSize);
    }
}
