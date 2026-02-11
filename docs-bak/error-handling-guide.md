# Error Handling Guide

## 📋 Overview

Error handling utilities provide centralized error processing, response formatting, and exception management for the Yii3 API application. These components ensure consistent error responses and proper error logging.

---

## 🏗️ Error Handling Architecture

### Directory Structure

```
src/Shared/ErrorHandler/
└── ErrorHandlerResponse.php    # Error response formatting
```

### Design Principles

#### **1. **Consistency**
- Standardized error response format
- Consistent HTTP status codes
- Uniform error message structure

#### **2. **Centralization**
- Single point for error processing
- Centralized error logging
- Unified error response generation

#### **3. **Security**
- Safe error information exposure
- Prevent information leakage
- Proper error sanitization

#### **4. **Debuggability**
- Detailed error information in development
- Stack traces for debugging
- Contextual error data

---

## 📁 Error Handling Components

### ErrorHandlerResponse

**Purpose**: Error response formatting and generation

```php
<?php

declare(strict_types=1);

namespace App\Shared\ErrorHandler;

use App\Shared\Exception\HttpException;
use App\Shared\ValueObject\Message;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Error Handler Response
 */
final class ErrorHandlerResponse
{
    public function __construct(
        private TranslatorInterface $translator,
        private bool $debug = false
    ) {}

    /**
     * Create error response from exception
     */
    public function createFromException(
        \Throwable $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = $this->extractErrorData($exception, $request);
        
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus($errorData['status'])
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create error response from HttpException
     */
    public function createFromHttpException(
        HttpException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = $this->extractHttpErrorData($exception, $request);
        
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus($exception->getStatusCode())
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create validation error response
     */
    public function createValidationResponse(
        array $errors,
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => 'validation_error',
            'message' => 'Validation failed',
            'status' => Status::UNPROCESSABLE_ENTITY,
            'errors' => $this->formatValidationErrors($errors),
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'request_data' => $this->getRequestData($request),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::UNPROCESSABLE_ENTITY)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create not found response
     */
    public function createNotFoundResponse(
        string $resource = '',
        ServerRequestInterface $request
    ): ResponseInterface {
        $message = $resource 
            ? "Resource '{$resource}' not found"
            : 'Resource not found';

        $errorData = [
            'type' => 'not_found',
            'message' => $message,
            'status' => Status::NOT_FOUND,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'resource' => $resource,
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::NOT_FOUND)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create unauthorized response
     */
    public function createUnauthorizedResponse(
        string $message = 'Authentication required',
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => 'unauthorized',
            'message' => $message,
            'status' => Status::UNAUTHORIZED,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'auth_headers' => $this->getAuthHeaders($request),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::UNAUTHORIZED)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create forbidden response
     */
    public function createForbiddenResponse(
        string $message = 'Access denied',
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => 'forbidden',
            'message' => $message,
            'status' => Status::FORBIDDEN,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'user_info' => $this->getUserInfo($request),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::FORBIDDEN)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create rate limit response
     */
    public function createRateLimitResponse(
        int $retryAfter = 60,
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => 'rate_limit_exceeded',
            'message' => 'Too many requests',
            'status' => Status::TOO_MANY_REQUESTS,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
            'retry_after' => $retryAfter,
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'rate_limit_info' => $this->getRateLimitInfo($request),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::TOO_MANY_REQUESTS)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Retry-After', (string) $retryAfter);
    }

    /**
     * Create server error response
     */
    public function createServerErrorResponse(
        \Throwable $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => 'internal_server_error',
            'message' => 'Internal server error',
            'status' => Status::INTERNAL_SERVER_ERROR,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'exception' => $this->formatException($exception),
                'stack_trace' => $exception->getTraceAsString(),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::INTERNAL_SERVER_ERROR)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Extract error data from exception
     */
    private function extractErrorData(
        \Throwable $exception,
        ServerRequestInterface $request
    ): array {
        $errorData = [
            'type' => $this->getErrorType($exception),
            'message' => $this->getErrorMessage($exception),
            'status' => $this->getErrorStatus($exception),
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'exception' => $this->formatException($exception),
                'stack_trace' => $exception->getTraceAsString(),
                'request_data' => $this->getRequestData($request),
            ];
        }

        return $errorData;
    }

    /**
     * Extract HTTP error data
     */
    private function extractHttpErrorData(
        HttpException $exception,
        ServerRequestInterface $request
    ): array {
        $errorData = [
            'type' => $exception->getType(),
            'message' => $this->getHttpErrorMessage($exception),
            'status' => $exception->getStatusCode(),
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
                'details' => $exception->getDetails(),
            ];
        }

        return $errorData;
    }

    /**
     * Get error type
     */
    private function getErrorType(\Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $exception->getType();
        }

        return (new \ReflectionClass($exception))->getShortName();
    }

    /**
     * Get error message
     */
    private function getErrorMessage(\Throwable $exception): string
    {
        if ($exception instanceof HttpException) {
            return $this->getHttpErrorMessage($exception);
        }

        // In production, don't expose detailed error messages
        if (!$this->debug) {
            return 'Internal server error';
        }

        return $exception->getMessage();
    }

    /**
     * Get HTTP error message
     */
    private function getHttpErrorMessage(HttpException $exception): string
    {
        $translateMessage = $exception->getTranslateMessage();
        
        if ($translateMessage) {
            return $this->translator->translate(
                $translateMessage->getKey(),
                $translateMessage->getParams(),
                $translateMessage->getDomain() ?? 'error'
            );
        }

        return $exception->getMessage();
    }

    /**
     * Get error status
     */
    private function getErrorStatus(\Throwable $exception): int
    {
        if ($exception instanceof HttpException) {
            return $exception->getStatusCode();
        }

        return Status::INTERNAL_SERVER_ERROR;
    }

    /**
     * Format validation errors
     */
    private function formatValidationErrors(array $errors): array
    {
        $formatted = [];
        
        foreach ($errors as $field => $message) {
            $formatted[$field] = [
                'message' => $message,
                'code' => 'validation_error',
            ];
        }
        
        return $formatted;
    }

    /**
     * Format exception for debug
     */
    private function formatException(\Throwable $exception): array
    {
        return [
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $this->formatStackTrace($exception->getTrace()),
        ];
    }

    /**
     * Format stack trace
     */
    private function formatStackTrace(array $trace): array
    {
        return array_map(function ($frame) {
            return [
                'function' => $frame['function'] ?? 'unknown',
                'class' => $frame['class'] ?? null,
                'file' => $frame['file'] ?? null,
                'line' => $frame['line'] ?? null,
                'type' => $frame['type'] ?? null,
            ];
        }, array_slice($trace, 0, 10)); // Limit to 10 frames
    }

    /**
     * Get request ID
     */
    private function getRequestId(ServerRequestInterface $request): string
    {
        return $request->getAttribute('request_id') ?? uniqid('req_');
    }

    /**
     * Get request data
     */
    private function getRequestData(ServerRequestInterface $request): array
    {
        return [
            'method' => $request->getMethod(),
            'uri' => (string) $request->getUri(),
            'headers' => $this->sanitizeHeaders($request->getHeaders()),
            'query_params' => $request->getQueryParams(),
            'body_params' => $this->sanitizeBodyParams($request->getParsedBody()),
        ];
    }

    /**
     * Get auth headers
     */
    private function getAuthHeaders(ServerRequestInterface $request): array
    {
        $headers = $request->getHeaders();
        
        return [
            'authorization' => $headers['authorization'][0] ?? null,
            'x-api-key' => $headers['x-api-key'][0] ?? null,
            'cookie' => $headers['cookie'][0] ?? null,
        ];
    }

    /**
     * Get user info
     */
    private function getUserInfo(ServerRequestInterface $request): ?array
    {
        $user = $request->getAttribute('user');
        
        if ($user === null) {
            return null;
        }
        
        return [
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'email' => method_exists($user, 'getEmail') ? $user->getEmail() : null,
            'roles' => method_exists($user, 'getRoles') ? $user->getRoles() : [],
        ];
    }

    /**
     * Get rate limit info
     */
    private function getRateLimitInfo(ServerRequestInterface $request): array
    {
        return [
            'identifier' => $this->getRateLimitIdentifier($request),
            'endpoint' => $this->getRateLimitEndpoint($request),
        ];
    }

    /**
     * Get rate limit identifier
     */
    private function getRateLimitIdentifier(ServerRequestInterface $request): string
    {
        $user = $request->getAttribute('user');
        
        if ($user && method_exists($user, 'getId')) {
            return 'user:' . $user->getId();
        }
        
        return 'ip:' . ($request->getServerParams()['REMOTE_ADDR'] ?? 'unknown');
    }

    /**
     * Get rate limit endpoint
     */
    private function getRateLimitEndpoint(ServerRequestInterface $request): string
    {
        $path = $request->getUri()->getPath();
        
        if (str_starts_with($path, '/api/auth')) {
            return 'auth';
        }
        
        if (str_starts_with($path, '/api/upload')) {
            return 'upload';
        }
        
        return 'default';
    }

    /**
     * Sanitize headers for debug output
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sanitized = [];
        
        foreach ($headers as $name => $values) {
            // Remove sensitive headers
            if (in_array(strtolower($name), ['authorization', 'cookie', 'x-api-key'], true)) {
                $sanitized[$name] = ['***'];
            } else {
                $sanitized[$name] = $values;
            }
        }
        
        return $sanitized;
    }

    /**
     * Sanitize body params for debug output
     */
    private function sanitizeBodyParams(mixed $params): mixed
    {
        if (!is_array($params)) {
            return $params;
        }
        
        $sanitized = [];
        
        foreach ($params as $key => $value) {
            // Remove sensitive fields
            if (in_array(strtolower($key), ['password', 'token', 'secret', 'key'], true)) {
                $sanitized[$key] = '***';
            } else {
                $sanitized[$key] = $value;
            }
        }
        
        return $sanitized;
    }

    /**
     * Create error response with custom data
     */
    public function createCustomErrorResponse(
        string $type,
        string $message,
        int $status,
        array $customData = [],
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => $type,
            'message' => $message,
            'status' => $status,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if (!empty($customData)) {
            $errorData['data'] = $customData;
        }

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');
    }

    /**
     * Create maintenance response
     */
    public function createMaintenanceResponse(
        string $message = 'System under maintenance',
        ServerRequestInterface $request
    ): ResponseInterface {
        $errorData = [
            'type' => 'maintenance',
            'message' => $message,
            'status' => Status::SERVICE_UNAVAILABLE,
            'timestamp' => date('c'),
            'path' => $request->getUri()->getPath(),
        ];

        if ($this->debug) {
            $errorData['debug'] = [
                'request_id' => $this->getRequestId($request),
            ];
        }

        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => $errorData
        ]));
        
        return $response
            ->withStatus(Status::SERVICE_UNAVAILABLE)
            ->withHeader('Content-Type', 'application/json');
    }
}
```

---

## 🔧 Integration Patterns

### 1. **Error Handler Middleware**
```php
final class ErrorHandlerMiddleware
{
    public function __construct(
        private ErrorHandlerResponse $errorHandler,
        private LoggerInterface $logger
    ) {}

    public function process(
        ServerRequestInterface $request,
        RequestHandlerInterface $handler
    ): ResponseInterface {
        try {
            return $handler->handle($request);
        } catch (HttpException $e) {
            $this->logHttpException($e, $request);
            return $this->errorHandler->createFromHttpException($e, $request);
        } catch (\Throwable $e) {
            $this->logException($e, $request);
            return $this->errorHandler->createServerErrorResponse($e, $request);
        }
    }

    private function logHttpException(HttpException $e, ServerRequestInterface $request): void
    {
        $this->logger->warning('HTTP Exception', [
            'exception' => $e,
            'request_id' => $request->getAttribute('request_id'),
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'user_id' => $this->getUserId($request),
        ]);
    }

    private function logException(\Throwable $e, ServerRequestInterface $request): void
    {
        $this->logger->error('Unhandled Exception', [
            'exception' => $e,
            'request_id' => $request->getAttribute('request_id'),
            'path' => $request->getUri()->getPath(),
            'method' => $request->getMethod(),
            'user_id' => $this->getUserId($request),
        ]);
    }

    private function getUserId(ServerRequestInterface $request): ?int
    {
        $user = $request->getAttribute('user');
        return $user && method_exists($user, 'getId') ? $user->getId() : null;
    }
}
```

### 2. **Exception Responder Factory**
```php
final class ExceptionResponderFactory
{
    public function __construct(
        private ErrorHandlerResponse $errorHandler
    ) {}

    public function createNotFoundResponse(
        NotFoundException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->errorHandler->createNotFoundResponse(
            $exception->getTranslateMessage()?->getParams()['resource'] ?? '',
            $request
        );
    }

    public function createValidationResponse(
        ValidationException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->errorHandler->createValidationResponse(
            $exception->getErrors(),
            $request
        );
    }

    public function createUnauthorizedResponse(
        UnauthorizedException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->errorHandler->createUnauthorizedResponse(
            $exception->getMessage(),
            $request
        );
    }

    public function createForbiddenResponse(
        ForbiddenException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->errorHandler->createForbiddenResponse(
            $exception->getMessage(),
            $request
        );
    }

    public function createRateLimitResponse(
        TooManyRequestsException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        $retryAfter = $exception->getRetryAfter() ?? 60;
        return $this->errorHandler->createRateLimitResponse($retryAfter, $request);
    }

    public function createErrorResponse(
        HttpException $exception,
        ServerRequestInterface $request
    ): ResponseInterface {
        return $this->errorHandler->createFromHttpException($exception, $request);
    }
}
```

### 3. **Controller Error Handling**
```php
final class ExampleController
{
    public function __construct(
        private ExampleApplicationService $service,
        private ExceptionResponderFactory $responderFactory
    ) {}

    public function actionCreate(): ResponseInterface
    {
        try {
            $data = $this->request->getParsedBody();
            $command = new CreateExampleCommand(
                name: $data['name'],
                email: $data['email']
            );
            
            $result = $this->service->create($command);
            
            return $this->responseFactory->success($result);
            
        } catch (ValidationException $e) {
            return $this->responderFactory->createValidationResponse($e, $this->request);
        } catch (NotFoundException $e) {
            return $this->responderFactory->createNotFoundResponse($e, $this->request);
        } catch (ConflictException $e) {
            return $this->responderFactory->createErrorResponse($e, $this->request);
        } catch (HttpException $e) {
            return $this->responderFactory->createErrorResponse($e, $this->request);
        }
    }
}
```

---

## 🚀 Best Practices

### 1. **Error Response Format**
```php
// ✅ Use standardized error format
$errorData = [
    'type' => 'validation_error',
    'message' => 'Validation failed',
    'status' => 422,
    'timestamp' => date('c'),
    'path' => $request->getUri()->getPath(),
];

// ❌ Inconsistent format
$errorData = [
    'error' => 'Validation failed',
    'code' => 422,
];
```

### 2. **Debug Information**
```php
// ✅ Include debug info only in debug mode
if ($this->debug) {
    $errorData['debug'] = $debugInfo;
}

// ❌ Always include debug info
$errorData['debug'] = $debugInfo;
```

### 3. **Security**
```php
// ✅ Sanitize sensitive data
$sanitizedHeaders = $this->sanitizeHeaders($headers);

// ❌ Expose sensitive data
$errorData['headers'] = $headers;
```

---

## 📊 Performance Considerations

### 1. **Response Generation**
- Use efficient JSON encoding
- Limit debug information size
- Cache error responses when possible

### 2. **Memory Usage**
- Avoid large stack traces in production
- Limit debug data size
- Use efficient data structures

### 3. **Logging Overhead**
- Log only essential information
- Use appropriate log levels
- Avoid expensive operations in error handling

---

## 🎯 Summary

Error handling utilities provide centralized, consistent error processing for the Yii3 API application. Key benefits include:

- **🔄 Consistency**: Standardized error response format
- **🛡️ Security**: Safe error information exposure
- **🔍 Debuggability**: Detailed error information in development
- **📝 Logging**: Centralized error logging
- **⚡ Performance**: Efficient error response generation
- **🌐 Localization**: Translatable error messages

By following the patterns and best practices outlined in this guide, you can build robust, maintainable error handling for your Yii3 API application! 🚀
