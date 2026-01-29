# Exceptions Guide

## 📋 Overview

The exception hierarchy in this Yii3 API application provides a structured way to handle different types of errors and exceptions. Each exception class is designed to handle specific error scenarios with proper HTTP status codes and error messages.

---

## 🏗️ Exception Architecture

### Directory Structure

```
src/Shared/Exception/
├── BadRequestException.php      # 400 Bad Request
├── BusinessRuleException.php    # 422 Unprocessable Entity
├── ConflictException.php        # 409 Conflict
├── ForbiddenException.php        # 403 Forbidden
├── HttpException.php            # Base HTTP exception
├── NoChangesException.php       # 200 No Content
├── NotFoundException.php         # 404 Not Found
├── OptimisticLockException.php  # 409 Conflict
├── ServiceException.php         # 500 Internal Server Error
├── TooManyRequestsException.php # 429 Too Many Requests
├── UnauthorizedException.php    # 401 Unauthorized
├── ValidationException.php      # 422 Unprocessable Entity
└── README.md                    # Exception documentation
```

### Design Principles

#### **1. **HTTP Status Mapping**
- Each exception maps to appropriate HTTP status codes
- Consistent error response format
- RESTful API compliance

#### **2. **Structured Error Information**
- Detailed error messages
- Contextual information
- Developer-friendly debugging

#### **3. **Localization Support**
- Translatable error messages
- Multi-language support
- User-friendly messages

#### **4. **Type Safety**
- Strong typing with PHP 8+ features
- Proper inheritance hierarchy
- Interface compliance

---

## 📁 Exception Components

### 1. HttpException (Base Class)

**Purpose**: Base class for all HTTP exceptions with common functionality

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use App\Shared\ValueObject\Message;
use Throwable;

/**
 * Base HTTP exception class
 */
abstract class HttpException extends \RuntimeException
{
    public function __construct(
        protected ?Message $translate = null,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }

    /**
     * Get HTTP status code
     */
    abstract public function getStatusCode(): int;

    /**
     * Get error type
     */
    public function getType(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Get translation message
     */
    public function getTranslateMessage(): ?Message
    {
        return $this->translate;
    }

    /**
     * Get error details for API response
     */
    public function getDetails(): array
    {
        return [
            'type' => $this->getType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status' => $this->getStatusCode(),
        ];
    }

    /**
     * Create from message
     */
    public static function fromMessage(Message $message, ?Throwable $previous = null): static
    {
        return new static(
            translate: $message,
            message: $message->getKey(),
            previous: $previous
        );
    }

    /**
     * Create with context
     */
    public static function withContext(string $message, array $context = [], ?Throwable $previous = null): static
    {
        $contextMessage = $message;
        
        if (!empty($context)) {
            $contextMessage .= ' (Context: ' . json_encode($context) . ')';
        }

        return new static(
            message: $contextMessage,
            previous: $previous
        );
    }
}
```

---

### 2. BadRequestException

**Purpose**: 400 Bad Request - Invalid request syntax or parameters

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Bad Request Exception (400)
 */
final class BadRequestException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::BAD_REQUEST;
    }

    /**
     * Create for invalid parameters
     */
    public static function invalidParameter(string $parameter, string $reason = ''): self
    {
        $message = "Invalid parameter: {$parameter}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for missing parameters
     */
    public static function missingParameter(string $parameter): self
    {
        return new self(
            message: "Missing required parameter: {$parameter}"
        );
    }

    /**
     * Create for invalid JSON
     */
    public static function invalidJson(string $reason = ''): self
    {
        $message = 'Invalid JSON in request body';
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for invalid request format
     */
    public static function invalidFormat(string $expectedFormat, string $actualFormat = ''): self
    {
        $message = "Invalid request format. Expected: {$expectedFormat}";
        if ($actualFormat) {
            $message .= ", Actual: {$actualFormat}";
        }

        return new self(message: $message);
    }
}
```

**Usage Example**:
```php
// Invalid parameter
throw BadRequestException::invalidParameter('email', 'Invalid email format');

// Missing parameter
throw BadRequestException::missingParameter('user_id');

// Invalid JSON
throw BadRequestException::invalidJson('Malformed JSON structure');

// Invalid format
throw BadRequestException::invalidFormat('application/json', 'text/plain');
```

---

### 3. UnauthorizedException

**Purpose**: 401 Unauthorized - Authentication required or failed

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Unauthorized Exception (401)
 */
final class UnauthorizedException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::UNAUTHORIZED;
    }

    /**
     * Create for missing authentication
     */
    public static function missingAuthentication(): self
    {
        return new self(
            message: 'Authentication required'
        );
    }

    /**
     * Create for invalid credentials
     */
    public static function invalidCredentials(): self
    {
        return new self(
            message: 'Invalid authentication credentials'
        );
    }

    /**
     * Create for expired token
     */
    public static function expiredToken(): self
    {
        return new self(
            message: 'Authentication token has expired'
        );
    }

    /**
     * Create for invalid token
     */
    public static function invalidToken(): self
    {
        return new self(
            message: 'Invalid authentication token'
        );
    }

    /**
     * Create for insufficient permissions
     */
    public static function insufficientPermissions(): self
    {
        return new self(
            message: 'Insufficient permissions for this operation'
        );
    }
}
```

**Usage Example**:
```php
// Missing authentication
throw UnauthorizedException::missingAuthentication();

// Invalid credentials
throw UnauthorizedException::invalidCredentials();

// Expired token
throw UnauthorizedException::expiredToken();
```

---

### 4. ForbiddenException

**Purpose**: 403 Forbidden - Access denied to resource

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Forbidden Exception (403)
 */
final class ForbiddenException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::FORBIDDEN;
    }

    /**
     * Create for access denied
     */
    public static function accessDenied(string $resource = ''): self
    {
        $message = 'Access denied';
        if ($resource) {
            $message .= " to resource: {$resource}";
        }

        return new self(message: $message);
    }

    /**
     * Create for insufficient permissions
     */
    public static function insufficientPermissions(string $action = ''): self
    {
        $message = 'Insufficient permissions';
        if ($action) {
            $message .= " to perform: {$action}";
        }

        return new self(message: $message);
    }

    /**
     * Create for account suspended
     */
    public static function accountSuspended(): self
    {
        return new self(
            message: 'Account is suspended'
        );
    }

    /**
     * Create for resource not accessible
     */
    public static function resourceNotAccessible(string $resource): self
    {
        return new self(
            message: "Resource '{$resource}' is not accessible"
        );
    }
}
```

**Usage Example**:
```php
// Access denied
throw ForbiddenException::accessDenied('user profile');

// Insufficient permissions
throw ForbiddenException::insufficientPermissions('delete users');

// Account suspended
throw ForbiddenException::accountSuspended();
```

---

### 5. NotFoundException

**Purpose**: 404 Not Found - Resource not found

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use App\Shared\ValueObject\Message;
use Yiisoft\Http\Status;

/**
 * Not Found Exception (404)
 */
final class NotFoundException extends HttpException
{
    public function __construct(
        ?Message $translate = null,
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null
    ) {
        if (empty($message) && $translate === null) {
            $message = 'Resource not found';
        }
        
        parent::__construct($translate, $message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Status::NOT_FOUND;
    }

    /**
     * Create for resource not found
     */
    public static function resourceNotFound(string $resource, string $field = '', mixed $value = null): self
    {
        $message = "Resource not found";
        if ($field && $value !== null) {
            $message .= " with {$field}: {$value}";
        } elseif ($resource) {
            $message = "{$resource} not found";
        }

        return new self(
            translate: new Message(
                key: 'resource.not_found',
                params: [
                    'resource' => $resource,
                    'field' => $field,
                    'value' => $value
                ],
                domain: 'error'
            ),
            message: $message
        );
    }

    /**
     * Create for entity not found
     */
    public static function entityNotFound(string $entity, mixed $identifier): self
    {
        return new self(
            message: "{$entity} with identifier '{$identifier}' not found"
        );
    }

    /**
     * Create for route not found
     */
    public static function routeNotFound(string $route): self
    {
        return new self(
            message: "Route '{$route}' not found"
        );
    }

    /**
     * Create for endpoint not found
     */
    public static function endpointNotFound(string $endpoint): self
    {
        return new self(
            message: "Endpoint '{$endpoint}' not found"
        );
    }
}
```

**Usage Example**:
```php
// Resource not found
throw NotFoundException::resourceNotFound('User', 'id', 123);

// Entity not found
throw NotFoundException::entityNotFound('Product', 'PROD-001');

// Route not found
throw NotFoundException::routeNotFound('/api/v1/users/999');
```

---

### 6. ConflictException

**Purpose**: 409 Conflict - Resource conflict or version mismatch

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Conflict Exception (409)
 */
final class ConflictException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::CONFLICT;
    }

    /**
     * Create for resource conflict
     */
    public static function resourceConflict(string $resource, string $reason = ''): self
    {
        $message = "Resource conflict for {$resource}";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for duplicate resource
     */
    public static function duplicateResource(string $resource, string $field = '', mixed $value = null): self
    {
        $message = "Duplicate {$resource}";
        if ($field && $value !== null) {
            $message .= " with {$field}: {$value}";
        }

        return new self(message: $message);
    }

    /**
     * Create for concurrent modification
     */
    public static function concurrentModification(string $resource): self
    {
        return new self(
            message: "Concurrent modification detected for {$resource}"
        );
    }

    /**
     * Create for state conflict
     */
    public static function stateConflict(string $resource, string $currentState, string $requiredState): self
    {
        return new self(
            message: "State conflict for {$resource}. Current: {$currentState}, Required: {$requiredState}"
        );
    }
}
```

**Usage Example**:
```php
// Resource conflict
throw ConflictException::resourceConflict('User', 'Email already exists');

// Duplicate resource
throw ConflictException::duplicateResource('User', 'email', 'user@example.com');

// Concurrent modification
throw ConflictException::concurrentModification('Document');
```

---

### 7. ValidationException

**Purpose**: 422 Unprocessable Entity - Validation failed

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Validation Exception (422)
 */
final class ValidationException extends HttpException
{
    private array $errors = [];

    public function __construct(
        string $message = 'Validation failed',
        array $errors = [],
        int $code = 0,
        ?Throwable $previous = null
    ) {
        $this->errors = $errors;
        parent::__construct(null, $message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Status::UNPROCESSABLE_ENTITY;
    }

    /**
     * Get validation errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Add validation error
     */
    public function addError(string $field, string $message): self
    {
        $this->errors[$field] = $message;
        return $this;
    }

    /**
     * Add multiple validation errors
     */
    public function addErrors(array $errors): self
    {
        $this->errors = array_merge($this->errors, $errors);
        return $this;
    }

    /**
     * Check if has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get error details for API response
     */
    public function getDetails(): array
    {
        return [
            'type' => $this->getType(),
            'message' => $this->getMessage(),
            'code' => $this->getCode(),
            'status' => $this->getStatusCode(),
            'errors' => $this->errors,
        ];
    }

    /**
     * Create from validation result
     */
    public static function fromValidationResult(array $errors): self
    {
        return new self(
            message: 'Validation failed',
            errors: $errors
        );
    }

    /**
     * Create for field validation error
     */
    public static function fieldError(string $field, string $message): self
    {
        return new self(
            message: 'Validation failed',
            errors: [$field => $message]
        );
    }

    /**
     * Create for multiple field errors
     */
    public static function fieldsErrors(array $fieldErrors): self
    {
        return new self(
            message: 'Validation failed',
            errors: $fieldErrors
        );
    }
}
```

**Usage Example**:
```php
// Field validation error
throw ValidationException::fieldError('email', 'Invalid email format');

// Multiple field errors
throw ValidationException::fieldsErrors([
    'name' => 'Name is required',
    'email' => 'Invalid email format',
    'age' => 'Age must be at least 18'
]);

// From validation result
$errors = $validator->validate($data);
if (!empty($errors)) {
    throw ValidationException::fromValidationResult($errors);
}
```

---

### 8. TooManyRequestsException

**Purpose**: 429 Too Many Requests - Rate limit exceeded

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Too Many Requests Exception (429)
 */
final class TooManyRequestsException extends HttpException
{
    public function __construct(
        string $message = 'Too many requests',
        private ?int $retryAfter = null,
        int $code = 0,
        ?Throwable $previous = null
    ) {
        parent::__construct(null, $message, $code, $previous);
    }

    public function getStatusCode(): int
    {
        return Status::TOO_MANY_REQUESTS;
    }

    /**
     * Get retry after seconds
     */
    public function getRetryAfter(): ?int
    {
        return $this->retryAfter;
    }

    /**
     * Get error details for API response
     */
    public function getDetails(): array
    {
        $details = parent::getDetails();
        
        if ($this->retryAfter !== null) {
            $details['retry_after'] = $this->retryAfter;
        }

        return $details;
    }

    /**
     * Create with retry after
     */
    public static function withRetryAfter(int $retryAfter, string $message = ''): self
    {
        $defaultMessage = "Too many requests. Try again after {$retryAfter} seconds";
        $finalMessage = $message ?: $defaultMessage;

        return new self(
            message: $finalMessage,
            retryAfter: $retryAfter
        );
    }

    /**
     * Create for rate limit exceeded
     */
    public static function rateLimitExceeded(int $limit, int $window, int $retryAfter = null): self
    {
        $message = "Rate limit exceeded. Limit: {$limit} requests per {$window} seconds";
        
        return new self(
            message: $message,
            retryAfter: $retryAfter
        );
    }

    /**
     * Create for quota exceeded
     */
    public static function quotaExceeded(string $resource, int $quota, int $retryAfter = null): self
    {
        $message = "Quota exceeded for {$resource}. Limit: {$quota}";

        return new self(
            message: $message,
            retryAfter: $retryAfter
        );
    }
}
```

**Usage Example**:
```php
// Rate limit exceeded
throw TooManyRequestsException::rateLimitExceeded(100, 3600, 60);

// Quota exceeded
throw TooManyRequestsException::quotaExceeded('API calls', 1000, 300);

// With retry after
throw TooManyRequestsException::withRetryAfter(60, 'Please try again later');
```

---

### 9. ServiceException

**Purpose**: 500 Internal Server Error - Service-related errors

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Service Exception (500)
 */
final class ServiceException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::INTERNAL_SERVER_ERROR;
    }

    /**
     * Create for service unavailable
     */
    public static function serviceUnavailable(string $service): self
    {
        return new self(
            message: "Service '{$service}' is unavailable"
        );
    }

    /**
     * Create for service error
     */
    public static function serviceError(string $service, string $error): self
    {
        return new self(
            message: "Service '{$service}' error: {$error}"
        );
    }

    /**
     * Create for external service failure
     */
    public static function externalServiceFailure(string $service, string $reason = ''): self
    {
        $message = "External service '{$service}' failure";
        if ($reason) {
            $message .= ": {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for database error
     */
    public static function databaseError(string $operation, string $error): self
    {
        return new self(
            message: "Database error during {$operation}: {$error}"
        );
    }

    /**
     * Create for configuration error
     */
    public static function configurationError(string $config, string $issue): self
    {
        return new self(
            message: "Configuration error in {$config}: {$issue}"
        );
    }
}
```

**Usage Example**:
```php
// Service unavailable
throw ServiceException::serviceUnavailable('Payment Gateway');

// Service error
throw ServiceException::serviceError('Email Service', 'SMTP connection failed');

// External service failure
throw ServiceException::externalServiceFailure('Google API', 'Rate limit exceeded');
```

---

### 10. BusinessRuleException

**Purpose**: 422 Unprocessable Entity - Business rule violations

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Business Rule Exception (422)
 */
final class BusinessRuleException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::UNPROCESSABLE_ENTITY;
    }

    /**
     * Create for business rule violation
     */
    public static function violation(string $rule, string $reason = ''): self
    {
        $message = "Business rule violation: {$rule}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for constraint violation
     */
    public static function constraintViolation(string $constraint, string $reason = ''): self
    {
        $message = "Constraint violation: {$constraint}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for policy violation
     */
    public static function policyViolation(string $policy, string $reason = ''): self
    {
        $message = "Policy violation: {$policy}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(message: $message);
    }

    /**
     * Create for workflow violation
     */
    public static function workflowViolation(string $step, string $reason = ''): self
    {
        $message = "Workflow violation at step: {$step}";
        if ($reason) {
            $message .= " - {$reason}";
        }

        return new self(message: $message);
    }
}
```

**Usage Example**:
```php
// Business rule violation
throw BusinessRuleException::violation('User cannot delete own account', 'Self-deletion not allowed');

// Constraint violation
throw BusinessRuleException::constraintViolation('Unique email', 'Email already exists');

// Policy violation
throw BusinessRuleException::policyViolation('Data retention', 'Data cannot be deleted before 30 days');
```

---

### 11. OptimisticLockException

**Purpose**: 409 Conflict - Optimistic locking failure

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * Optimistic Lock Exception (409)
 */
final class OptimisticLockException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::CONFLICT;
    }

    /**
     * Create for lock version mismatch
     */
    public static function versionMismatch(string $resource, int $expected, int $actual): self
    {
        return new self(
            message: "Optimistic lock failed for {$resource}. Expected version: {$expected}, Actual version: {$actual}"
        );
    }

    /**
     * Create for concurrent modification
     */
    public static function concurrentModification(string $resource): self
    {
        return new self(
            message: "Concurrent modification detected for {$resource}. Please refresh and try again."
        );
    }

    /**
     * Create for stale data
     */
    public static function staleData(string $resource): self
    {
        return new self(
            message: "Stale data detected for {$resource}. Please refresh and try again."
        );
    }
}
```

**Usage Example**:
```php
// Version mismatch
throw OptimisticLockException::versionMismatch('User', 5, 7);

// Concurrent modification
throw OptimisticLockException::concurrentModification('Document');

// Stale data
throw OptimisticLockException::staleData('Product');
```

---

### 12. NoChangesException

**Purpose**: 200 No Content - No changes detected

```php
<?php

declare(strict_types=1);

namespace App\Shared\Exception;

use Yiisoft\Http\Status;

/**
 * No Changes Exception (200)
 */
final class NoChangesException extends HttpException
{
    public function getStatusCode(): int
    {
        return Status::NO_CONTENT;
    }

    /**
     * Create for no changes detected
     */
    public static function noChanges(string $resource = ''): self
    {
        $message = 'No changes detected';
        if ($resource) {
            $message .= " for {$resource}";
        }

        return new self(message: $message);
    }

    /**
     * Create for identical data
     */
    public static function identicalData(string $resource): self
    {
        return new self(
            message: "Identical data provided for {$resource}. No changes made."
        );
    }

    /**
     * Create for no modifications needed
     */
    public static function noModificationNeeded(string $resource): self
    {
        return new self(
            message: "No modification needed for {$resource}"
        );
    }
}
```

**Usage Example**:
```php
// No changes detected
throw NoChangesException::noChanges('User profile');

// Identical data
throw NoChangesException::identicalData('User settings');

// No modification needed
throw NoChangesException::noModificationNeeded('Configuration');
```

---

## 🔧 Exception Handling Patterns

### 1. **Controller Exception Handling**
```php
final class ExampleController
{
    public function __construct(
        private ExampleApplicationService $service,
        private ExceptionResponderFactory $responderFactory
    ) {}

    public function actionUpdate(int $id, UpdateRequest $request): ResponseInterface
    {
        try {
            $result = $this->service->update($id, $request);
            return $this->responseFactory->success($result);
        } catch (NotFoundException $e) {
            return $this->responderFactory->createNotFoundResponse($e);
        } catch (ValidationException $e) {
            return $this->responderFactory->createValidationResponse($e);
        } catch (ConflictException $e) {
            return $this->responderFactory->createConflictResponse($e);
        } catch (HttpException $e) {
            return $this->responderFactory->createErrorResponse($e);
        }
    }
}
```

### 2. **Service Exception Handling**
```php
final class ExampleApplicationService
{
    public function update(int $id, UpdateRequest $command): ExampleResponse
    {
        $example = $this->repository->findById($id);
        
        if ($example === null) {
            throw NotFoundException::resourceNotFound('Example', 'id', $id);
        }

        if (!$example->canBeUpdated()) {
            throw BusinessRuleException::violation('Example cannot be updated in current status');
        }

        try {
            $this->repository->update($example);
            return ExampleResponse::fromEntity($example);
        } catch (OptimisticLockException $e) {
            throw ConflictException::concurrentModification('Example', previous: $e);
        }
    }
}
```

### 3. **Repository Exception Handling**
```php
final class ExampleRepository
{
    public function findById(int $id): ?Example
    {
        try {
            $data = $this->db->createQueryBuilder()
                ->select('*')
                ->from('example')
                ->where('id', $id)
                ->andWhere('status', '!=', RecordStatus::DELETED->value)
                ->fetchOne();

            return $data ? $this->mapper->toEntity($data) : null;
        } catch (\Exception $e) {
            throw ServiceException::databaseError('findById', $e->getMessage(), previous: $e);
        }
    }

    public function save(Example $example): void
    {
        try {
            $this->db->createCommand()
                ->insert('example', $this->mapper->toArray($example))
                ->execute();
        } catch (\PDOException $e) {
            if ($e->getCode() === '23505') { // Unique constraint violation
                throw ConflictException::duplicateResource('Example', 'email', $example->getEmail());
            }
            throw ServiceException::databaseError('save', $e->getMessage(), previous: $e);
        }
    }
}
```

---

## 🚀 Best Practices

### 1. **Exception Creation**
```php
// ✅ Use specific exception types
throw NotFoundException::resourceNotFound('User', 'id', $id);

// ❌ Avoid generic exceptions
throw new RuntimeException('User not found');
```

### 2. **Error Messages**
```php
// ✅ Provide context
throw ValidationException::fieldError('email', 'Invalid email format');

// ❌ Avoid generic messages
throw new ValidationException('Validation failed');
```

### 3. **HTTP Status Codes**
```php
// ✅ Use appropriate status codes
throw new UnauthorizedException(); // 401
throw new ForbiddenException();   // 403
throw new NotFoundException();    // 404

// ❌ Avoid wrong status codes
throw new BadRequestException('User not found'); // Should be 404
```

### 4. **Exception Chaining**
```php
// ✅ Chain exceptions for context
throw ServiceException::serviceError('Database', 'Connection failed', previous: $e);

// ❌ Lose original exception
throw new ServiceException('Database error');
```

---

## 📊 Performance Considerations

### 1. **Exception Overhead**
- Exceptions are expensive, use for exceptional cases only
- Avoid using exceptions for flow control
- Use validation before business logic

### 2. **Memory Usage**
- Keep exception messages concise
- Avoid storing large objects in exceptions
- Use exception chaining wisely

### 3. **Logging**
- Log exceptions with proper context
- Include relevant debugging information
- Use appropriate log levels

---

## 🎯 Summary

The exception hierarchy provides a structured way to handle different types of errors in the Yii3 API application. Key benefits include:

- **🔍 Type Safety**: Strong typing for different error types
- **📡 HTTP Compliance**: Proper HTTP status code mapping
- **🌐 Localization**: Translatable error messages
- **🔧 Debugging**: Detailed error information for developers
- **📦 Consistency**: Uniform error response format
- **🚀 Performance**: Optimized exception handling

By following the patterns and best practices outlined in this guide, you can build robust, maintainable exception handling for your Yii3 API application! 🚀
