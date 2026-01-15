<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RequestParams
{
    private const DEFAULT_PAGE_SIZE = 50;
    private const MAX_PAGE_SIZE = 200;
    private const DEFAULT_PAGE = 1;
    private const DEFAULT_SORT_DIR = 'asc';

    private RawParams $rawParams;
    private RawParams $query;
    private PaginationParams $pagination;
    private SortParams $sort;

    public function __construct(
        RequestDataParser $parser,
        int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        int $maxPageSize = self::MAX_PAGE_SIZE
    ) {
        $rawData = $parser->all();
        $this->rawParams = new RawParams($rawData);
        
        $this->query = $this->createQueryParams($rawData);
        $this->pagination = $this->createPaginationParams($rawData, $parser, $defaultPageSize, $maxPageSize);
        $this->sort = $this->createSortParams($rawData, $parser);
    }

    private function createQueryParams(array $rawParams): RawParams
    {
        $queryData = $rawParams['query'] ?? [];
        return new RawParams($queryData);
    }

    private function createPaginationParams(
        array $rawParams,
        RequestDataParser $parser,
        int $defaultPageSize,
        int $maxPageSize
    ): PaginationParams {
        $paginationData = $rawParams['pagination'] ?? [];
        
        $page = $this->normalizePage($paginationData['page'] ?? $parser->get('page', self::DEFAULT_PAGE));
        $pageSize = $this->normalizePageSize(
            $paginationData['page_size'] ?? $parser->get('page_size', $defaultPageSize),
            $maxPageSize
        );

        return new PaginationParams(page: $page, page_size: $pageSize);
    }

    private function createSortParams(array $rawParams, RequestDataParser $parser): SortParams
    {
        $sortData = $rawParams['sort'] ?? [];
        
        return new SortParams(
            by: $sortData['by'] ?? null,
            dir: $sortData['dir'] ?? self::DEFAULT_SORT_DIR
        );
    }

    private function normalizePage(mixed $page): int
    {
        return max(self::DEFAULT_PAGE, (int) $page);
    }

    private function normalizePageSize(mixed $pageSize, int $maxPageSize): int
    {
        $normalized = (int) $pageSize;
        return max(1, min($maxPageSize, $normalized));
    }

    // ====== MAIN GETTERS ======
    public function getQuery(): RawParams
    {
        return $this->query;
    }

    public function getPagination(): PaginationParams
    {
        return $this->pagination;
    }

    public function getSort(): SortParams
    {
        return $this->sort;
    }

    public function getRawParams(): RawParams
    {
        return $this->rawParams;
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

    public function getParams(): array
    {
        return array_diff_key($this->rawParams, array_flip(['query', 'pagination', 'sort']));
    }

    public function withTotal(): bool
    {
        return ($this->rawParams['with_total'] ?? '0') !== '0';
    }

    // ====== UTILITY METHODS ======
    public function hasQuery(string $key): bool
    {
        return $this->query->has($key);
    }

    public function hasPagination(string $key): bool
    {
        return array_key_exists($key, $this->pagination->toArray());
    }

    public function hasSort(string $key): bool
    {
        return array_key_exists($key, $this->sort->toArray());
    }

    public static function fromRequest(ServerRequestInterface $request, string $attribute = 'payload'): self
    {
        $params = $request->getAttribute($attribute);
        if (!$params instanceof self) {
            throw new \RuntimeException('RequestParams not found in request attribute.');
        }

        return $params;
    }
}
