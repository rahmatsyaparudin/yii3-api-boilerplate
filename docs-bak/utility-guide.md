# Utility Guide

## 📋 Overview

Utility functions provide helpful, reusable functionality for common operations throughout the Yii3 API application. These utilities simplify complex operations and ensure consistent behavior across the codebase.

---

## 🏗️ Utility Architecture

### Directory Structure

```
src/Shared/Core/Utility/
├── Arrays.php        # Dirty-checking and array diff utilities
├── FieldMapper.php   # Row field renaming/normalization helpers
└── JsonHandler.php   # JSON field encoding, decoding, and transformation
```

All utilities live in the `App\Shared\Core\Utility` namespace and are shared across every layer of the application.

### Design Principles

#### **1. **Reusability**
- Generic utility functions
- No coupling to specific domains
- Easy to use across different contexts

#### **2. **Performance**
- Optimized algorithms
- Minimal memory usage
- Efficient data processing

#### **3. **Type Safety**
- Strong typing with PHP 8+ features
- Proper parameter validation
- Clear return types

#### **4. **Error Handling**
- Graceful error handling
- Meaningful error messages
- Fail-safe behavior (`JsonHandler` returns safe fallbacks instead of throwing)

---

## 📁 Utility Components

### 1. Arrays

**Purpose**: Array diffing and "dirty data" detection used to decide whether an update actually changed anything.

`Arrays` is a `final` class with **static** methods only:

```php
use App\Shared\Core\Utility\Arrays;

// Remove null values (shallow)
$clean = Arrays::removeNulls($data);

// Check if $after differs from $before, ignoring excluded fields
$changed = Arrays::hasDirtyData($after, $before, ['detail_info.change_log']);

// Get only the fields that actually changed
$dirty = Arrays::getDirtyData($after, $before, ['sync_mdb']);

// Keys of $data minus the excluded keys
$updatable = Arrays::getUpdatableKeys($data, ['id', 'status', 'syncMdb']);

// Shorthand: any changes at all?
$hasChanges = Arrays::hasChanges($after, $before);

// Differences with old/new values
$diff = Arrays::getDifferences($after, $before);
// ['name' => ['old' => 'John', 'new' => 'Jane'], ...]
```

**Method Signatures**:
```php
public static function removeNulls(array $data): array
public static function hasDirtyData(array $after, array $before, array $exclude = []): bool
public static function getDirtyData(array $after, array $before, array $exclude = []): array
public static function getUpdatableKeys(array $data, array $exclude): array
public static function hasChanges(array $after, array $before): bool
public static function getDifferences(array $after, array $before): array
```

**Notes**:
- `getDirtyData` / `hasDirtyData` support **dot-notation excludes** such as `detail_info.change_log`, which skips the parent field when the excluded nested key is present — used to avoid false positives from audit-trail updates.
- `getDifferences` returns `['field' => ['old' => ..., 'new' => ...]]`, which is convenient for change logs.

**Real Usage** — the `Stateful` entity concern uses these helpers to detect whether an update payload changes anything:

```php
// src/Domain/Shared/Core/Concerns/Entity/Stateful.php
private const IMMUTABLE_FIELDS = ['id', 'status', 'syncMdb'];

public function hasFieldChanges(array $data, bool $removeNulls = true): bool
{
    $filteredData = $removeNulls ? Arrays::removeNulls($data) : $data;

    $updatableData = Arrays::getUpdatableKeys(
        data: $filteredData,
        exclude: self::IMMUTABLE_FIELDS
    );

    return !empty($updatableData);
}
```

---

### 2. FieldMapper

**Purpose**: Stateless helpers for generic database-row field transformations — renaming keys and normalizing zero values.

```php
use App\Shared\Core\Utility\FieldMapper;

// Rename keys: old key => new key
$row = FieldMapper::rename($row, ['full_name' => 'name', 'mail' => 'email']);

// Turn 0 / '0' into null for the listed columns
$row = FieldMapper::nullWhenZero($row, ['parent_id', 'dept_id']);

// Rename, then null-when-zero, in one call
$row = FieldMapper::map(
    row: $row,
    rename: ['full_name' => 'name'],
    nullWhenZero: ['parent_id'],
);
```

**Method Signatures**:
```php
public static function rename(array $row, array $map): array
public static function nullWhenZero(array $row, array $columns): array
public static function map(array $row, array $rename, array $nullWhenZero = []): array
```

**Notes**:
- `rename` only renames keys that actually exist in the row.
- `nullWhenZero` only converts strict `0` or `'0'` values — `false`, `''`, and missing keys are untouched.
- `map` applies `rename` first, then `nullWhenZero` against the **renamed** column names.

---

### 3. JsonHandler

**Purpose**: Encoding, decoding, and transforming JSON column data. Unlike `Arrays` and `FieldMapper`, `JsonHandler` is an **instance** class — call its methods on an object.

```php
use App\Shared\Core\Utility\JsonHandler;

$jsonHandler = new JsonHandler();
```

#### **Field casting**

```php
// Decode the listed JSON columns on a single row or a list of rows
$rows = $jsonHandler->handle($rows, ['detail_info', 'sync_mdb']);

// castFields() is an alias of handle()
$rows = $jsonHandler->castFields($rows, ['detail_info']);
```

`handle(array $data, array $jsonFields): array` detects list-of-rows data (when `$data[0]` is an array) and processes each row; each listed field that holds a JSON string is decoded into an array.

#### **Encoding / decoding**

```php
// Safe decode: returns $fallback on null, empty string, or invalid JSON
$data = $jsonHandler->decode($jsonString, fallback: []);

// Encode with JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
$json = $jsonHandler->encode($data);

// Full JSON validation
$isValid = $jsonHandler->isValid($jsonString);

// Quick shape check without full parsing (faster than isValid)
$maybe = $jsonHandler->looksLikeJson($value); // '{...}' or '[...]'
```

#### **Transformation helpers**

```php
// Deep-merge new data into an existing JSON document
$updated = $jsonHandler->merge($existingJson, ['profile' => ['theme' => 'dark']]);

// Nested array => dot-notation flat array
$flat = $jsonHandler->flatten(['user' => ['id' => 1]]); // ['user.id' => 1]

// Dot-notation flat array => nested array
$nested = $jsonHandler->expand(['user.id' => 1]); // ['user' => ['id' => 1]]

// Read a value from a JSON string via dot notation
$theme = $jsonHandler->get($json, 'profile.theme', 'light');

// Normalize mixed input into an array (decodes JSON strings, wraps scalars)
$arr = $jsonHandler->wrap($value);
```

#### **Safety helpers**

```php
// Recursively mask sensitive keys before logging
$safe = $jsonHandler->mask($data); // masks password, token, secret, key
$safe = $jsonHandler->mask($data, ['api_key', 'credit_card']);

// Recursively remove null / '' / [] values (e.g. before persisting to MongoDB)
$clean = $jsonHandler->filter($data);

// Deep-search a value inside a JSON document
$found = $jsonHandler->contains($json, 'dark');

// Pretty-printed JSON string
$pretty = $jsonHandler->pretty($data);
```

**Method Signatures**:
```php
public function handle(array $data, array $jsonFields): array
public function castFields(array $data, array $jsonFields): array
public function decode(?string $value, array $fallback = []): array
public function encode(mixed $value): string
public function isValid(string $value): bool
public function merge(string $existingJson, array $newData): string
public function flatten(array $array, string $prefix = ''): array
public function expand(array $flatArray): array
public function get(string $json, string $path, mixed $default = null): mixed
public function mask(array $data, array $sensitiveKeys = ['password', 'token', 'secret', 'key']): array
public function pretty(mixed $value): string
public function contains(string $json, mixed $needle): bool
public function filter(array $data): array
public function looksLikeJson(mixed $value): bool
public function wrap(mixed $value): array
```

**Notes**:
- `decode`, `encode`, and `merge` never throw — invalid input returns the fallback (`[]` by default) so callers get fail-safe behavior.
- `merge` uses `array_replace_recursive`, so nested keys are merged rather than overwritten wholesale.
- `mask` lowercases each key before comparing, so `Password`, `TOKEN`, etc. are all masked.

---

## 🔧 Integration Patterns

### 1. **Entity Integration — dirty checking**

```php
use App\Domain\Shared\Core\Concerns\Entity\Stateful;
use App\Shared\Core\Utility\Arrays;

final class Example
{
    use Stateful;

    public function update(array $data): void
    {
        // Skip no-op updates — hasFieldChanges() strips immutable fields
        if (!$this->hasFieldChanges($data)) {
            return;
        }

        // Or inspect the actual changed fields yourself
        $dirty = Arrays::getDirtyData($data, $this->toArray(), ['detail_info.change_log']);
    }
}
```

### 2. **Repository Integration — JSON columns**

```php
use App\Shared\Core\Utility\FieldMapper;
use App\Shared\Core\Utility\JsonHandler;
use Yiisoft\Db\Query\Query;

final class ExampleRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private JsonHandler $jsonHandler,
    ) {}

    public function findById(int $id): ?array
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->one();

        if ($row === null) {
            return null;
        }

        // Decode JSON columns and normalize field names
        $row = $this->jsonHandler->handle($row, ['detail_info', 'sync_mdb']);

        return FieldMapper::map(
            row: $row,
            rename: ['full_name' => 'name'],
            nullWhenZero: ['parent_id'],
        );
    }
}
```

### 3. **Service Integration — audit diffs**

```php
use App\Shared\Core\Utility\Arrays;

final class ExampleService
{
    public function update(Example $entity, array $data): void
    {
        $before = $entity->toArray();

        $entity->update($data);

        // Record what actually changed for the audit trail
        $changes = Arrays::getDifferences($entity->toArray(), $before);
    }
}
```

---

## 🚀 Best Practices

### 1. **Change Detection**
```php
// ✅ Use the utility methods
if (!Arrays::hasDirtyData($payload, $existing, ['detail_info.change_log'])) {
    return; // nothing changed
}

// ❌ Manual comparison
$changed = false;
foreach ($payload as $key => $value) {
    if (($existing[$key] ?? null) !== $value) {
        $changed = true;
    }
}
```

### 2. **JSON Field Processing**
```php
// ✅ Use JsonHandler for JSON columns
$rows = $this->jsonHandler->handle($rows, ['detail_info', 'sync_mdb']);

// ❌ Manual per-row decoding without fallbacks
foreach ($rows as &$row) {
    $row['detail_info'] = json_decode($row['detail_info'], true); // null on bad JSON
}
```

### 3. **Data Transformation**
```php
// ✅ Use FieldMapper for row normalization
$row = FieldMapper::map($row, ['full_name' => 'name'], ['parent_id']);

// ❌ Manual renaming
$row['name'] = $row['full_name'];
unset($row['full_name']);
if ($row['parent_id'] === 0) {
    $row['parent_id'] = null;
}
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- `Arrays::getDirtyData` / `getDifferences` only iterate `$after` — keep update payloads small.
- `JsonHandler::handle` maps rows without copying unchanged fields.

### 2. **Processing Speed**
- Prefer `JsonHandler::looksLikeJson()` over `isValid()` when you only need a shape check — it skips full parsing.
- Use `JsonHandler::get()` to read a single value instead of decoding a whole document yourself.

### 3. **JSON Processing**
- `decode()` never throws — always pass a meaningful `fallback` instead of checking for exceptions.
- Use `filter()` before persisting JSON to MongoDB to drop null/empty values.

---

## 🎯 Summary

Utility functions provide essential, reusable functionality for the Yii3 API application. Key benefits include:

- **🔄 Reusability**: Generic functions for common tasks
- **🛡️ Type Safety**: Strong typing and validation
- **⚡ Performance**: Optimized algorithms
- **🧪 Testability**: Easy to unit test
- **📦 Modularity**: Focused, single-purpose functions
- **🚀 Efficiency**: Minimal overhead and fast execution

The three utilities cover distinct concerns:

| Utility | Style | Purpose |
|---|---|---|
| `Arrays` | static | Dirty-data detection and array diffing |
| `FieldMapper` | static | Row field renaming and zero-to-null normalization |
| `JsonHandler` | instance | JSON column casting, encoding, and transformation |

By following the patterns and best practices outlined in this guide, you can build efficient, maintainable utility functions for your Yii3 API application! 🚀
