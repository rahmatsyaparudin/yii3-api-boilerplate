# Request Processing Guide

## 📋 Overview

Request processing utilities provide a structured way to handle HTTP request data: query/body parsing, whitelisting, sanitization, pagination, sorting, and search criteria. `RequestParamsMiddleware` builds a `RequestParams` value object once per request and stores it in the `payload` request attribute; actions consume it and convert it to `SearchCriteria` via `SearchCriteriaFactory`.

---

## 🏗️ Request Architecture

### Directory Structure

```
src/Shared/Core/Request/
├── DataParserInterface.php    # Interface for data parsing (all(), get())
├── PaginationParams.php       # Pagination value object (page, page_size)
├── RawParams.php              # Immutable param bag (get/has/with/onlyAllowed/sanitize)
├── RequestDataParser.php      # Merges query params + parsed body (body wins)
├── RequestParams.php          # Aggregates raw params, filter, pagination, sort
└── SortParams.php             # Sort value object (by, dir)

src/Shared/Core/Dto/
├── SearchCriteria.php         # filter + page/pageSize + sortBy/sortDir + allowedSort
└── PaginatedResult.php        # data + total + page/pageSize + filter/sort meta

src/Shared/Core/Middleware/
└── RequestParamsMiddleware.php  # Builds RequestParams, stores in 'payload' attribute

src/Application/Shared/Core/Factory/
└── SearchCriteriaFactory.php    # RequestParams -> SearchCriteria
```

### Design Principles

#### **1. **Type Safety**
- Strong typing with PHP 8+ features
- `readonly` value objects (`RawParams`, `PaginationParams`, `SortParams`, `SearchCriteria`)
- `BadRequestException` on invalid input

#### **2. **Consistency**
- One `RequestParams` object per request, created by middleware
- Uniform `filter` / `pagination` / `sort` payload shape
- `SearchCriteria` is the single hand-off object to application/repository layers

#### **3. **Security**
- `onlyAllowed()` rejects unknown parameter keys
- `sanitize()` applies `InputSanitizer` to all values
- Page size is capped (`maxPageSize`)

#### **4. **Performance**
- Single parse pass in middleware
- Immutable objects — safe to pass between layers
- Lazy: no validation happens until an action opts in

---

## 📦 Request Payload Shape

`RequestParams` understands both nested and flat parameters:

```json
{
    "filter": {
        "name": "acer",
        "status": 1
    },
    "pagination": {
        "page": 1,
        "page_size": 20
    },
    "sort": {
        "by": "name",
        "dir": "desc"
    },
    "with_total": "1"
}
```

Flat equivalents also work for pagination/sort: `?page=1&page_size=20&by=name&dir=desc`.
Non-numeric `page`/`page_size` values throw `BadRequestException`; `page` is clamped to `>= 1` and `page_size` to `1..maxPageSize`.

---

## 📁 Request Components

### 1. RequestParams

**Purpose**: Aggregates all request parameters into typed sub-objects. Created by `RequestParamsMiddleware` and stored in the `payload` request attribute.

**Location**: `src/Shared/Core/Request/RequestParams.php`

```php
final readonly class RequestParams
{
    private const DEFAULT_PAGE_SIZE = 50;
    private const MAX_PAGE_SIZE     = 200;
    private const DEFAULT_PAGE      = 1;
    private const DEFAULT_SORT_DIR  = 'desc';

    public function __construct(
        DataParserInterface $parser,
        int $defaultPageSize = self::DEFAULT_PAGE_SIZE,
        int $maxPageSize = self::MAX_PAGE_SIZE
    ) {
        $rawData         = $parser->all();
        $this->rawParams = new RawParams($rawData);
        $this->filter    = new RawParams($rawData['filter'] ?? []);
        // pagination: $rawData['pagination'] or flat page/page_size
        // sort:       $rawData['sort'] or flat by/dir (default dir 'desc')
    }

    public function ensureExists(string $resource): void   // 400 when payload is empty
    public function getRawParams(): RawParams;             // all params
    public function getFilter(): RawParams;                // the 'filter' sub-array
    public function getPagination(): PaginationParams;
    public function getSort(): SortParams;
    public function getPage(): int;
    public function getPageSize(): int;
    public function getOffset(): int;
    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;
    public function all(): array;
    public function withTotal(): bool;                     // 'with_total' !== '0'

    /** Pull the RequestParams stored in a request attribute ('payload' by default). */
    public static function fromRequest(ServerRequestInterface $request, string $attribute = 'payload'): self;

    /** Build directly from an array (tests, non-HTTP usage). */
    public static function from(array $data, int $defaultPageSize = self::DEFAULT_PAGE_SIZE, int $maxPageSize = self::MAX_PAGE_SIZE): self;
}
```

**Usage Example**:
```php
// In an action — RequestParams comes from the 'payload' attribute
public function __invoke(ServerRequestInterface $request): ResponseInterface
{
    /** @var RequestParams $payload */
    $payload = $request->getAttribute('payload');
    // or: $payload = RequestParams::fromRequest($request);

    $payload->ensureExists(resource: 'Example'); // BadRequestException if empty

    $page   = $payload->getPage();
    $size   = $payload->getPageSize();
    $offset = $payload->getOffset();
    $sort   = $payload->getSort();        // SortParams
    $filter = $payload->getFilter();      // RawParams of the 'filter' block
}
```

---

### 2. RawParams

**Purpose**: Immutable parameter bag — the base value object for raw params and filters.

```php
final readonly class RawParams
{
    public function __construct(private array $params = []) {}

    public function get(string $key, mixed $default = null): mixed;
    public function has(string $key): bool;       // array_key_exists
    public function toArray(): array;
    public function all(): array;                  // alias of toArray()
    public function with(string $key, mixed $value): self;  // returns new instance
    public function merge(array $data): self;                // returns new instance

    public function __get(string $name): mixed;     // $params->name
    public function __isset(string $name): bool;    // isset($params->name)

    /**
     * Keep only allowed keys. Throws BadRequestException listing
     * unknown + allowed keys when extras are present.
     */
    public function onlyAllowed(array $allowedKeys): self;

    /** Sanitize all values via InputSanitizer (XSS-safe). */
    public function sanitize(): self;
}
```

**Usage Example**:
```php
// In an action — typical create/update pipeline
$params = $payload->getRawParams()
    ->onlyAllowed(allowedKeys: ['name', 'status', 'lock_version'])
    ->with('id', $id)          // merge route param
    ->sanitize();

$name = $params->get('name');
$lockVersion = $params->get('lock_version');
```

---

### 3. PaginationParams

**Purpose**: Pagination value object — `page` + `page_size` with computed offset.

```php
final readonly class PaginationParams
{
    public function __construct(
        public int $page = 1,
        public int $page_size = 50
    ) {}

    public function getLimit(): int;   // = page_size
    public function getOffset(): int;  // = (page - 1) * page_size
    public function toArray(): array;  // ['page' => ..., 'page_size' => ...]
}
```

**Usage Example**:
```php
$pagination = $payload->getPagination();

$query->limit($pagination->getLimit())
      ->offset($pagination->getOffset());
```

---

### 4. SortParams

**Purpose**: Sort value object — a single `by` field + `dir` direction.

```php
final readonly class SortParams
{
    public function __construct(
        public ?string $by = null,
        public string $dir = 'asc'   // RequestParams defaults this to 'desc'
    ) {}

    public function getSortBy(): ?string;
    public function getSortDir(): string;   // 'asc' | 'desc'
    public function toArray(): array;       // ['by' => ..., 'dir' => ...]
}
```

**Usage Example**:
```php
$sort = $payload->getSort();

if ($sort->getSortBy()) {
    $direction = $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
    $query->orderBy([$sort->getSortBy() => $direction]);
}
```

---

### 5. DataParserInterface & RequestDataParser

**Purpose**: Abstraction over "where the raw data comes from".

```php
interface DataParserInterface
{
    public function all(): array;
    public function get(string $key, mixed $default = null): mixed;
}
```

`RequestDataParser` implements it for PSR-7 requests — merges query params and parsed body, body taking precedence:

```php
final readonly class RequestDataParser implements DataParserInterface
{
    public function __construct(private ServerRequestInterface $request)
    {
        $this->data = $this->parse(); // array_merge($query, $body)
    }

    public function get(string $key, mixed $default = null): mixed;
    public function all(): array;
}
```

**Usage Example**:
```php
// Standalone usage (outside the middleware pipeline)
$parser = new RequestDataParser(request: $request);
$params = new RequestParams(parser: $parser);

// RequestParams::from() wraps an array in an anonymous DataParserInterface
$params = RequestParams::from(['filter' => ['status' => 1], 'page' => 2]);
```

> **Note**: `RequestParamsMiddleware` does not use `RequestDataParser` — it builds an anonymous `DataParserInterface` inline that performs the same query+body merge.

---

### 6. RequestParamsMiddleware

**Purpose**: Builds `RequestParams` once per request and stores it in request attributes.

**Location**: `src/Shared/Core/Middleware/RequestParamsMiddleware.php`

```php
final class RequestParamsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $defaultPageSize = 50,
        private int $maxPageSize = 200
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // anonymous DataParserInterface merging query + body (body wins)
        $parser = new class($request) implements DataParserInterface { /* ... */ };

        $params = new RequestParams($parser, $this->defaultPageSize, $this->maxPageSize);

        $request = $request->withAttribute('paginationConfig', [
            'defaultPageSize' => $this->defaultPageSize,
            'maxPageSize'     => $this->maxPageSize,
        ]);
        $request = $request->withAttribute('payload', $params);

        return $handler->handle($request);
    }
}
```

Registered per-route-group in `config/common/routes.php` and configured via `app/pagination` params in `config/common/di/middleware-di.php`:

```php
Group::create('/v1')
    ->middleware(RequestParamsMiddleware::class)
    ->routes(/* ... */);
```

```php
RequestParamsMiddleware::class => static function () use ($params) {
    $pagination = $params['app/pagination'] ?? [];

    return new RequestParamsMiddleware(
        defaultPageSize: (int) ($pagination['defaultPageSize'] ?? 50),
        maxPageSize: (int) ($pagination['maxPageSize'] ?? 200),
    );
},
```

---

### 7. SearchCriteria & SearchCriteriaFactory

**Purpose**: `SearchCriteria` is the DTO handed to application services/repositories; `SearchCriteriaFactory` builds it from `RequestParams` with a whitelist of sortable columns.

**Locations**: `src/Shared/Core/Dto/SearchCriteria.php`, `src/Application/Shared/Core/Factory/SearchCriteriaFactory.php`

```php
final readonly class SearchCriteria
{
    public function __construct(
        public array $filter,
        public int $page,
        public int $pageSize = 10,
        public string $sortBy = 'id',
        public string $sortDir = 'desc',
        public ?int $offset = null,                       // manual offset override
        private array $allowedSort = ['id' => 'id'],      // client key => db column
    ) {}

    public function calculateOffset(): int;  // (page - 1) * pageSize

    /**
     * Order clause for the query builder: maps sortBy through allowedSort,
     * falls back to the first allowed column when the key is unknown,
     * and converts dir to SORT_ASC/SORT_DESC.
     */
    public function getOrderClause(): array;
}
```

```php
final class SearchCriteriaFactory
{
    public function createFromRequest(
        RequestParams $params,
        array $allowedSort,
        int $defaultPageSize = 15
    ): SearchCriteria {
        $pagination = $params->getPagination();
        $sort       = $params->getSort();

        return new SearchCriteria(
            filter: $params->getFilter()->toArray(),
            page: $pagination->page ?? 1,
            pageSize: $pagination->page_size ?? $defaultPageSize,
            sortBy: $sort->by ?? \array_key_first($allowedSort),
            sortDir: $sort->dir ?? 'desc',
            allowedSort: $allowedSort
        );
    }
}
```

---

## 🔧 Integration Patterns

### 1. **List Endpoint** (`ExampleDataAction`)

```php
final class ExampleDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'status'];
    private const ALLOWED_SORT = [
        'id'     => 'id',
        'name'   => 'name',
        'status' => 'status',
    ];

    public function __construct(
        private SearchCriteriaFactory $factory,
        private ExampleInputValidator $inputValidator,
        private ExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload */
        $payload = $request->getAttribute('payload');

        // Whitelist the filter keys, then validate with the SEARCH context
        $filter = $payload->getFilter()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->with('status', RecordStatus::DRAFT->value);

        $this->inputValidator->validate(
            data: $filter,
            context: ValidationContext::SEARCH,
        );

        $criteria = $this->factory->createFromRequest(
            params: $payload,
            allowedSort: self::ALLOWED_SORT
        );

        $result = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            translate: Message::create(key: 'resource.list_retrieved', params: [
                'resource' => $this->applicationService->getResource(),
            ]),
            meta: $result->getMeta(),
        );
    }
}
```

### 2. **Service Integration**

```php
final class ExampleApplicationService
{
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(criteria: $criteria);
    }
}
```

### 3. **Repository Integration**

```php
final class ExampleRepository
{
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where($this->scopeWhereNotDeleted());

        // Apply filters via QueryConditionApplier
        $this->queryConditionApplier->apply($query, $criteria->filter);

        $total = (clone $query)->count();

        $data = $query
            ->orderBy($criteria->getOrderClause())
            ->offset($criteria->offset ?? $criteria->calculateOffset())
            ->limit($criteria->pageSize)
            ->all();

        return new PaginatedResult(
            data: $data,
            total: $total,
            page: $criteria->page,
            pageSize: $criteria->pageSize,
            filter: $criteria->filter,
        );
    }
}
```

---

## 🚀 Best Practices

### 1. **Always Go Through the Payload**
```php
// ✅ Use the middleware-built RequestParams
$payload = $request->getAttribute('payload');
$params  = $payload->getRawParams()->onlyAllowed(self::ALLOWED_KEYS)->sanitize();

// ❌ Re-parse query/body manually in the action
$data = $request->getQueryParams();
```

### 2. **Whitelist Before Use**
```php
// ✅ Reject unknown keys early
$params = $payload->getRawParams()->onlyAllowed(['name', 'status']);

// ❌ Trust whatever the client sent
$params = $payload->getRawParams();
```

### 3. **Whitelist Sortable Columns**
```php
// ✅ Map client keys to DB columns — unknown sorts fall back safely
$criteria = $this->factory->createFromRequest($payload, allowedSort: [
    'id' => 'id', 'name' => 'name', 'created_at' => 'created_at',
]);

// ❌ Pass the raw sort field into orderBy (SQL injection risk)
$query->orderBy([$sort->getSortBy() => SORT_ASC]);
```

### 4. **Sanitize Input**
```php
// ✅ Sanitize values that will be persisted
$params = $payload->getRawParams()->onlyAllowed(self::ALLOWED_KEYS)->sanitize();
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- `readonly` value objects — no defensive copying needed
- Params are parsed once in middleware, reused by all layers

### 2. **Validation Overhead**
- `onlyAllowed()` fails fast on unknown keys
- `page_size` is capped by `maxPageSize` to prevent heavy queries

### 3. **Parsing Performance**
- Single `array_merge` of query + body per request
- `SearchCriteria::getOrderClause()` resolves the column in O(1)

---

## 🎯 Summary

Request processing utilities provide a structured, type-safe way to handle HTTP request data in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: `readonly` value objects prevent runtime errors
- **✅ Validation**: `onlyAllowed()` + input validators + `ensureExists()`
- **🔄 Consistency**: One `RequestParams` in `payload`, one `SearchCriteria` to services
- **🧪 Testability**: `RequestParams::from()` builds params from plain arrays
- **📦 Modularity**: Each component has a single responsibility
- **🚀 Performance**: Parse once, pass immutable objects between layers

By following the patterns and best practices outlined in this guide, you can build robust, maintainable request processing for your Yii3 API application! 🚀
