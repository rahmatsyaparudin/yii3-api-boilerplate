<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Request\PaginationParams;
use App\Shared\Request\SortParams;
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;
use App\Shared\Request\DataParserInterface;
use Psr\Http\Message\ServerRequestInterface;

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
        DataParserInterface $parser,
        int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        int $maxPageSize = self::MAX_PAGE_SIZE
    ) {
        $rawData = $parser->all();
        $this->rawParams = new RawParams($rawData);
        
        $filterData = $rawData['filter'] ?? [];
        $this->filter = new RawParams($filterData);
        
        $paginationData = $rawData['pagination'] ?? [];
        if (empty($paginationData)) {
            $pageParam = $rawData['page'] ?? self::DEFAULT_PAGE;
            $pageSizeParam = $rawData['page_size'] ?? $defaultPageSize;
        } else {
            $pageParam = $paginationData['page'] ?? self::DEFAULT_PAGE;
            $pageSizeParam = $paginationData['page_size'] ?? $defaultPageSize;
        }
        
        if (!is_numeric($pageParam)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'pagination.invalid_parameter', 
                    params: ['parameter' => 'page']
                )
            );
        }
        $page = max(self::DEFAULT_PAGE, (int) $pageParam);
        
        if (!is_numeric($pageSizeParam)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'pagination.invalid_parameter', 
                    params: ['parameter' => 'page_size']
                )
            );
        }
        $pageSize = max(1, min($maxPageSize, (int) $pageSizeParam));
        $this->pagination = new PaginationParams(page: $page, page_size: $pageSize);
        
        $sortData = $rawData['sort'] ?? [];
        if (empty($sortData)) {
            $sortData = $rawData;
        }
        $this->sort = new SortParams(
            by: $sortData['by'] ?? null,
            dir: $sortData['dir'] ?? self::DEFAULT_SORT_DIR
        );
    }

    public function ensureExists(string $resource): void
    {
        if (empty($this->rawParams->all())) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'validation.invalid_payload',
                    params: ['resource' => $resource]
                )
            );
        }
    }

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

    public static function fromRequest(ServerRequestInterface $request, string $attribute = 'payload'): self
    {
        $params = $request->getAttribute($attribute);
        if (!$params instanceof self) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'http.missing_request_params', 
                    params: ['parameter' => 'page']
                )
            );
        }

        return $params;
    }

    public static function from(array $data, int $defaultPageSize = self::DEFAULT_PAGE_SIZE, int $maxPageSize = self::MAX_PAGE_SIZE): self
    {
        $parser = new class($data) implements DataParserInterface {
            public function __construct(private array $data) {}
            public function all(): array { return $this->data; }
            public function get(string $key, mixed $default = null): mixed { return $this->data[$key] ?? $default; }
        };

        return new self($parser, $defaultPageSize, $maxPageSize);
    }
}