# Panduan Error Handling

## 📋 Gambaran Umum

Utility error handling menyediakan pemrosesan error terpusat, pemformatan response, dan manajemen exception untuk aplikasi Yii3 API. Komponen-komponen ini memastikan response error yang konsisten dan logging error yang tepat.

---

## 🏗️ Arsitektur Error Handling

### Struktur Direktori

```
src/Shared/Core/ErrorHandler/
└── ErrorHandlerResponse.php       # ThrowableRendererInterface → JSON ErrorData

src/Api/Shared/
├── ExceptionResponderFactory.php  # Builds Yiisoft ExceptionResponder middleware
├── NotFoundMiddleware.php         # Terminal 404 responder for unmatched routes
├── ResponseFactory.php            # success() / fail() / notFound() / failValidation()
└── Presenter/
    ├── AsIsPresenter.php
    ├── CollectionPresenter.php
    ├── FailPresenter.php
    ├── OffsetPaginatorPresenter.php
    ├── PresenterInterface.php
    ├── SuccessPresenter.php
    ├── SuccessWithMetaPresenter.php
    └── ValidationResultPresenter.php

src/Infrastructure/Core/Monitoring/
└── ErrorMonitoringMiddleware.php  # Captures errors/exceptions for monitoring
```

### Prinsip Desain

#### **1. Consistency**
- Format response error terstandar: `{code, success: false, message, errors}`
- HTTP status code yang konsisten digerakkan oleh `HttpException::getHttpStatusCode()`
- Struktur pesan error yang seragam via value object `Message` yang terlokalisasi

#### **2. Centralization**
- `Yiisoft\ErrorHandler\Middleware\ErrorCatcher` menangkap throwable yang tidak tertangani
- `Yiisoft\ErrorHandler\Middleware\ExceptionResponder` (dibangun oleh `ExceptionResponderFactory`) memetakan exception ke response
- `ErrorHandlerResponse` me-render body error JSON akhir

#### **3. Security**
- Stack trace (key `trace`) hanya saat `APP_ENV=dev` **dan** `APP_DEBUG=1`
- Business exception dari `App\Shared\Core\Exception` tidak pernah membocorkan internal
- Detail sensitif tidak masuk ke response; middleware monitoring mengontrol apa yang di-log

#### **4. Debuggability**
- `renderVerbose()` / mode dev menambahkan `trace` berisi type, message, code, file, line, dan hingga 10 stack frame
- `ErrorMonitoringMiddleware` dapat menangkap exception sesuai params `app/monitoring.error_monitoring`

---

## 📁 Komponen Error Handling

### 1. ErrorHandlerResponse

**Lokasi**: `src/Shared/Core/ErrorHandler/ErrorHandlerResponse.php`

**Tujuan**: Me-render `Throwable` apa pun menjadi body error JSON terstandar. Mengimplementasikan `Yiisoft\ErrorHandler\ThrowableRendererInterface` dan digunakan oleh middleware `ErrorCatcher` bawaan framework.

```php
final readonly class ErrorHandlerResponse implements ThrowableRendererInterface
{
    public function render(\Throwable $t, ?ServerRequestInterface $request = null): ErrorData;
    public function renderVerbose(\Throwable $t, ?ServerRequestInterface $request = null): ErrorData;
}
```

**Format response** (`formatErrorResponse()`):
```json
{
    "code": 500,
    "success": false,
    "message": "Something went wrong",
    "errors": []
}
```

- `errors` hanya diisi untuk `ValidationException` (dari `$e->getErrors()`)
- `renderVerbose()` menambahkan objek `trace`: `{type, message, code, file, line, trace: [...10 frames]}`

**Contoh Penggunaan**:
```php
$renderer = new ErrorHandlerResponse();
$errorData = $renderer->render($exception, $request);
// $errorData->getBody()    → JSON string
// $errorData->getHeaders() → ['Content-Type' => 'application/json']
```

### 2. ExceptionResponderFactory

**Lokasi**: `src/Api/Shared/ExceptionResponderFactory.php`

**Tujuan**: Membangun middleware `Yiisoft\ErrorHandler\Middleware\ExceptionResponder` dengan handler per tipe exception. Terdaftar di middleware stack dalam `config/web/di/application.php`.

```php
final readonly class ExceptionResponderFactory
{
    public function __construct(
        private ResponseFactoryInterface $psrResponseFactory,
        private ResponseFactory $apiResponseFactory,
        private TranslatorInterface $translator,
        private Injector $injector,
    ) {}

    public function create(): ExceptionResponder
    {
        return new ExceptionResponder(
            [
                InputValidationException::class => $this->inputValidationException(...),
                NoChangesException::class       => $this->noChangesException(...),
                \Throwable::class               => $this->throwable(...),
            ],
            $this->psrResponseFactory,
            $this->injector,
        );
    }
}
```

**Pemetaan handler**:

| Exception | Hasil |
|---|---|
| `Yiisoft\Input\Http\InputValidationException` | `ResponseFactory::failValidation($result)` → HTTP 422 dengan field error |
| `NoChangesException` | `ResponseFactory::success(data, translate)` → response HTTP 200 "no changes" |
| `HttpException` (dan subclass-nya) | Error JSON dengan status `$e->getCode()` dan `getTranslateMessage()` yang diterjemahkan |
| `Yiisoft\ErrorHandler\Exception\UserException` | HTTP 400 dengan pesan exception |
| `\Throwable` lainnya | Error JSON HTTP 500 |

**Format response throwable**:
```json
{
    "code": 404,
    "success": false,
    "message": "The requested resource was not found",
    "errors": []
}
```

- Untuk `HttpException`, `message` diterjemahkan via `TranslatorInterface::translate($message->getKey(), $message->getParams(), $message->getDomain() ?? 'error')`.
- Untuk `ValidationException`, `errors` berisi error per field.
- Key `trace` ditambahkan hanya saat `APP_ENV=dev` **dan** `APP_DEBUG=1`, dan exception tersebut bukan business exception dari `App\Shared\Core\Exception` (kecuali `ValidationException`, yang boleh menampilkan detail di dev).

### 3. ResponseFactory

**Lokasi**: `src/Api/Shared/ResponseFactory.php`

**Tujuan**: Menghasilkan response API yang konsisten menggunakan class-class presenter. Digunakan oleh `ExceptionResponderFactory` dan oleh action secara langsung.

```php
final readonly class ResponseFactory
{
    public function success(
        array|object|null $data = null,
        ?array $meta = null,
        string|Message|null $translate = null,
        PresenterInterface $presenter = new AsIsPresenter(),
    ): ResponseInterface;

    public function fail(
        array|object|null $data = null,
        PresenterInterface $presenter = new AsIsPresenter(),
        string|Message|null $translate = null,
        ?int $httpCode = Status::BAD_REQUEST,
    ): ResponseInterface;

    public function notFound(string $message = 'Not found.'): ResponseInterface;

    public function failValidation(Result $result): ResponseInterface;
}
```

- `success()` — `SuccessPresenter`, atau `SuccessWithMetaPresenter` saat `$meta` diberikan; `Message` diterjemahkan dengan domain default `'success'`
- `fail()` — `FailPresenter` dengan HTTP code yang diberikan; domain `Message` default ke `'error'`
- `notFound()` — `fail()` dengan `Message::create(key: 'http.not_found')` dan HTTP 404
- `failValidation()` — `fail()` dengan `ValidationResultPresenter`, `Message::create(key: 'validation.failed')`, HTTP 422

### 4. NotFoundMiddleware

**Lokasi**: `src/Api/Shared/NotFoundMiddleware.php`

Middleware terminal yang ditempatkan **setelah** `Router` di stack — mengembalikan `ResponseFactory::notFound()` untuk request apa pun yang mencapainya (yaitu, tidak ada route yang cocok).

### 5. ErrorMonitoringMiddleware

**Lokasi**: `src/Infrastructure/Core/Monitoring/ErrorMonitoringMiddleware.php`

Menangkap exception dan error PHP untuk monitoring sesuai params `app/monitoring.error_monitoring` (`capture_exceptions`, `ignore_exceptions`, `ignore_error_codes` seperti `[404, 422]`, `max_errors_per_request`, `include_stack_trace`).

---

## 🔧 Pola Integrasi

### 1. **Middleware Stack**

Pipeline nyata dari `config/web/di/application.php` (yang paling atas dijalankan pertama):

```php
'withMiddlewares()' => [
    [
        FormatDataResponseAsJson::class,
        static fn () => new ContentNegotiator([
            'application/json' => new JsonDataResponseFormatter(),
        ]),
        ErrorCatcher::class,                                          // catches uncaught throwables
        static fn (ExceptionResponderFactory $factory) => $factory->create(), // typed exception mapping
        static fn () => new TrustedHostMiddleware(
            $params['app/trusted_hosts']['allowedHosts'] ?? [],
        ),
        CorsMiddleware::class,
        JwtMiddleware::class,
        RequestIdMiddleware::class,
        StructuredLoggingMiddleware::class,
        MetricsMiddleware::class,
        RateLimitMiddleware::class,
        SecureHeadersMiddleware::class,
        ErrorMonitoringMiddleware::class,
        RequestBodyParser::class,
        AccessMiddleware::class,
        Router::class,
        NotFoundMiddleware::class,                                    // 404 for unmatched routes
    ],
],
```

### 2. **Melempar Exception di Kode Aplikasi**

Biarkan middleware yang memformat error — cukup lempar typed exception:

```php
use App\Shared\Core\Exception\NotFoundException;
use App\Shared\Core\Exception\ValidationException;
use App\Shared\Core\ValueObject\Message;

throw new NotFoundException(
    translate: Message::create(
        key: 'resource.not_found',
        params: ['resource' => 'User', 'field' => 'id', 'value' => $id],
    ),
);

throw new ValidationException(
    errors: ['email' => ['Invalid email format']],
);
```

### 3. **Mengembalikan Error dari Action**

Gunakan `ResponseFactory` ketika Anda menginginkan response error terkontrol tanpa melempar exception:

```php
final class ExampleAction
{
    public function __construct(private ResponseFactory $responseFactory) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        if ($badInput) {
            return $this->responseFactory->fail(
                translate: Message::create(key: 'request.invalid_parameter', params: ['param' => 'email']),
            );
        }

        return $this->responseFactory->success($result);
    }
}
```

---

## 🚀 Praktik Terbaik

### 1. **Throw, Jangan Format**
```php
// ✅ Throw typed exceptions; ExceptionResponder formats them
throw new ForbiddenException(translate: Message::create(key: 'access.insufficient_permissions'));

// ❌ Hand-build JSON error payloads inside actions
return $response->withStatus(403)->withBody(...);
```

### 2. **Informasi Debug**
```php
// ✅ Details are gated by APP_ENV=dev + APP_DEBUG=1 automatically
// ❌ Don't add your own "debug" flag or leak traces in production
```

### 3. **Lokalisasi**
```php
// ✅ Use Message value objects so clients get localized messages
translate: Message::create(key: 'resource.not_found', params: [...])

// ❌ Hardcode English strings when a message key exists
```

---

## 📊 Pertimbangan Performa

### 1. **Pembuatan Response**
- JSON di-encode sekali dengan `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`
- `trace` dibatasi hingga 10 frame di `ErrorHandlerResponse` dan dihilangkan sepenuhnya di luar mode dev

### 2. **Penggunaan Memori**
- Tidak ada stack trace di response produksi
- `ErrorMonitoringMiddleware` membatasi error yang ditangkap via `max_errors_per_request`

### 3. **Overhead Logging**
- `StructuredLoggingMiddleware` dan `ErrorMonitoringMiddleware` dapat dikonfigurasi via params `app/monitoring` (path yang dikecualikan, status code yang diabaikan)

---

## 🎯 Ringkasan

Error handling di boilerplate ini terpusat: middleware `ErrorCatcher` + `ExceptionResponder` mengonversi exception ke envelope JSON standar `{code, success, message, errors}`, `ErrorHandlerResponse` me-render output fallback, dan `ResponseFactory`/presenter memberi action cara yang konsisten untuk mengembalikan success dan failure. Manfaat utamanya meliputi:

- **🔄 Consistency**: Format response error terstandar
- **🛡️ Security**: Detail debug hanya di mode dev, tidak ada kebocoran dari business exception
- **🔍 Debuggability**: Data `trace` saat `APP_ENV=dev` dan `APP_DEBUG=1`
- **📝 Logging**: Middleware monitoring dengan aturan penangkapan yang dapat dikonfigurasi
- **⚡ Performance**: Pembuatan response error yang efisien
- **🌐 Localization**: Pesan error yang dapat diterjemahkan via `Message` + `TranslatorInterface`

Dengan mengikuti pola dan praktik terbaik yang diuraikan dalam panduan ini, Anda dapat membangun error handling yang robust dan mudah dipelihara untuk aplikasi Yii3 API Anda! 🚀
