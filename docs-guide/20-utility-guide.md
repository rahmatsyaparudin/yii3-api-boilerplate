# Panduan Utility

## 📋 Gambaran Umum

Fungsi-fungsi utility menyediakan fungsionalitas bermanfaat yang dapat digunakan kembali untuk operasi-operasi umum di seluruh aplikasi Yii3 API. Utility ini menyederhanakan operasi yang kompleks dan memastikan perilaku yang konsisten di seluruh codebase.

---

## 🏗️ Arsitektur Utility

### Struktur Direktori

```
src/Shared/Core/Utility/
├── Arrays.php        # Dirty-checking and array diff utilities
├── FieldMapper.php   # Row field renaming/normalization helpers
└── JsonHandler.php   # JSON field encoding, decoding, and transformation
```

Semua utility berada di namespace `App\Shared\Core\Utility` dan digunakan bersama di setiap layer aplikasi.

### Prinsip Desain

#### **1. **Reusability**
- Fungsi-fungsi utility generik
- Tidak ada coupling ke domain tertentu
- Mudah digunakan di berbagai konteks

#### **2. **Performance**
- Algoritma yang dioptimalkan
- Penggunaan memori minimal
- Pemrosesan data yang efisien

#### **3. **Type Safety**
- Pengetikan kuat dengan fitur PHP 8+
- Validasi parameter yang tepat
- Return type yang jelas

#### **4. **Error Handling**
- Penanganan error yang graceful
- Pesan error yang bermakna
- Perilaku fail-safe (`JsonHandler` mengembalikan fallback yang aman alih-alih melempar exception)

---

## 📁 Komponen Utility

### 1. Arrays

**Tujuan**: Diffing array dan deteksi "dirty data" yang digunakan untuk memutuskan apakah sebuah update benar-benar mengubah sesuatu.

`Arrays` adalah class `final` yang hanya berisi method **static**:

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

**Catatan**:
- `getDirtyData` / `hasDirtyData` mendukung **exclude dot-notation** seperti `detail_info.change_log`, yang melewatkan field parent ketika nested key yang dikecualikan ada — digunakan untuk menghindari false positive dari update audit-trail.
- `getDifferences` mengembalikan `['field' => ['old' => ..., 'new' => ...]]`, yang praktis untuk change log.

**Penggunaan Nyata** — concern entity `Stateful` menggunakan helper ini untuk mendeteksi apakah payload update mengubah sesuatu:

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

**Tujuan**: Helper stateless untuk transformasi field baris database secara generik — mengganti nama key dan menormalisasi nilai nol.

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

**Catatan**:
- `rename` hanya mengganti nama key yang benar-benar ada di baris.
- `nullWhenZero` hanya mengonversi nilai strict `0` atau `'0'` — `false`, `''`, dan key yang tidak ada dibiarkan utuh.
- `map` menerapkan `rename` terlebih dahulu, lalu `nullWhenZero` terhadap nama kolom yang **sudah diganti**.

---

### 3. JsonHandler

**Tujuan**: Encoding, decoding, dan transformasi data kolom JSON. Berbeda dengan `Arrays` dan `FieldMapper`, `JsonHandler` adalah class **instance** — panggil method-nya pada objek.

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

`handle(array $data, array $jsonFields): array` mendeteksi data berupa list-of-rows (ketika `$data[0]` adalah array) dan memproses setiap baris; setiap field yang terdaftar dan berisi string JSON di-decode menjadi array.

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

**Catatan**:
- `decode`, `encode`, dan `merge` tidak pernah melempar exception — input yang tidak valid mengembalikan fallback (`[]` secara default) sehingga caller mendapat perilaku fail-safe.
- `merge` menggunakan `array_replace_recursive`, sehingga nested key digabungkan, bukan ditimpa sepenuhnya.
- `mask` mengubah setiap key menjadi huruf kecil sebelum membandingkan, sehingga `Password`, `TOKEN`, dll. semuanya ikut di-mask.

---

## 🔧 Pola Integrasi

### 1. **Integrasi Entity — dirty checking**

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

### 2. **Integrasi Repository — kolom JSON**

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

### 3. **Integrasi Service — audit diff**

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

## 🚀 Praktik Terbaik

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

## 📊 Pertimbangan Performa

### 1. **Penggunaan Memori**
- `Arrays::getDirtyData` / `getDifferences` hanya mengiterasi `$after` — jaga payload update tetap kecil.
- `JsonHandler::handle` memetakan baris tanpa menyalin field yang tidak berubah.

### 2. **Kecepatan Pemrosesan**
- Utamakan `JsonHandler::looksLikeJson()` daripada `isValid()` ketika Anda hanya butuh pemeriksaan bentuk — method ini melewatkan parsing penuh.
- Gunakan `JsonHandler::get()` untuk membaca satu nilai, alih-alih men-decode seluruh dokumen sendiri.

### 3. **Pemrosesan JSON**
- `decode()` tidak pernah melempar exception — selalu berikan `fallback` yang bermakna alih-alih memeriksa exception.
- Gunakan `filter()` sebelum menyimpan JSON ke MongoDB untuk membuang nilai null/kosong.

---

## 🎯 Ringkasan

Fungsi-fungsi utility menyediakan fungsionalitas esensial yang dapat digunakan kembali untuk aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🔄 Reusability**: Fungsi generik untuk tugas-tugas umum
- **🛡️ Type Safety**: Pengetikan kuat dan validasi
- **⚡ Performance**: Algoritma yang dioptimalkan
- **🧪 Testability**: Mudah di-unit test
- **📦 Modularity**: Fungsi yang fokus dan single-purpose
- **🚀 Efficiency**: Overhead minimal dan eksekusi cepat

Ketiga utility mencakup concern yang berbeda:

| Utility | Gaya | Tujuan |
|---|---|---|
| `Arrays` | static | Deteksi dirty-data dan diffing array |
| `FieldMapper` | static | Penggantian nama field baris dan normalisasi nol-ke-null |
| `JsonHandler` | instance | Casting, encoding, dan transformasi kolom JSON |

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun fungsi utility yang efisien dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
