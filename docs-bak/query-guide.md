# Query Building Guide

## 📋 Overview

Query building utilities provide a structured way to build and execute database queries in the Yii3 API application. These components ensure consistent query construction and prevent SQL injection.

---

## 🏗️ Query Architecture

### Directory Structure

```
src/Shared/Core/Query/
└── QueryConditionApplier.php    # Static condition helpers for Yiisoft\Db\Query\Query

src/Shared/Core/Dto/
├── SearchCriteria.php           # filter + page/pageSize + sort DTO
└── PaginatedResult.php          # data + total + page meta DTO

src/Shared/Core/Request/
├── RequestParams.php            # filter/sort/pagination request params
├── PaginationParams.php
├── SortParams.php
├── RawParams.php                # raw params + sanitize() via InputSanitizer
├── DataParserInterface.php      # parser contract
└── RequestDataParser.php        # request body/query parser implementation

src/Application/Shared/Core/Factory/
└── SearchCriteriaFactory.php    # Builds SearchCriteria from RequestParams
```

### Design Principles

#### **1. Type Safety**
- Strong typing with PHP 8+ features
- Parameter binding via `Query::andWhere()` prevents SQL injection
- `readonly` DTOs for criteria and results

#### **2. Flexibility**
- Composable static condition methods — mix and match `andWhere`/`orLike`/`andIn`/`andRange`
- Column whitelisting for request-driven filters

#### **3. Security**
- `filterByExactMatch()` / `filterByLike()` only apply whitelisted columns
- Empty (`null`/`''`) values are skipped automatically
- Parameterized conditions throughout

#### **4. Performance**
- Minimal overhead — thin wrappers around `Query::andWhere()`
- Repositories stream rows and clone the query for `count()`

---

## 📁 Query Components

### QueryConditionApplier

**Location**: `src/Shared/Core/Query/QueryConditionApplier.php`

**Purpose**: Static helpers that apply conditional filters to `Yiisoft\Db\Query\Query` objects. All methods accept and return the `Query` instance (the object is mutated; the return is for chaining convenience).

```php
final class QueryConditionApplier
{
    // Whitelisted filters
    public static function filterByExactMatch(Query $query, array $filters, array $allowedColumns): Query;
    public static function filterByLike(
        Query $query,
        array $filters,
        array $allowedColumns,
        string $operator = 'like',
        bool $autoWrap = true,   // wraps value in %...% when it has no '%'
    ): Query;

    // Equality
    public static function andWhere(Query $query, array $conditions): Query;
    public static function orWhere(Query $query, array $conditions): Query;   // AND (a = x OR b = y)

    // LIKE / ILIKE
    public static function andLike(Query $query, string $operator, array $conditions): Query;
    public static function orLike(Query $query, string $operator, array $conditions): Query; // AND (a LIKE x OR b LIKE y)

    // IN
    public static function andIn(Query $query, array $conditions): Query;     // column => array of values
    public static function orIn(Query $query, array $conditions): Query;      // AND (a IN (...) OR b IN (...))

    // Ranges (min/max → >= / <=)
    public static function andRange(Query $query, array $ranges): Query;      // column => ['min' => ..., 'max' => ...]

    private static function isFilled(mixed $value): bool; // $value !== null && $value !== ''
}
```

**Behavior notes**:
- `andWhere`, `orWhere`, `andLike`, `orLike` skip values where `isFilled()` is false (`null` or `''`)
- `andIn`/`orIn` skip non-arrays and empty arrays
- `andRange` applies `>=` for `min` and `<=` for `max`, each optional
- `orWhere`/`orLike`/`orIn` wrap the OR group in a single `andWhere(['or', ...])` clause
- `filterByExactMatch`/`filterByLike` intersect `$filters` with `array_flip($allowedColumns)` — non-whitelisted keys are ignored

**Usage Examples**:
```php
use App\Shared\Core\Query\QueryConditionApplier;
use Yiisoft\Db\Query\Query;

// Whitelisted exact-match filters (safe for request input)
QueryConditionApplier::filterByExactMatch(
    query: $query,
    filters: $criteria->filter,
    allowedColumns: ['id', 'status', 'sync_mdb'],
);

// Case-insensitive multi-field search → AND (name ILIKE %x% OR email ILIKE %x%)
QueryConditionApplier::orLike($query, 'ilike', [
    'name'  => "%{$search}%",
    'email' => "%{$search}%",
]);

// IN filter → AND status IN (1, 2)
QueryConditionApplier::andIn($query, [
    'status' => [RecordStatus::ACTIVE->value, RecordStatus::DRAFT->value],
]);

// Date/price ranges → created_at >= min AND created_at <= max
QueryConditionApplier::andRange($query, [
    'created_at' => ['min' => '2024-01-01', 'max' => '2024-12-31'],
]);
```

### SearchCriteria

**Location**: `src/Shared/Core/Dto/SearchCriteria.php`

```php
final readonly class SearchCriteria
{
    public function __construct(
        public array $filter,
        public int $page,
        public int $pageSize = 10,
        public string $sortBy = 'id',
        public string $sortDir = 'desc',
        public ?int $offset = null,
        private array $allowedSort = ['id' => 'id'],
    ) {}

    public function calculateOffset(): int;   // ($page - 1) * $pageSize
    public function getOrderClause(): array;  // ['column' => SORT_ASC|SORT_DESC]
}
```

- `getOrderClause()` maps `sortBy` through `allowedSort` (whitelist → real column); unknown fields fall back to the first allowed column. `sortDir` of `'desc'` → `SORT_DESC`, anything else → `SORT_ASC`.
- Expected request payload: `{ "filter": {...}, "pagination": {"page": 1, "page_size": 10}, "sort": {"by": "name", "dir": "desc"} }`

### PaginatedResult

**Location**: `src/Shared/Core/Dto/PaginatedResult.php`

```php
final readonly class PaginatedResult
{
    public function __construct(
        public array $data,
        public int $total,
        public int $page,
        public int $pageSize,
        public array $filter = [],
        public array $sort = []
    ) {}

    public function getTotalPages(): int; // ceil(total / pageSize), min 1
    public function getMeta(): array;
    // ['filter' => ..., 'sort' => ..., 'pagination' => ['total', 'display', 'page', 'page_size']]
}
```

### SearchCriteriaFactory

**Location**: `src/Application/Shared/Core/Factory/SearchCriteriaFactory.php`

```php
final class SearchCriteriaFactory
{
    public function createFromRequest(
        RequestParams $params,
        array $allowedSort,
        int $defaultPageSize = 15
    ): SearchCriteria;
}
```

Builds `SearchCriteria` from `RequestParams` (produced by `RequestParamsMiddleware`): `filter` from `getFilter()->toArray()`, `page`/`pageSize` from `getPagination()`, `sortBy`/`sortDir` from `getSort()` (default dir `'desc'`).

---

## 🔧 Integration Patterns

### 1. **Repository Usage** (actual pattern from `ExampleRepository`)

```php
use App\Shared\Core\Dto\PaginatedResult;
use App\Shared\Core\Dto\SearchCriteria;
use App\Shared\Core\Query\QueryConditionApplier;
use Yiisoft\Db\Connection\ConnectionInterface;
use Yiisoft\Db\Query\Query;

final class ExampleRepository implements ExampleRepositoryInterface
{
    public function __construct(
        private ConnectionInterface $db,
        private QueryConditionApplier $queryConditionApplier,
    ) {}

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        $query = (new Query($this->db))
            ->select(['id', 'name', 'status', 'detail_info', 'sync_mdb', 'lock_version'])
            ->from(self::TABLE_NAME)
            ->where($this->scopeWhereNotDeleted());

        $filter = $criteria->filter;

        // Whitelisted exact-match filters
        $this->queryConditionApplier->filterByExactMatch(
            query: $query,
            filters: $filter,
            allowedColumns: ['id', 'status', 'sync_mdb'],
        );

        // Optional text search on a single column
        if (!empty($filter['name'])) {
            $this->queryConditionApplier->orLike(
                query: $query,
                operator: 'ilike',
                conditions: ['name' => $filter['name']],
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
            sort: ['by' => $criteria->sortBy, 'dir' => $criteria->sortDir],
        );
    }
}
```

### 2. **Action / Service Usage**

```php
// Action: RequestParams is set as the 'payload' attribute by RequestParamsMiddleware
final class ExampleDataAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $criteria = $this->searchCriteriaFactory->createFromRequest(
            params: $payload,
            allowedSort: ['id' => 'id', 'name' => 'name', 'status' => 'status'],
        );

        $result = $this->service->list(criteria: $criteria); // → repository->list($criteria)

        return $this->responseFactory->success(
            data: $result->data,
            meta: $result->getMeta(),
        );
    }
}
```

### 3. **Composing Conditions Manually**

```php
$query = (new Query($this->db))->from('example');

QueryConditionApplier::andWhere($query, ['status' => RecordStatus::ACTIVE->value]);
QueryConditionApplier::orLike($query, 'ilike', ['name' => "%{$term}%", 'detail_info' => "%{$term}%"]);
QueryConditionApplier::andIn($query, ['id' => $ids]);
QueryConditionApplier::andRange($query, ['created_at' => ['min' => $from, 'max' => $to]]);

$rows = $query->all();
```

---

## 🚀 Best Practices

### 1. **Whitelist Request Filters**
```php
// ✅ Only whitelisted columns can be filtered from request input
QueryConditionApplier::filterByExactMatch($query, $criteria->filter, ['id', 'status']);

// ❌ Passing raw request filters without a whitelist
$query->andWhere($criteria->filter);
```

### 2. **Parameter Binding**
```php
// ✅ Use parameterized conditions via the applier / Query::andWhere
QueryConditionApplier::orLike($query, 'ilike', ['name' => "%{$term}%"]);

// ❌ Manual string concatenation
$query->andWhere("name LIKE '%" . $term . "%'");
```

### 3. **Sorting via allowedSort**
```php
// ✅ Map sort fields through SearchCriteria::getOrderClause() (whitelist)
$query->orderBy($criteria->getOrderClause());

// ❌ Trusting the client's sort field directly
$query->orderBy([$request->get('sort') => SORT_ASC]);
```

---

## 📊 Performance Considerations

### 1. **Query Optimization**
- Use appropriate indexes for filtered fields
- Count with a cloned query *before* applying limit/offset: `(clone $query)->count()`
- Select only needed columns

### 2. **Condition Application**
- Whitelist filters; skip empty values automatically via `isFilled`
- Prefer `andIn` over many `orWhere` equality checks

### 3. **Memory Usage**
- Repositories stream rows (`streamRows()`) instead of loading everything
- Keep `pageSize` bounded — `app/pagination.maxPageSize` caps it via `RequestParamsMiddleware`

---

## 🎯 Summary

Query building utilities provide a structured, type-safe way to build database queries in the Yii3 API application. Key benefits include:

- **🛡️ Security**: Parameterized conditions + column whitelists prevent SQL injection
- **🔧 Flexibility**: Composable static helpers (exact match, LIKE/ILIKE, IN, ranges)
- **📝 Type Safety**: `SearchCriteria`/`PaginatedResult` readonly DTOs
- **🧪 Testability**: Pure static methods are easy to unit test
- **⚡ Performance**: Clone-for-count, streaming rows, bounded page sizes
- **🔄 Reusability**: Shared across repositories via injected `QueryConditionApplier`

By following the patterns and best practices outlined in this guide, you can build robust, maintainable query building for your Yii3 API application! 🚀
