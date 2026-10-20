# Panduan Exception

## 📋 Gambaran Umum

Hierarki exception dalam aplikasi Yii3 API ini menyediakan cara terstruktur untuk menangani berbagai jenis error dan exception. Setiap class exception dirancang untuk menangani skenario error tertentu dengan HTTP status code yang tepat dan pesan error terlokalisasi yang dibawa oleh value object `Message`.

---

## 🏗️ Arsitektur Exception

### Struktur Direktori

```
src/Shared/Core/Exception/
├── HttpException.php              # Abstract base — status code + Message + errors + data
├── BadRequestException.php        # 400 Bad Request
├── UnauthorizedException.php      # 401 Unauthorized
├── ForbiddenException.php         # 403 Forbidden
├── NotFoundException.php          # 404 Not Found
├── ConflictException.php          # 409 Conflict
├── TooManyRequestsException.php   # 429 Too Many Requests
├── ValidationException.php        # 422 Unprocessable Entity (carries field errors)
├── OptimisticLockException.php    # 409 Conflict (optimistic locking failure)
├── NoChangesException.php         # 200 OK — rendered as success "no changes" response
├── ServiceException.php           # Configurable status (default 200) + data payload
└── README.md                      # Exception documentation
```

### Prinsip Desain

#### **1. HTTP Status Mapping**
- Setiap exception dipetakan ke HTTP status code via `getHttpStatusCode()` (juga diekspos sebagai `getCode()`)
- Format response error yang konsisten dihasilkan oleh `ExceptionResponderFactory` / `ErrorHandlerResponse`
- Kepatuhan RESTful API

#### **2. Structured Error Information**
- Value object `Message` yang terlokalisasi (`key`, `params`, `domain`)
- Array `errors` opsional untuk detail validasi per field
- Payload `data` opsional untuk konteks tambahan

#### **3. Localization Support**
- Setiap exception menerima `Message` atau string biasa (dikonversi menjadi `Message::create($string)`)
- `domain` default ke kategori terjemahan `error` saat di-render
- Key pesan default tersedia di `resources/messages/{locale}/error.php` dan `validation.php`

#### **4. Type Safety**
- Pengetikan kuat dengan fitur PHP 8+
- Class konkret `final` yang meng-extend `HttpException` abstrak
- Konstruktor yang ramah named-argument (`translate:`, `errors:`, `data:`, `previous:`)

---

## 📁 Komponen Exception

### 1. HttpException (Base Class)

**Lokasi**: `src/Shared/Core/Exception/HttpException.php`

**Tujuan**: Base abstrak untuk semua HTTP exception; menyimpan status code, `Message`, errors, dan data.

```php
abstract class HttpException extends \RuntimeException
{
    public function __construct(
        private readonly int $httpStatusCode,
        private readonly Message $translateMessage,
        private readonly ?array $errors = null,
        private readonly ?array $data = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct('', $httpStatusCode, $previous); // getCode() === HTTP status
    }

    public function getHttpStatusCode(): int;
    public function getTranslateMessage(): Message;
    public function getDefaultMessageKey(): string;   // $translateMessage->getKey()
    public function getTranslateParams(): array;      // $translateMessage->getParams()
    public function getErrors(): ?array;
    public function getData(): ?array;
}
```

**Value object `Message`** (`src/Shared/Core/ValueObject/Message.php`):

```php
final readonly class Message
{
    public function __construct(
        public string $key,
        public array $params = [],
        public ?string $domain = null,   // translation category: 'error' (default), 'validation', 'success', 'app'
    ) {}

    public static function create(string $key, array $params = [], ?string $domain = null): self;
    public function getKey(): string;
    public function getParams(): array;
    public function getDomain(): ?string;
}
```

### 2. Exception Konkret

Semua exception konkret bersifat `final` dan menerima `Message|string|null` untuk `translate` — string dibungkus via `Message::create($string)`. Saat `translate` bernilai `null`, key pesan default digunakan.

| Exception | HTTP | Signature | Key default |
|---|---|---|---|
| `BadRequestException` | 400 | `(Message\|string\|null $translate = null, ?array $errors = null, ?Throwable $previous = null)` | `http.bad_request` |
| `UnauthorizedException` | 401 | sama seperti di atas | `http.unauthorized` |
| `ForbiddenException` | 403 | sama seperti di atas | `http.forbidden` |
| `NotFoundException` | 404 | sama seperti di atas | `http.not_found` |
| `ConflictException` | 409 | sama seperti di atas | `resource.conflict` |
| `TooManyRequestsException` | 429 | sama seperti di atas | `http.too_many_requests` |
| `ValidationException` | 422 | `(?array $errors = null, Message\|string\|null $translate = null, ?Throwable $previous = null)` — **errors di posisi pertama** | `validation.failed` |
| `OptimisticLockException` | 409 | `(Message\|string\|null $translate = null, ?array $data = null, ?Throwable $previous = null)` | `optimistic.lock.failed` |
| `NoChangesException` | 200 | `(Message\|string\|null $translate = null, ?array $data = null, ?Throwable $previous = null)` | `resource.conflict` |
| `ServiceException` | dapat dikonfigurasi (default `Status::OK`) | `(Message\|string\|null $translate = null, ?array $data = null, ?int $code = Status::OK, ?Throwable $previous = null)` | `service.error` |

**Catatan**:
- `NoChangesException` ditangani khusus oleh `ExceptionResponderFactory` — di-render sebagai response **success** (HTTP 200) yang membawa `getData()` dan pesan yang diterjemahkan, bukan sebagai error.
- `ServiceException` membawa `$data`-nya via `getData()` (diteruskan sebagai slot `errors` secara internal) dan memungkinkan Anda memilih HTTP status apa pun via `code:`.
- **Tidak ada** `BusinessRuleException` — gunakan `ConflictException`, `ValidationException`, atau `ServiceException` dengan `code` yang sesuai untuk kegagalan business rule.

### Contoh Penggunaan

```php
use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\Exception\NotFoundException;
use App\Shared\Core\Exception\ValidationException;
use App\Shared\Core\ValueObject\Message;

// Default localized message
throw new BadRequestException();

// Plain string (becomes the message key)
throw new NotFoundException(translate: 'User not found');

// Localized message with params
throw new NotFoundException(
    translate: Message::create(
        key: 'resource.not_found',
        params: ['resource' => 'User', 'field' => 'id', 'value' => $id],
    ),
);

// Validation errors (note: errors is the FIRST parameter)
throw new ValidationException(
    errors: [
        'email'    => ['Invalid email format'],
        'password' => ['Password too short'],
    ],
);

// Service error with custom status and data payload
use Yiisoft\Http\Status;

throw new ServiceException(
    translate: Message::create(
        key: 'service.unavailable',
        params: ['service' => 'Payment Gateway'],
    ),
    data: ['provider' => 'stripe', 'error_code' => 'card_declined'],
    code: Status::SERVICE_UNAVAILABLE,
    previous: $e,
);

// Optimistic locking failure
throw new OptimisticLockException(
    translate: Message::create(
        key: 'optimistic.lock.failed',
        params: ['resource' => 'User'],
    ),
);
```

---

## 🔧 Pola Exception Handling

### 1. **Biarkan Middleware yang Menangani**

Middleware `ExceptionResponder` (dibangun oleh `App\Api\Shared\ExceptionResponderFactory`, terdaftar di `config/web/di/application.php`) mengonversi exception ke response JSON — tidak perlu try/catch di action:

```php
final class UpdateAction
{
    public function __invoke(int $id, UpdateRequest $request): ResponseInterface
    {
        // Just throw — middleware produces {code, success, message, errors}
        $result = $this->service->update($id, $request);

        return $this->responseFactory->success($result);
    }
}
```

### 2. **Service Layer**

```php
final class ExampleService
{
    public function update(int $id, array $data): Example
    {
        $example = $this->repository->findById($id);

        if ($example === null) {
            throw new NotFoundException(
                translate: Message::create(
                    key: 'resource.not_found',
                    params: ['resource' => 'Example', 'field' => 'id', 'value' => $id],
                ),
            );
        }

        if (!$example->canBeUpdated()) {
            throw new ConflictException(
                translate: Message::create(
                    key: 'resource.cannot_update',
                    params: ['resource' => 'Example', 'current_status' => (string) $example->getStatus(), 'status' => 'active'],
                ),
            );
        }

        $this->repository->update($example);
        return $example;
    }
}
```

### 3. **Repository / Infrastructure Layer**

```php
final class ExampleRepository
{
    public function save(Example $example): void
    {
        try {
            $this->db->createCommand()
                ->insert('example', $this->mapper->toArray($example))
                ->execute();
        } catch (\Throwable $e) {
            throw new ServiceException(
                translate: Message::create(
                    key: 'service.failed',
                ),
                data: ['operation' => 'save', 'error' => $e->getMessage()],
                code: Status::INTERNAL_SERVER_ERROR,
                previous: $e,
            );
        }
    }
}
```

---

## 🚀 Praktik Terbaik

### 1. **Pembuatan Exception**
```php
// ✅ Use specific exception types with Message keys
throw new NotFoundException(
    translate: Message::create(key: 'resource.not_found', params: ['resource' => 'User', 'field' => 'id', 'value' => $id]),
);

// ❌ Avoid generic exceptions
throw new RuntimeException('User not found');
```

### 2. **Pesan Error**
```php
// ✅ Provide context via params
throw new ValidationException(
    errors: ['email' => ['Invalid email format']],
);

// ❌ Avoid empty context when details exist
throw new ValidationException();
```

### 3. **HTTP Status Code**
```php
// ✅ Use appropriate status codes
throw new UnauthorizedException();  // 401
throw new ForbiddenException();     // 403
throw new NotFoundException();      // 404

// ❌ Avoid wrong status codes
throw new BadRequestException(translate: 'User not found'); // Should be 404
```

### 4. **Exception Chaining**
```php
// ✅ Chain exceptions for context
throw new ServiceException(
    translate: 'External API call failed',
    code: Status::BAD_GATEWAY,
    previous: $e,
);

// ❌ Lose the original exception
throw new ServiceException(translate: 'External API call failed');
```

---

## 📊 Pertimbangan Performa

### 1. **Overhead Exception**
- Exception itu mahal — gunakan hanya untuk kasus yang benar-benar exceptional
- Hindari penggunaan exception untuk flow control
- Gunakan validasi sebelum business logic

### 2. **Penggunaan Memori**
- Jaga payload `params`, `errors`, dan `data` tetap ringkas
- Hindari menyimpan objek besar di dalam exception
- Gunakan exception chaining dengan bijak

### 3. **Logging**
- Log exception dengan konteks yang tepat (`getDefaultMessageKey()`, `getTranslateParams()`, `getData()`)
- `ErrorMonitoringMiddleware` menangkap exception sesuai config `app/monitoring.error_monitoring`
- Gunakan log level yang sesuai

---

## 🎯 Ringkasan

Hierarki exception menyediakan cara terstruktur untuk menangani berbagai jenis error di aplikasi Yii3 API. Manfaat utamanya meliputi:

- **🔍 Type Safety**: Exception konkret `final` yang meng-extend `HttpException`
- **📡 HTTP Compliance**: Pemetaan HTTP status code yang tepat via `getHttpStatusCode()`
- **🌐 Localization**: Value object `Message` dengan `key`/`params`/`domain`
- **🔧 Debugging**: Payload `errors` dan `data`, trace mode-dev di response error
- **📦 Consistency**: Format error `{code, success, message, errors}` yang seragam
- **🚀 Performance**: Exception handling yang dioptimalkan

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun exception handling yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
