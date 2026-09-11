# Exceptions Guide

## 📋 Overview

The exception hierarchy in this Yii3 API application provides a structured way to handle different types of errors and exceptions. Each exception class is designed to handle specific error scenarios with proper HTTP status codes and localized error messages carried by a `Message` value object.

---

## 🏗️ Exception Architecture

### Directory Structure

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

### Design Principles

#### **1. HTTP Status Mapping**
- Each exception maps to an HTTP status code via `getHttpStatusCode()` (also exposed as `getCode()`)
- Consistent error response format produced by `ExceptionResponderFactory` / `ErrorHandlerResponse`
- RESTful API compliance

#### **2. Structured Error Information**
- Localized `Message` value object (`key`, `params`, `domain`)
- Optional `errors` array for field-level validation details
- Optional `data` payload for additional context

#### **3. Localization Support**
- Every exception accepts a `Message` or a plain string (converted to `Message::create($string)`)
- `domain` defaults to the `error` translation category when rendered
- Default message keys exist under `resources/messages/{locale}/error.php` and `validation.php`

#### **4. Type Safety**
- Strong typing with PHP 8+ features
- `final` concrete classes extending the abstract `HttpException`
- Named-argument-friendly constructors (`translate:`, `errors:`, `data:`, `previous:`)

---

## 📁 Exception Components

### 1. HttpException (Base Class)

**Location**: `src/Shared/Core/Exception/HttpException.php`

**Purpose**: Abstract base for all HTTP exceptions; stores status code, `Message`, errors, and data.

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

**The `Message` value object** (`src/Shared/Core/ValueObject/Message.php`):

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

### 2. Concrete Exceptions

All concrete exceptions are `final` and accept `Message|string|null` for `translate` — a string is wrapped via `Message::create($string)`. When `translate` is `null`, a default message key is used.

| Exception | HTTP | Signature | Default key |
|---|---|---|---|
| `BadRequestException` | 400 | `(Message\|string\|null $translate = null, ?array $errors = null, ?Throwable $previous = null)` | `http.bad_request` |
| `UnauthorizedException` | 401 | same as above | `http.unauthorized` |
| `ForbiddenException` | 403 | same as above | `http.forbidden` |
| `NotFoundException` | 404 | same as above | `http.not_found` |
| `ConflictException` | 409 | same as above | `resource.conflict` |
| `TooManyRequestsException` | 429 | same as above | `http.too_many_requests` |
| `ValidationException` | 422 | `(?array $errors = null, Message\|string\|null $translate = null, ?Throwable $previous = null)` — **errors first** | `validation.failed` |
| `OptimisticLockException` | 409 | `(Message\|string\|null $translate = null, ?array $data = null, ?Throwable $previous = null)` | `optimistic.lock.failed` |
| `NoChangesException` | 200 | `(Message\|string\|null $translate = null, ?array $data = null, ?Throwable $previous = null)` | `resource.conflict` |
| `ServiceException` | configurable (default `Status::OK`) | `(Message\|string\|null $translate = null, ?array $data = null, ?int $code = Status::OK, ?Throwable $previous = null)` | `service.error` |

**Notes**:
- `NoChangesException` is special-cased by `ExceptionResponderFactory` — it is rendered as a **success** response (HTTP 200) carrying `getData()` and the translated message, not as an error.
- `ServiceException` carries its `$data` via `getData()` (passed as the `errors` slot internally) and lets you pick any HTTP status via `code:`.
- There is **no** `BusinessRuleException` — use `ConflictException`, `ValidationException`, or `ServiceException` with an appropriate `code` for business-rule failures.

### Usage Examples

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

## 🔧 Exception Handling Patterns

### 1. **Let the Middleware Handle It**

The `ExceptionResponder` middleware (built by `App\Api\Shared\ExceptionResponderFactory`, registered in `config/web/di/application.php`) converts exceptions to JSON responses — no try/catch needed in actions:

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

## 🚀 Best Practices

### 1. **Exception Creation**
```php
// ✅ Use specific exception types with Message keys
throw new NotFoundException(
    translate: Message::create(key: 'resource.not_found', params: ['resource' => 'User', 'field' => 'id', 'value' => $id]),
);

// ❌ Avoid generic exceptions
throw new RuntimeException('User not found');
```

### 2. **Error Messages**
```php
// ✅ Provide context via params
throw new ValidationException(
    errors: ['email' => ['Invalid email format']],
);

// ❌ Avoid empty context when details exist
throw new ValidationException();
```

### 3. **HTTP Status Codes**
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

## 📊 Performance Considerations

### 1. **Exception Overhead**
- Exceptions are expensive — use for exceptional cases only
- Avoid using exceptions for flow control
- Use validation before business logic

### 2. **Memory Usage**
- Keep `params`, `errors`, and `data` payloads concise
- Avoid storing large objects in exceptions
- Use exception chaining wisely

### 3. **Logging**
- Log exceptions with proper context (`getDefaultMessageKey()`, `getTranslateParams()`, `getData()`)
- `ErrorMonitoringMiddleware` captures exceptions per `app/monitoring.error_monitoring` config
- Use appropriate log levels

---

## 🎯 Summary

The exception hierarchy provides a structured way to handle different types of errors in the Yii3 API application. Key benefits include:

- **🔍 Type Safety**: `final` concrete exceptions extending `HttpException`
- **📡 HTTP Compliance**: Proper HTTP status code mapping via `getHttpStatusCode()`
- **🌐 Localization**: `Message` value objects with `key`/`params`/`domain`
- **🔧 Debugging**: `errors` and `data` payloads, dev-mode traces in error responses
- **📦 Consistency**: Uniform `{code, success, message, errors}` error format
- **🚀 Performance**: Optimized exception handling

By following the patterns and best practices outlined in this guide, you can build robust, maintainable exception handling for your Yii3 API application! 🚀
