# Panduan Query Building

## 📋 Gambaran Umum

Utility query building menyediakan cara terstruktur untuk membangun dan mengeksekusi query database di aplikasi Yii3 API. Komponen-komponen ini memastikan konstruksi query yang konsisten dan mencegah SQL injection.

---

## 🏗️ Arsitektur Query

### Struktur Direktori

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

### Prinsip Desain

#### **1. Type Safety**
- Pengetikan kuat dengan fitur PHP 8+
- Parameter binding via `Query::andWhere()` mencegah SQL injection
- DTO `readonly` untuk criteria dan result

#### **2. Flexibility**
- Method kondisi static yang composable — padu padankan `andWhere`/`orLike`/`andIn`/`andRange`
- Column whitelisting untuk filter yang digerakkan oleh request

#### **3. Security**
- `filterByExactMatch()` / `filterByLike()` hanya menerapkan kolom yang di-whitelist
- Nilai kosong (`null`/`''`) dilewati secara otomatis
- Kondisi terparameterisasi di seluruh kode

#### **4. Performance**
- Overhead minimal — wrapper tipis di atas `Query::andWhere()`
- Repository melakukan streaming baris dan meng-clone query untuk `count()`

---

## 📁 Komponen Query

### QueryConditionApplier

**Lokasi**: `src/Shared/Core/Query/QueryConditionApplier.php`

**Tujuan**: Helper static yang menerapkan filter kondisional ke objek `Yiisoft\Db\Query\Query`. Semua method menerima dan mengembalikan instance `Query` (objek di-mutate; nilai kembalian disediakan untuk kemudahan chaining).

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

**Catatan perilaku**:
- `andWhere`, `orWhere`, `andLike`, `orLike` melewatkan nilai di mana `isFilled()` bernilai false (`null` atau `''`)
- `andIn`/`orIn` melewatkan non-array dan array kosong
- `andRange` menerapkan `>=` untuk `min` dan `<=` untuk `max`, masing-masing opsional
- `orWhere`/`orLike`/`orIn` membungkus grup OR dalam satu klausa `andWhere(['or', ...])`
- `filterByExactMatch`/`filterByLike` meng-intersect `$filters` dengan `array_flip($allowedColumns)` — key yang tidak di-whitelist diabaikan

**Contoh Penggunaan**:
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

**Lokasi**: `src/Shared/Core/Dto/SearchCriteria.php`

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

- `getOrderClause()` memetakan `sortBy` melalui `allowedSort` (whitelist → kolom asli); field yang tidak dikenal jatuh kembali ke kolom pertama yang diizinkan. `sortDir` bernilai `'desc'` → `SORT_DESC`, selain itu → `SORT_ASC`.
- Payload request yang diharapkan: `{ "filter": {...}, "pagination": {"page": 1, "page_size": 10}, "sort": {"by": "name", "dir": "desc"} }`

### PaginatedResult

**Lokasi**: `src/Shared/Core/Dto/PaginatedResult.php`

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

**Lokasi**: `src/Application/Shared/Core/Factory/SearchCriteriaFactory.php`

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

Membangun `SearchCriteria` dari `RequestParams` (dihasilkan oleh `RequestParamsMiddleware`): `filter` dari `getFilter()->toArray()`, `page`/`pageSize` dari `getPagination()`, `sortBy`/`sortDir` dari `getSort()` (dir default `'desc'`).

---

## 🔧 Pola Integrasi

### 1. **Penggunaan Repository** (pola nyata dari `ExampleRepository`)

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

### 2. **Penggunaan Action / Service**

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

### 3. **Menyusun Kondisi Secara Manual**

```php
$query = (new Query($this->db))->from('example');

QueryConditionApplier::andWhere($query, ['status' => RecordStatus::ACTIVE->value]);
QueryConditionApplier::orLike($query, 'ilike', ['name' => "%{$term}%", 'detail_info' => "%{$term}%"]);
QueryConditionApplier::andIn($query, ['id' => $ids]);
QueryConditionApplier::andRange($query, ['created_at' => ['min' => $from, 'max' => $to]]);

$rows = $query->all();
```

---

## 🚀 Praktik Terbaik

### 1. **Whitelist Filter Request**
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

## 📊 Pertimbangan Performa

### 1. **Optimasi Query**
- Gunakan index yang sesuai untuk field yang difilter
- Lakukan count dengan query yang di-clone *sebelum* menerapkan limit/offset: `(clone $query)->count()`
- Select hanya kolom yang dibutuhkan

### 2. **Penerapan Kondisi**
- Whitelist filter; lewati nilai kosong secara otomatis via `isFilled`
- Utamakan `andIn` daripada banyak pemeriksaan equality `orWhere`

### 3. **Penggunaan Memori**
- Repository melakukan streaming baris (`streamRows()`) alih-alih memuat semuanya
- Jaga `pageSize` tetap terbatas — `app/pagination.maxPageSize` membatasinya via `RequestParamsMiddleware`

---

## 🎯 Ringkasan

Utility query building menyediakan cara yang terstruktur dan type-safe untuk membangun query database di aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🛡️ Security**: Kondisi terparameterisasi + whitelist kolom mencegah SQL injection
- **🔧 Flexibility**: Helper static yang composable (exact match, LIKE/ILIKE, IN, range)
- **📝 Type Safety**: DTO readonly `SearchCriteria`/`PaginatedResult`
- **🧪 Testability**: Method static murni yang mudah di-unit test
- **⚡ Performance**: Clone-untuk-count, streaming baris, ukuran halaman terbatas
- **🔄 Reusability**: Digunakan bersama antar repository via `QueryConditionApplier` yang di-inject

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun query building yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
