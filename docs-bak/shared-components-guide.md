# Shared Components Guide

## 📋 Overview

This guide covers the shared components in `src/Shared/`. These components provide reusable functionality across the application layers, following Domain-Driven Design (DDD) principles and promoting code reusability.

---

## 🏗️ Shared Architecture Overview

### Directory Structure

```
src/Shared/
├── ApplicationParams.php    # Application parameters management
├── Dto/                    # Data Transfer Objects
├── Enums/                  # Shared enumerations
├── ErrorHandler/           # Error handling utilities
├── Exception/              # Custom exception classes
├── Middleware/             # HTTP middleware components
├── Query/                  # Query building utilities
├── Request/                # Request handling utilities
├── Security/               # Security utilities
├── Utility/                # General utility functions
├── Validation/             # Validation utilities
└── ValueObject/            # Value object implementations
```

### Component Categories

#### **1. Data Management**
- **Dto/**: Data Transfer Objects for API communication
- **ValueObject/**: Immutable value objects with validation
- **Query/**: Query building and execution utilities

#### **2. Request & Response**
- **Request/**: HTTP request processing and validation
- **Middleware/**: HTTP middleware for request pipeline

#### **3. Error Handling**
- **Exception/**: Custom exception hierarchy
- **ErrorHandler/**: Centralized error processing

#### **4. Business Logic**
- **Enums/**: Shared enumerations and constants
- **Validation/**: Validation rules and utilities

#### **5. Infrastructure**
- **Security/**: Security utilities and helpers
- **Utility/**: General utility functions

---

## 📚 Individual Component Guides

### 1. [Data Transfer Objects (Dto)](dto-guide.md)
Purpose: Data structures for API communication and data transfer between layers.

### 2. [Enumerations (Enums)](enums-guide.md)
Purpose: Shared enumerations and constants used across the application.

### 3. [Error Handling](error-handling-guide.md)
Purpose: Error handling utilities and exception management.

### 4. [Exceptions](exceptions-guide.md)
Purpose: Custom exception classes for different error scenarios.

### 5. [Middleware](middleware-guide.md)
Purpose: HTTP middleware components for request processing pipeline.

### 6. [Query Building](query-guide.md)
Purpose: Query building utilities for database operations.

### 7. [Request Processing](request-guide.md)
Purpose: HTTP request processing and validation utilities.

### 8. [Security](security-guide.md)
Purpose: Security utilities and authentication helpers.

### 9. [Utilities](utility-guide.md)
Purpose: General utility functions and helper classes.

### 10. [Validation](validation-guide.md)
Purpose: Validation rules and data validation utilities.

### 11. [Value Objects](value-object-guide.md)
Purpose: Immutable value objects with built-in validation.

### 12. [Application Parameters](application-params-guide.md)
Purpose: Application parameters management and configuration.

---

## 🔧 Usage Patterns

### 1. **Dependency Injection**
```php
// Shared components are designed for DI injection
final class ExampleService
{
    public function __construct(
        private ValidatorInterface $validator,
        private ErrorHandlerInterface $errorHandler
    ) {}
}
```

### 2. **Static Utilities**
```php
// Some utilities provide static methods
$isValid = ValidationHelper::isValidEmail($email);
$hash = SecurityHelper::hashPassword($password);
```

### 3. **Value Objects**
```php
// Immutable value objects with validation
$email = new Email($userInput);
$status = new RecordStatus(RecordStatus::ACTIVE);
```

### 4. **Exception Handling**
```php
// Custom exceptions with proper context
throw new NotFoundException(
    resource: 'User',
    field: 'id',
    value: $id
);
```

---

## 🎯 Design Principles

### 1. **Single Responsibility**
- Each component has a single, well-defined purpose
- Components are focused and maintainable

### 2. **Reusability**
- Components are designed to be reused across different layers
- No coupling to specific domain logic

### 3. **Type Safety**
- Strong typing with PHP 8+ features
- Value objects ensure data integrity

### 4. **Immutability**
- Value objects are immutable
- Prevents accidental state changes

### 5. **Testability**
- Components are easily testable
- Dependency injection enables mocking

---

## 📊 Integration Examples

### 1. **Controller Integration**
```php
final class ExampleController
{
    public function __construct(
        private RequestValidator $requestValidator,
        private ResponseFormatter $responseFormatter
    ) {}
    
    public function actionCreate(CreateRequest $request): ResponseInterface
    {
        $validatedData = $this->requestValidator->validate($request);
        return $this->responseFormatter->success($validatedData);
    }
}
```

### 2. **Service Integration**
```php
final class ExampleApplicationService
{
    public function __construct(
        private DomainValidator $domainValidator,
        private ErrorHandler $errorHandler
    ) {}
    
    public function create(CreateExampleCommand $command): ExampleResponse
    {
        if (!$this->domainValidator->isValid($command)) {
            $this->errorHandler->handleValidationError();
        }
        
        // Business logic here
    }
}
```

### 3. **Repository Integration**
```php
final class ExampleRepository
{
    public function __construct(
        private QueryBuilder $queryBuilder,
        private DataMapper $dataMapper
    ) {}
    
    public function findById(int $id): ?Example
    {
        $query = $this->queryBuilder->select('*')
            ->from('example')
            ->where('id', $id);
            
        $data = $this->executeQuery($query);
        return $data ? $this->dataMapper->toEntity($data) : null;
    }
}
```

---

## 🚀 Best Practices

### 1. **Component Usage**
```php
// ✅ Use dependency injection
public function __construct(
    private SharedComponentInterface $component
) {}

// ❌ Avoid static instantiation
$component = new SharedComponent();
```

### 2. **Error Handling**
```php
// ✅ Use custom exceptions
throw new ResourceNotFoundException('User', 'id', $id);

// ❌ Avoid generic exceptions
throw new RuntimeException('User not found');
```

### 3. **Validation**
```php
// ✅ Use value objects
$email = new Email($input);

// ❌ Avoid manual validation
if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
    throw new ValidationException('Invalid email');
}
```

### 4. **Data Transfer**
```php
// ✅ Use DTOs for API communication
class CreateUserRequest extends AbstractDto
{
    public function __construct(
        public readonly string $name,
        public readonly string $email
    ) {}
}

// ❌ Avoid associative arrays
$data = ['name' => $name, 'email' => $email];
```

---

## 📚 Related Documentation

- **[Architecture Guide](architecture-guide.md)**: Complete architecture overview
- **[DI Configuration Guide](di-configuration-guide.md)**: Dependency injection setup
- **[API Documentation](api-documentation.md)**: API development guidelines
- **[Testing Guide](testing-guide.md)**: Testing strategies and utilities

---

## 🎯 Summary

The shared components provide a robust foundation for building maintainable, testable, and reusable code in the Yii3 API application. Key benefits include:

- **🔄 Reusability**: Components can be used across different layers
- **🛡️ Type Safety**: Strong typing prevents runtime errors
- **🧪 Testability**: Easy to unit test with dependency injection
- **📦 Modularity**: Each component has a single responsibility
- **🔧 Maintainability**: Clear separation of concerns
- **🚀 Performance**: Optimized for common use cases

By following the patterns and best practices outlined in this guide, you can build robust, maintainable shared components for your Yii3 API application! 🚀
