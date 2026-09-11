# Shared Components Guide

## 📋 Overview

This guide covers the shared components in `src/Shared/` (plus shared kernel code in `src/Domain/Shared/` and `src/Infrastructure/Core/`). These components provide reusable functionality across the application layers, following Domain-Driven Design (DDD) principles and promoting code reusability.

---

## 🏗️ Shared Architecture Overview

### Directory Structure

```
src/Shared/
├── ApplicationParams.php   # readonly app name/version/language/environment DTO
├── Common/
│   └── Context/
│       └── ValidationContext.php   # implements ValidationContextInterface
└── Core/
    ├── Dto/                # SearchCriteria, PaginatedResult
    ├── Enums/              # AppConstants, RecordStatus
    ├── ErrorHandler/       # ErrorHandlerResponse (ThrowableRendererInterface)
    ├── Exception/          # HttpException + final concrete exceptions
    ├── Middleware/         # PSR-15 middleware (JWT, CORS, rate limit, ...)
    ├── Query/              # QueryConditionApplier (static helpers)
    ├── Request/            # RawParams, RequestParams, PaginationParams, SortParams, ...
    ├── Security/           # InputSanitizer (static)
    ├── Utility/            # Arrays, FieldMapper, JsonHandler
    ├── Validation/         # AbstractValidator, ValidationContextInterface, Rules/
    └── ValueObject/        # Message, LockVersionConfig

src/Domain/Shared/Core/
├── Audit/                  # AuditServiceInterface
├── Concerns/
│   ├── Entity/             # Identifiable, Stateful, Descriptive, ChangeLogged
│   └── Service/            # DomainValidator (guard* / ensure* helpers)
├── Contract/               # ActorInterface, CurrentUserInterface, DateTimeProviderInterface
├── Enum/                   # SyncStatus, SyncDirection
├── Security/               # AuthorizerInterface
└── ValueObject/            # DetailInfo, LockVersion, ResourceStatus, SyncFlag, SyncMdb

src/Infrastructure/Core/
├── Concerns/               # HasCoreFeatures, HasMongoDBSync, ManagesPersistence, Auditable
└── Security/               # Actor, CurrentUser, JwtService, AccessChecker, ...
```

### Component Categories

#### **1. Data Management**
- **Dto/**: `SearchCriteria`, `PaginatedResult` for list endpoints
- **ValueObject/**: Immutable value objects (`Message`, `LockVersion`, `ResourceStatus`, `DetailInfo`, `SyncFlag`, `SyncMdb`, `LockVersionConfig`)
- **Query/**: `QueryConditionApplier` static condition helpers

#### **2. Request & Response**
- **Request/**: `RawParams`, `RequestParams`, `PaginationParams`, `SortParams`, `RequestDataParser`
- **Middleware/**: PSR-15 middleware for the request pipeline

#### **3. Error Handling**
- **Exception/**: `HttpException` hierarchy
- **ErrorHandler/**: `ErrorHandlerResponse` JSON renderer

#### **4. Business Logic**
- **Enums/**: `RecordStatus` enum, `AppConstants` constants
- **Domain Enum/**: `SyncStatus`, `SyncDirection`
- **Validation/**: `AbstractValidator`, `ValidationContextInterface`, custom rules (`UniqueValue`, `HasNoDependencies`)

#### **5. Infrastructure**
- **Security/**: `InputSanitizer` (input scrubbing)
- **Utility/**: `Arrays`, `FieldMapper`, `JsonHandler`
- **Concerns/**: entity/repository traits (`HasCoreFeatures`, `ManagesPersistence`, `HasMongoDBSync`, `Auditable`)

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
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private ExampleRepositoryInterface $repository,
    ) {}
}
```

### 2. **Static Utilities**
```php
// Some utilities provide static methods
use App\Shared\Core\Query\QueryConditionApplier;
use App\Shared\Core\Security\InputSanitizer;
use App\Shared\Core\Utility\Arrays;

$clean    = InputSanitizer::process($input);
$filtered = Arrays::removeNulls($data);
QueryConditionApplier::filterByExactMatch($query, $filters, $allowedColumns);
```

### 3. **Value Objects**
```php
// Immutable value objects / enums
use App\Domain\Shared\Core\ValueObject\ResourceStatus;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\ValueObject\Message;

$status  = ResourceStatus::active();           // VO wrapping RecordStatus::ACTIVE
$message = Message::create(key: 'resource.not_found', params: ['resource' => 'Example']);
$state   = RecordStatus::ACTIVE;               // backed enum case
```

### 4. **Exception Handling**
```php
// Custom exceptions carry a Message value object
use App\Shared\Core\Exception\NotFoundException;

throw new NotFoundException(
    translate: Message::create(
        key: 'resource.not_found',
        params: ['resource' => 'User', 'field' => 'id', 'value' => $id],
    ),
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
- Value objects and DTOs are `readonly`
- Prevents accidental state changes

### 5. **Testability**
- Components are easily testable
- Dependency injection enables mocking

---

## 📊 Integration Examples

### 1. **Action Integration**
```php
final class ExampleDataAction
{
    public function __construct(
        private SearchCriteriaFactory $factory,
        private ExampleApplicationService $applicationService,
        private ResponseFactory $responseFactory,
    ) {}

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload — set by RequestParamsMiddleware */
        $payload = $request->getAttribute('payload');

        $criteria = $this->factory->createFromRequest(
            params: $payload,
            allowedSort: ['id' => 'id', 'name' => 'name'],
        );

        $result = $this->applicationService->list(criteria: $criteria);

        return $this->responseFactory->success(
            data: $result->data,
            meta: $result->getMeta(),
        );
    }
}
```

### 2. **Service Integration**
```php
final class ExampleApplicationService
{
    public function __construct(
        private AuthorizerInterface $auth,
        private ExampleRepositoryInterface $repository,
        private ExampleDomainService $domainService,
    ) {}

    public function delete(int $id): ExampleResponse
    {
        $this->domainService->guardPermission(
            id: $id,
            authorizer: $this->auth,
            permission: 'example.delete',
            resource: $this->getResource(),
        );
        // ...
    }
}
```

### 3. **Repository Integration**
```php
final class ExampleRepository implements ExampleRepositoryInterface, CurrentUserAwareInterface
{
    use HasCoreFeatures;
    use HasMongoDBSync;
    use ManagesPersistence;

    public function findById(int $id, ?int $status = null): ?Example
    {
        $row = (new Query($this->db))
            ->from(self::TABLE_NAME)
            ->where(['id' => $id])
            ->andWhere($this->scopeWhereNotDeleted())
            ->andWhere($this->scopeByStatus($status))
            ->one();

        return $row ? Example::reconstitute(/* ... */) : null;
    }
}
```

---

## 🚀 Best Practices

### 1. **Component Usage**
```php
// ✅ Use dependency injection
public function __construct(
    private ExampleRepositoryInterface $repository
) {}

// ❌ Avoid manual instantiation of services
$repository = new ExampleRepository($db);
```

### 2. **Error Handling**
```php
// ✅ Use custom exceptions
throw new NotFoundException(translate: Message::create(key: 'resource.not_found', params: [...]));

// ❌ Avoid generic exceptions
throw new RuntimeException('User not found');
```

### 3. **Validation**
```php
// ✅ Use value objects and validators
$status = ResourceStatus::from($input);       // throws on invalid value
$validator->validate($data, ValidationContextInterface::CREATE);

// ❌ Avoid scattered manual checks
if (!filter_var($input, FILTER_VALIDATE_EMAIL)) {
    throw new ValidationException();
}
```

### 4. **Data Transfer**
```php
// ✅ Use typed DTOs / commands for API communication
final readonly class CreateExampleCommand
{
    public function __construct(
        public string $name,
        public int $status,
    ) {}
}

// ❌ Avoid passing raw associative arrays between layers
$data = ['name' => $name, 'status' => $status];
```

---

## 📚 Related Documentation

- **[Architecture Guide](architecture-guide.md)**: Complete architecture overview
- **[DI Configuration Guide](di-configuration-guide.md)**: Dependency injection setup
- **[Migration & Seeding Guide](migration-seeding-guide.md)**: Database migrations and seeders
- **[Quality Guide](quality-guide.md)**: Code quality tooling and standards
- **[Setup Guide](setup-guide.md)**: Project setup and environment configuration

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
