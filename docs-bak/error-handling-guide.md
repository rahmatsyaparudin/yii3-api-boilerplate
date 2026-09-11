# Error Handling Guide

## 📋 Overview

Error handling utilities provide centralized error processing, response formatting, and exception management for the Yii3 API application. These components ensure consistent error responses and proper error logging.

---

## 🏗️ Error Handling Architecture

### Directory Structure

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

### Design Principles

#### **1. Consistency**
- Standardized error response format: `{code, success: false, message, errors}`
- Consistent HTTP status codes driven by `HttpException::getHttpStatusCode()`
- Uniform error message structure via localized `Message` value objects

#### **2. Centralization**
- `Yiisoft\ErrorHandler\Middleware\ErrorCatcher` catches uncaught throwables
- `Yiisoft\ErrorHandler\Middleware\ExceptionResponder` (built by `ExceptionResponderFactory`) maps exceptions to responses
- `ErrorHandlerResponse` renders the final JSON error body

#### **3. Security**
- Stack traces (`trace` key) only when `APP_ENV=dev` **and** `APP_DEBUG=1`
- Business exceptions from `App\Shared\Core\Exception` never leak internals
- Sensitive detail stays out of responses; monitoring middleware controls what's logged

#### **4. Debuggability**
- `renderVerbose()` / dev mode adds `trace` with type, message, code, file, line, and up to 10 stack frames
- `ErrorMonitoringMiddleware` can capture exceptions per `app/monitoring.error_monitoring` params

---

## 📁 Error Handling Components

### 1. ErrorHandlerResponse

**Location**: `src/Shared/Core/ErrorHandler/ErrorHandlerResponse.php`

**Purpose**: Renders any `Throwable` into a standardized JSON error body. It implements `Yiisoft\ErrorHandler\ThrowableRendererInterface` and is used by the framework's `ErrorCatcher` middleware.

```php
final readonly class ErrorHandlerResponse implements ThrowableRendererInterface
{
    public function render(\Throwable $t, ?ServerRequestInterface $request = null): ErrorData;
    public function renderVerbose(\Throwable $t, ?ServerRequestInterface $request = null): ErrorData;
}
```

**Response format** (`formatErrorResponse()`):
```json
{
    "code": 500,
    "success": false,
    "message": "Something went wrong",
    "errors": []
}
```

- `errors` is populated only for `ValidationException` (from `$e->getErrors()`)
- `renderVerbose()` adds a `trace` object: `{type, message, code, file, line, trace: [...10 frames]}`

**Usage Example**:
```php
$renderer = new ErrorHandlerResponse();
$errorData = $renderer->render($exception, $request);
// $errorData->getBody()    → JSON string
// $errorData->getHeaders() → ['Content-Type' => 'application/json']
```

### 2. ExceptionResponderFactory

**Location**: `src/Api/Shared/ExceptionResponderFactory.php`

**Purpose**: Builds the `Yiisoft\ErrorHandler\Middleware\ExceptionResponder` middleware with per-exception-type handlers. Registered in the middleware stack in `config/web/di/application.php`.

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

**Handler mapping**:

| Exception | Result |
|---|---|
| `Yiisoft\Input\Http\InputValidationException` | `ResponseFactory::failValidation($result)` → HTTP 422 with field errors |
| `NoChangesException` | `ResponseFactory::success(data, translate)` → HTTP 200 "no changes" response |
| `HttpException` (and subclasses) | JSON error with `$e->getCode()` status and translated `getTranslateMessage()` |
| `Yiisoft\ErrorHandler\Exception\UserException` | HTTP 400 with the exception message |
| Any other `\Throwable` | HTTP 500 JSON error |

**Throwable response format**:
```json
{
    "code": 404,
    "success": false,
    "message": "The requested resource was not found",
    "errors": []
}
```

- For `HttpException`, `message` is translated via `TranslatorInterface::translate($message->getKey(), $message->getParams(), $message->getDomain() ?? 'error')`.
- For `ValidationException`, `errors` contains the field-level errors.
- A `trace` key is appended only when `APP_ENV=dev` **and** `APP_DEBUG=1`, and the exception is not a business exception from `App\Shared\Core\Exception` (except `ValidationException`, which may show details in dev).

### 3. ResponseFactory

**Location**: `src/Api/Shared/ResponseFactory.php`

**Purpose**: Produces consistent API responses using the presenter classes. Used by `ExceptionResponderFactory` and by actions directly.

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

- `success()` — `SuccessPresenter`, or `SuccessWithMetaPresenter` when `$meta` is given; `Message` is translated with domain default `'success'`
- `fail()` — `FailPresenter` with the given HTTP code; `Message` domain defaults to `'error'`
- `notFound()` — `fail()` with `Message::create(key: 'http.not_found')` and HTTP 404
- `failValidation()` — `fail()` with `ValidationResultPresenter`, `Message::create(key: 'validation.failed')`, HTTP 422

### 4. NotFoundMiddleware

**Location**: `src/Api/Shared/NotFoundMiddleware.php`

Terminal middleware placed **after** `Router` in the stack — returns `ResponseFactory::notFound()` for any request that reaches it (i.e., no route matched).

### 5. ErrorMonitoringMiddleware

**Location**: `src/Infrastructure/Core/Monitoring/ErrorMonitoringMiddleware.php`

Captures exceptions and PHP errors for monitoring per the `app/monitoring.error_monitoring` params (`capture_exceptions`, `ignore_exceptions`, `ignore_error_codes` like `[404, 422]`, `max_errors_per_request`, `include_stack_trace`).

---

## 🔧 Integration Patterns

### 1. **Middleware Stack**

The real pipeline from `config/web/di/application.php` (top runs first):

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

### 2. **Throwing Exceptions in Application Code**

Let the middleware format errors — just throw the typed exception:

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

### 3. **Returning Errors from Actions**

Use `ResponseFactory` when you want a controlled error response without throwing:

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

## 🚀 Best Practices

### 1. **Throw, Don't Format**
```php
// ✅ Throw typed exceptions; ExceptionResponder formats them
throw new ForbiddenException(translate: Message::create(key: 'access.insufficient_permissions'));

// ❌ Hand-build JSON error payloads inside actions
return $response->withStatus(403)->withBody(...);
```

### 2. **Debug Information**
```php
// ✅ Details are gated by APP_ENV=dev + APP_DEBUG=1 automatically
// ❌ Don't add your own "debug" flag or leak traces in production
```

### 3. **Localization**
```php
// ✅ Use Message value objects so clients get localized messages
translate: Message::create(key: 'resource.not_found', params: [...])

// ❌ Hardcode English strings when a message key exists
```

---

## 📊 Performance Considerations

### 1. **Response Generation**
- JSON is encoded once with `JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE`
- `trace` is limited to 10 frames in `ErrorHandlerResponse` and excluded entirely outside dev mode

### 2. **Memory Usage**
- No stack traces in production responses
- `ErrorMonitoringMiddleware` caps captured errors via `max_errors_per_request`

### 3. **Logging Overhead**
- `StructuredLoggingMiddleware` and `ErrorMonitoringMiddleware` are configurable via `app/monitoring` params (excluded paths, ignored status codes)

---

## 🎯 Summary

Error handling in this boilerplate is centralized: `ErrorCatcher` + `ExceptionResponder` middleware convert exceptions into the standard `{code, success, message, errors}` JSON envelope, `ErrorHandlerResponse` renders fallback output, and `ResponseFactory`/presenters give actions a consistent way to return success and failure. Key benefits include:

- **🔄 Consistency**: Standardized error response format
- **🛡️ Security**: Debug detail only in dev mode, no leakage from business exceptions
- **🔍 Debuggability**: `trace` data when `APP_ENV=dev` and `APP_DEBUG=1`
- **📝 Logging**: Monitoring middleware with configurable capture rules
- **⚡ Performance**: Efficient error response generation
- **🌐 Localization**: Translatable error messages via `Message` + `TranslatorInterface`

By following the patterns and best practices outlined in this guide, you can build robust, maintainable error handling for your Yii3 API application! 🚀
