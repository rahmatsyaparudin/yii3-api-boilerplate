# Data Transfer Objects (Dto) Guide

## 📋 Overview

Data Transfer Objects (DTOs) are simple data structures used to transfer data between processes or layers. In this Yii3 API application, DTOs provide a clean way to structure data for API communication and internal data transfer.

---

## 🏗️ DTO Architecture

### Directory Structure

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

### Design Principles

#### **1. **Immutability**
- DTOs are immutable using PHP 8+ readonly properties
- Prevents accidental state changes
- Ensures data integrity

#### **2. **Type Safety**
- Strong typing with PHP 8+ features
- Prevents runtime errors
- Enables IDE auto-completion

#### **3. **Validation**
- Built-in validation in constructors
- Type hints enforce data types
- Optional validation methods

#### **4. **Serialization**
- Easy conversion to/from arrays
- JSON serialization support
- API response formatting

---

## 📁 DTO Components

### 1. PaginatedResult

**Purpose**: Standardized structure for paginated query results

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

**Usage Example**:
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

**Purpose**: Standardized structure for search, filtering, and pagination parameters

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

`SearchCriteria` is normally not constructed by hand — actions build it through
`SearchCriteriaFactory` (`src/Application/Shared/Core/Factory/SearchCriteriaFactory.php`)
from the `RequestParams` payload parsed by `RequestParamsMiddleware`:

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

**Usage Example**:
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

## 🔧 Integration Patterns

### 1. **Action Usage**
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

### 2. **Service Usage**
```php
// src/Application/Example/ExampleApplicationService.php
public function list(SearchCriteria $criteria): PaginatedResult
{
    return $this->repository->list(criteria: $criteria);
}
```

### 3. **Repository Usage**
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

### 4. **Application-Layer DTOs (Commands & Responses)**

Commands carry validated input into the application service; response DTOs carry
entity data back to the API layer:

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

`AnotherExample` adds `example_id`, `origin_id` and `sync_flag` to its command/response
DTOs (see `CreateAnotherExampleCommand`, `AnotherExampleResponse`,
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

## 📊 Performance Considerations

### 1. **Memory Usage**
- DTOs are lightweight and memory-efficient
- Use readonly properties to prevent copying
- Avoid unnecessary object creation

### 2. **Serialization**
- Implement efficient toArray() methods
- Use JSON serialization for API responses
- Cache serialized data when appropriate

### 3. **Validation**
- Validate in constructor to fail fast
- Use built-in PHP functions for validation
- Avoid complex validation logic in DTOs

---

## 🎯 Summary

DTOs provide a clean, type-safe way to transfer data between layers in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: Strong typing prevents runtime errors
- **🔄 Immutability**: Readonly properties ensure data integrity
- **🧪 Testability**: Easy to unit test with predictable behavior
- **📦 Modularity**: Each DTO has a single responsibility
- **🚀 Performance**: Lightweight and efficient data transfer

By following the patterns and best practices outlined in this guide, you can build robust, maintainable DTOs for your Yii3 API application! 🚀
