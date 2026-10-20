# Panduan Pemrosesan Request

## 📋 Gambaran Umum

Utilitas pemrosesan request menyediakan cara terstruktur untuk menangani data HTTP request: parsing query/body, whitelisting, sanitasi, pagination, sorting, dan search criteria. `RequestParamsMiddleware` membangun value object `RequestParams` sekali per request dan menyimpannya di atribut request `payload`; action mengonsumsinya dan mengonversinya menjadi `SearchCriteria` melalui `SearchCriteriaFactory`.

---

## 🏗️ Arsitektur Request

### Struktur Direktori

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

### Prinsip Desain

#### **1. **Type Safety**
- Pengetikan kuat dengan fitur PHP 8+
- Value object `readonly` (`RawParams`, `PaginationParams`, `SortParams`, `SearchCriteria`)
- `BadRequestException` untuk input yang tidak valid

#### **2. **Consistency**
- Satu objek `RequestParams` per request, dibuat oleh middleware
- Bentuk payload `filter` / `pagination` / `sort` yang seragam
- `SearchCriteria` adalah objek hand-off tunggal ke layer application/repository

#### **3. **Security**
- `onlyAllowed()` menolak key parameter yang tidak dikenal
- `sanitize()` menerapkan `InputSanitizer` ke semua nilai
- Page size dibatasi (`maxPageSize`)

#### **4. **Performance**
- Parsing sekali jalan di middleware
- Objek immutable — aman diteruskan antar layer
- Lazy: tidak ada validasi yang berjalan sampai sebuah action memilih untuk ikut serta

---

## 📦 Bentuk Payload Request

`RequestParams` memahami parameter nested maupun flat:

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

Padanan flat juga berfungsi untuk pagination/sort: `?page=1&page_size=20&by=name&dir=desc`.
Nilai `page`/`page_size` yang non-numerik melempar `BadRequestException`; `page` dikunci ke `>= 1` dan `page_size` ke `1..maxPageSize`.

---

## 📁 Komponen Request

### 1. RequestParams

**Tujuan**: Mengagregasi semua parameter request ke dalam sub-objek bertipe. Dibuat oleh `RequestParamsMiddleware` dan disimpan di atribut request `payload`.

**Lokasi**: `src/Shared/Core/Request/RequestParams.php`

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

**Contoh Penggunaan**:
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

**Tujuan**: Parameter bag immutable — value object dasar untuk raw params dan filter.

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

**Contoh Penggunaan**:
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

**Tujuan**: Value object pagination — `page` + `page_size` dengan offset terhitung.

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

**Contoh Penggunaan**:
```php
$pagination = $payload->getPagination();

$query->limit($pagination->getLimit())
      ->offset($pagination->getOffset());
```

---

### 4. SortParams

**Tujuan**: Value object sort — satu field `by` + arah `dir`.

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

**Contoh Penggunaan**:
```php
$sort = $payload->getSort();

if ($sort->getSortBy()) {
    $direction = $sort->getSortDir() === 'desc' ? SORT_DESC : SORT_ASC;
    $query->orderBy([$sort->getSortBy() => $direction]);
}
```

---

### 5. DataParserInterface & RequestDataParser

**Tujuan**: Abstraksi atas "dari mana data mentah berasal".

```php
interface DataParserInterface
{
    public function all(): array;
    public function get(string $key, mixed $default = null): mixed;
}
```

`RequestDataParser` mengimplementasikannya untuk request PSR-7 — menggabungkan query params dan parsed body, dengan body yang didahulukan:

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

**Contoh Penggunaan**:
```php
// Standalone usage (outside the middleware pipeline)
$parser = new RequestDataParser(request: $request);
$params = new RequestParams(parser: $parser);

// RequestParams::from() wraps an array in an anonymous DataParserInterface
$params = RequestParams::from(['filter' => ['status' => 1], 'page' => 2]);
```

> **Catatan**: `RequestParamsMiddleware` tidak menggunakan `RequestDataParser` — middleware membangun `DataParserInterface` anonim secara inline yang melakukan penggabungan query+body yang sama.

---

### 6. RequestParamsMiddleware

**Tujuan**: Membangun `RequestParams` sekali per request dan menyimpannya di atribut request.

**Lokasi**: `src/Shared/Core/Middleware/RequestParamsMiddleware.php`

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

Didaftarkan per-route-group di `config/common/routes.php` dan dikonfigurasi melalui params `app/pagination` di `config/common/di/middleware-di.php`:

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

**Tujuan**: `SearchCriteria` adalah DTO yang diserahkan ke application service/repository; `SearchCriteriaFactory` membangunnya dari `RequestParams` dengan whitelist kolom yang dapat di-sort.

**Lokasi**: `src/Shared/Core/Dto/SearchCriteria.php`, `src/Application/Shared/Core/Factory/SearchCriteriaFactory.php`

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

## 🔧 Pola Integrasi

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

### 2. **Integrasi Service**

```php
final class ExampleApplicationService
{
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->repository->list(criteria: $criteria);
    }
}
```

### 3. **Integrasi Repository**

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

### 1. **Selalu Melalui Payload**
```php
// ✅ Use the middleware-built RequestParams
$payload = $request->getAttribute('payload');
$params  = $payload->getRawParams()->onlyAllowed(self::ALLOWED_KEYS)->sanitize();

// ❌ Re-parse query/body manually in the action
$data = $request->getQueryParams();
```

### 2. **Whitelist Sebelum Digunakan**
```php
// ✅ Reject unknown keys early
$params = $payload->getRawParams()->onlyAllowed(['name', 'status']);

// ❌ Trust whatever the client sent
$params = $payload->getRawParams();
```

### 3. **Whitelist Kolom yang Dapat Di-sort**
```php
// ✅ Map client keys to DB columns — unknown sorts fall back safely
$criteria = $this->factory->createFromRequest($payload, allowedSort: [
    'id' => 'id', 'name' => 'name', 'created_at' => 'created_at',
]);

// ❌ Pass the raw sort field into orderBy (SQL injection risk)
$query->orderBy([$sort->getSortBy() => SORT_ASC]);
```

### 4. **Sanitasi Input**
```php
// ✅ Sanitize values that will be persisted
$params = $payload->getRawParams()->onlyAllowed(self::ALLOWED_KEYS)->sanitize();
```

---

## 📊 Pertimbangan Performa

### 1. **Penggunaan Memori**
- Value object `readonly` — tidak perlu defensive copying
- Params di-parse sekali di middleware, dipakai ulang oleh semua layer

### 2. **Overhead Validasi**
- `onlyAllowed()` gagal cepat pada key yang tidak dikenal
- `page_size` dibatasi oleh `maxPageSize` untuk mencegah query berat

### 3. **Performa Parsing**
- Satu `array_merge` query + body per request
- `SearchCriteria::getOrderClause()` me-resolve kolom dalam O(1)

---

## 🎯 Ringkasan

Utilitas pemrosesan request menyediakan cara yang terstruktur dan type-safe untuk menangani data HTTP request di aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🛡️ Type Safety**: Value object `readonly` mencegah runtime error
- **✅ Validation**: `onlyAllowed()` + input validator + `ensureExists()`
- **🔄 Consistency**: Satu `RequestParams` di `payload`, satu `SearchCriteria` ke service
- **🧪 Testability**: `RequestParams::from()` membangun params dari array biasa
- **📦 Modularity**: Setiap komponen memiliki satu tanggung jawab
- **🚀 Performance**: Parse sekali, teruskan objek immutable antar layer

Dengan mengikuti pola dan best practice yang diuraikan dalam panduan ini, Anda dapat membangun pemrosesan request yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
