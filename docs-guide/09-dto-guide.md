# Panduan Data Transfer Objects (Dto)

## 📋 Gambaran Umum

Data Transfer Objects (DTO) adalah struktur data sederhana yang digunakan untuk mentransfer data antar proses atau layer. Dalam aplikasi Yii3 API ini, DTO menyediakan cara yang bersih untuk menstrukturkan data bagi komunikasi API dan transfer data internal.

---

## 🏗️ Arsitektur DTO

### Struktur Direktori

```
src/Shared/Core/Dto/
├── PaginatedResult.php    # Paginated query results
└── SearchCriteria.php     # Search and filtering criteria

src/Application/Example/
├── Command/
│   ├── CreateExampleCommand.php   # Create use-case input
│   └── UpdateExampleCommand.php   # Update use-case input
└── Dto/
    └── ExampleResponse.php        # Example response DTO

src/Application/AnotherExample/
├── Command/
│   ├── CreateAnotherExampleCommand.php
│   └── UpdateAnotherExampleCommand.php
└── Dto/
    ├── AnotherExampleResponse.php     # Response DTO
    └── AnotherExampleDetailInfo.php   # detail_info payload DTO
```

### Prinsip Desain

#### **1. **Immutability**
- DTO bersifat immutable menggunakan readonly properties PHP 8+
- Mencegah perubahan state yang tidak disengaja
- Memastikan integritas data

#### **2. **Type Safety**
- Strong typing dengan fitur PHP 8+
- Mencegah runtime error
- Memungkinkan auto-completion di IDE

#### **3. **Validation**
- Validasi bawaan di constructor
- Type hint menegakkan tipe data
- Metode validasi opsional

#### **4. **Serialization**
- Konversi mudah ke/dari array
- Dukungan serialisasi JSON
- Pemformatan respons API

---

## 📁 Komponen DTO

### 1. PaginatedResult

**Tujuan**: Struktur standar untuk hasil query terpaginasi

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\Dto;

/**
 * Data Transfer Object for paginated results (trimmed — see source for full docs).
 */
final readonly class PaginatedResult
{
    /**
     * @param array $data   The paginated data items
     * @param int   $total    Total number of items across all pages
     * @param int   $page     Current page number (1-based)
     * @param int   $pageSize Number of items per page
     * @param array $filter   Applied filters as key-value pairs
     * @param array $sort     Applied sorting as field-direction pairs
     */
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $pageSize,
        public array $filter = [],
        public array $sort = []
    ) {
        if ($this->pageSize <= 0) {
            $this->pageSize = 10;
        }
    }

    /**
     * Calculate the total number of pages (returns 1 for empty results).
     */
    public function getTotalPages(): int
    {
        return $this->total === 0 ? 1 : (int) \ceil($this->total / $this->pageSize);
    }

    /**
     * Metadata for API responses: applied filter, sort and pagination info.
     */
    public function getMeta(): array
    {
        return [
            'filter'     => $this->filter,
            'sort'       => $this->sort,
            'pagination' => [
                'total'     => $this->total,
                'display'   => \count($this->data),
                'page'      => $this->page,
                'page_size' => $this->pageSize,
            ],
        ];
    }
}
```

**Contoh Penggunaan**:
```php
// In repository — ExampleRepository::list()
return new PaginatedResult(
    data: $rows,
    total: $total,
    page: $criteria->page,
    pageSize: $criteria->pageSize,
    filter: $criteria->filter,
    sort: [
        'by'  => $criteria->sortBy,
        'dir' => $criteria->sortDir,
    ]
);

// In action — hand the meta block to ResponseFactory
$result = $this->applicationService->list(criteria: $criteria);

return $this->responseFactory->success(
    data: $result->data,
    translate: Message::create(
        key: 'resource.list_retrieved',
        params: ['resource' => $resource]
    ),
    meta: $result->getMeta(),
);
```

---

### 2. SearchCriteria

**Tujuan**: Struktur standar untuk parameter pencarian, filtering, dan pagination

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\Dto;

/**
 * Data Transfer Object for search criteria (trimmed — see source for full docs).
 */
final readonly class SearchCriteria
{
    /**
     * @param array    $filter      Search filters as key-value pairs
     * @param int      $page        Current page number (1-based)
     * @param int      $pageSize    Number of items per page (default: 10)
     * @param string   $sortBy      Field to sort by (default: 'id')
     * @param string   $sortDir     Sort direction 'asc' or 'desc' (default: 'desc')
     * @param int|null $offset      Manual offset override (optional)
     * @param array    $allowedSort Allowed sortable fields with column mapping
     */
    public function __construct(
        public array $filter,
        public int $page,
        public int $pageSize = 10,
        public string $sortBy = 'id',
        public string $sortDir = 'desc',
        public ?int $offset = null,
        private array $allowedSort = ['id' => 'id'],
    ) {
    }

    /**
     * Calculate the database offset: (page - 1) * pageSize.
     */
    public function calculateOffset(): int
    {
        return ($this->page - 1) * $this->pageSize;
    }

    /**
     * Order clause for the query builder, e.g. ['name' => SORT_ASC].
     * Falls back to the first allowed sort column when $sortBy is not allowed.
     */
    public function getOrderClause(): array
    {
        $column    = $this->allowedSort[$this->sortBy] ?? \array_values($this->allowedSort)[0];
        $direction = \strtolower($this->sortDir) === 'desc' ? SORT_DESC : SORT_ASC;

        return [$column => $direction];
    }
}
```

`SearchCriteria` biasanya tidak dibuat secara manual — action membangunnya melalui
`SearchCriteriaFactory` (`src/Application/Shared/Core/Factory/SearchCriteriaFactory.php`)
dari payload `RequestParams` yang di-parse oleh `RequestParamsMiddleware`:

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

**Contoh Penggunaan**:
```php
// In action (see ExampleDataAction)
/** @var RequestParams $payload */
$payload = $request->getAttribute('payload');

$criteria = $this->factory->createFromRequest(
    params: $payload,
    allowedSort: ['id' => 'id', 'name' => 'name', 'status' => 'status']
);

$result = $this->applicationService->list(criteria: $criteria);

// In repository (see ExampleRepository::list)
$query = (new Query($this->db))
    ->select(['id', 'name', 'status', 'detail_info', SyncMdb::field(), LockVersion::field()])
    ->from(self::TABLE_NAME)
    ->where($this->scopeWhereNotDeleted());

// Whitelisted exact-match filters + 'name' LIKE search
$this->queryConditionApplier->filterByExactMatch(
    query: $query,
    filters: $criteria->filter,
    allowedColumns: ['id', 'status', SyncMdb::field()]
);

$total = (clone $query)->count();

$query->orderBy($criteria->getOrderClause())
    ->limit($criteria->pageSize)
    ->offset($criteria->calculateOffset());
```

---

## 🔧 Pola Integrasi

### 1. **Penggunaan di Action**
```php
// src/Api/V1/Example/Action/ExampleDataAction.php (simplified)
final class ExampleDataAction
{
    private const ALLOWED_KEYS = ['id', 'name', 'status'];
    private const ALLOWED_SORT = ['id' => 'id', 'name' => 'name', 'status' => 'status'];

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

        $filter = $payload->getFilter()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->with('status', RecordStatus::DRAFT->value);

        $this->inputValidator->validate(data: $filter, context: ValidationContext::SEARCH);

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

### 2. **Penggunaan di Service**
```php
// src/Application/Example/ExampleApplicationService.php
public function list(SearchCriteria $criteria): PaginatedResult
{
    return $this->repository->list(criteria: $criteria);
}
```

### 3. **Penggunaan di Repository**
```php
// src/Infrastructure/Common/Persistence/Example/ExampleRepository.php (simplified)
public function list(SearchCriteria $criteria): PaginatedResult
{
    $query = (new Query($this->db))
        ->select(['id', 'name', 'status', 'detail_info', SyncMdb::field(), LockVersion::field()])
        ->from(self::TABLE_NAME)
        ->where($this->scopeWhereNotDeleted());

    $this->queryConditionApplier->filterByExactMatch(
        query: $query,
        filters: $criteria->filter,
        allowedColumns: ['id', 'status', SyncMdb::field()]
    );

    if (!empty($criteria->filter['name'])) {
        $this->queryConditionApplier->orLike(
            query: $query,
            operator: 'ilike',
            conditions: ['name' => $criteria->filter['name']]
        );
    }

    $total = (clone $query)->count();

    $query->orderBy($criteria->getOrderClause())
        ->limit($criteria->pageSize)
        ->offset($criteria->calculateOffset());

    return new PaginatedResult(
        data: \iterator_to_array($this->streamRows(query: $query, jsonKeys: [])),
        total: $total,
        page: $criteria->page,
        pageSize: $criteria->pageSize,
        filter: $criteria->filter,
        sort: ['by' => $criteria->sortBy, 'dir' => $criteria->sortDir]
    );
}
```

### 4. **DTO di Application Layer (Command & Response)**

Command membawa input yang sudah divalidasi ke application service; response DTO membawa
data entity kembali ke API layer:

```php
// src/Application/Example/Command/CreateExampleCommand.php
final readonly class CreateExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
        public ?array $detailInfo,
    ) {}

    public static function create(
        string $name,
        int $status,
        ?array $detailInfo = null,
    ): self {
        return new self(name: $name, status: $status, detailInfo: $detailInfo);
    }
}

// src/Application/Example/Dto/ExampleResponse.php
final readonly class ExampleResponse
{
    public function __construct(
        public int $id,
        public string $name,
        public int $status,
        public array $detail_info,
        public ?int $sync_mdb,
        public int $lock_version,
    ) {}

    public static function fromEntity(Example $entity): self { /* ... */ }

    public function toArray(): array
    {
        return \get_object_vars($this);
    }
}
```

`AnotherExample` menambahkan `example_id`, `origin_id` dan `sync_flag` pada command/response
DTO miliknya (lihat `CreateAnotherExampleCommand`, `AnotherExampleResponse`,
`AnotherExampleDetailInfo`).

---

## 🚀 Best Practices

### 1. **Immutability**
```php
// ✅ Use readonly properties
final readonly class CreateUserRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email
    ) {}
}

// ❌ Avoid mutable properties
class CreateUserRequest
{
    public string $name;
    public string $email;
}
```

### 2. **Validation**
```php
// ✅ Validate in constructor
final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
    }
}

// ❌ Avoid external validation
$email = new Email($input);
if (!$email->isValid()) {
    // Validation should be in constructor
}
```

### 3. **Type Safety**
```php
// ✅ Use specific types
final readonly class CreateUserRequest
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $active
    ) {}
}

// ❌ Avoid generic types
final readonly class CreateUserRequest
{
    public function __construct(
        public readonly $name,
        public readonly $age,
        public readonly $active
    ) {}
}
```

### 4. **Serialization**
```php
// ✅ Provide toArray method
public function toArray(): array
{
    return [
        'name' => $this->name,
        'email' => $this->email,
    ];
}

// ❌ Avoid direct property access
echo json_encode($dto);
```

---

## 📊 Pertimbangan Performa

### 1. **Penggunaan Memori**
- DTO ringan dan efisien dalam penggunaan memori
- Gunakan readonly properties untuk mencegah penyalinan
- Hindari pembuatan objek yang tidak perlu

### 2. **Serialisasi**
- Implementasikan metode toArray() yang efisien
- Gunakan serialisasi JSON untuk respons API
- Cache data hasil serialisasi bila diperlukan

### 3. **Validasi**
- Validasi di constructor agar fail fast
- Gunakan fungsi bawaan PHP untuk validasi
- Hindari logika validasi yang kompleks di DTO

---

## 🎯 Ringkasan

DTO menyediakan cara yang bersih dan type-safe untuk mentransfer data antar layer dalam aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🛡️ Type Safety**: Strong typing mencegah runtime error
- **🔄 Immutability**: Readonly properties memastikan integritas data
- **🧪 Testability**: Mudah di-unit test dengan perilaku yang dapat diprediksi
- **📦 Modularity**: Setiap DTO memiliki satu tanggung jawab
- **🚀 Performance**: Transfer data yang ringan dan efisien

Dengan mengikuti pola dan best practice yang diuraikan dalam panduan ini, Anda dapat membangun DTO yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
