# Layer Architecture

## 🏗️ Overview

Yii3 API follows a clean layered architecture with clear separation of concerns. Each layer has specific responsibilities and communicates only with adjacent layers.

## 📋 Architecture Layers

```
┌─────────────────────────────────────┐
│              API Layer               │  ← HTTP Endpoints, Request/Response
├─────────────────────────────────────┤
│          Application Layer           │  ← Use Cases, Application Services
├─────────────────────────────────────┤
│             Domain Layer              │  ← Business Logic, Entities, Rules
├─────────────────────────────────────┤
│         Infrastructure Layer         │  ← Database, External Services
├─────────────────────────────────────┤
│            Shared Layer              │  ← Common Utilities, Cross-cutting
└─────────────────────────────────────┘
```

## 🌐 API Layer (Presentation)

### 📍 Location: `src/Api/`

**Responsibilities:**
- Handle HTTP requests and responses
- Input validation and transformation
- Response formatting
- Error handling and status codes

**Key Components:**
```php
// Actions/Controllers
final class BrandCreateAction
{
    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        // Handle request, call application service, return response
    }
}

// Response Factory
final class ResponseFactory
{
    public function success(array $data, ?Message $message = null): ResponseInterface
    public function fail(Message $translate, int $httpCode): ResponseInterface
}
```

**Communication Flow:**
- **Receives**: HTTP requests from clients
- **Calls**: Application layer services
- **Returns**: HTTP responses to clients

### 🎯 Rules:
- ✅ Handle HTTP-specific concerns only
- ✅ Validate input parameters
- ✅ Format responses according to API standards
- ❌ No business logic
- ❌ No database access
- ❌ No external service calls

---

## 🏗️ Application Layer (Use Cases)

### 📍 Location: `src/Application/`

**Responsibilities:**
- Coordinate use cases
- Transaction management
- Domain object orchestration
- Business workflow implementation

**Key Components:**
```php
final class BrandApplicationService
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,
        private BrandDomainService $brandDomainService
    ) {}
    
    public function create(array $data): Brand
    {
        // Business workflow
        $this->brandDomainService->validateUniqueName($data['name']);
        $brand = Brand::create(/* ... */);
        return $this->brandRepository->save($brand);
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        return $this->brandRepository->list($criteria);
    }
}
```

**Communication Flow:**
- **Called by**: API layer
- **Uses**: Domain layer services and repositories
- **Manages**: Transactions and workflows

### 🎯 Rules:
- ✅ Implement business use cases
- ✅ Coordinate domain objects
- ✅ Manage transactions
- ✅ Handle cross-domain operations
- ❌ No HTTP concerns
- ❌ No direct database access
- ❌ No business rules (delegate to domain)

---

## 🎯 Domain Layer (Business Logic)

### 📍 Location: `src/Domain/`

**Responsibilities:**
- Core business logic
- Domain entities and value objects
- Business rules and invariants
- Domain services

**Key Components:**
```php
// Entity
final class Brand
{
    private function __construct(
        private ?int $id,
        private string $name,
        private Status $status,
        private DetailInfo $detailInfo
    ) {}
    
    public static function create(/* ... */): self
    public function changeName(string $newName): void
    public function canBeDeleted(): bool
}

// Value Object
final readonly class Status
{
    public static function draft(): self
    public static function active(): self
    public function isActive(): bool
    public function allowsTransitionTo(Status $newStatus): bool
}

// Domain Service
final class BrandDomainService
{
    public function validateUniqueName(string $name): void
    public function canDeleteBrand(Brand $brand): void
}

// Repository Interface
interface BrandRepositoryInterface
{
    public function save(Brand $brand): Brand;
    public function findById(int $id): ?Brand;
    public function list(SearchCriteria $criteria): PaginatedResult;
}
```

**Communication Flow:**
- **Used by**: Application layer
- **Defines**: Business contracts (interfaces)
- **Contains**: Pure business logic

### 🎯 Rules:
- ✅ Pure business logic
- ✅ Rich domain models
- ✅ Business invariants
- ✅ Domain-specific exceptions
- ❌ No infrastructure dependencies
- ❌ No framework concerns
- ❌ No external services

---

## 🗄️ Infrastructure Layer (External)

### 📍 Location: `src/Infrastructure/`

**Responsibilities:**
- Data persistence
- External service integration
- Framework-specific implementations
- Technical concerns

**Key Components:**
```php
// Repository Implementation
final class BrandRepository implements BrandRepositoryInterface
{
    public function __construct(private Connection $db) {}
    
    public function save(Brand $brand): Brand
    {
        // Database operations
        return $this->persist($brand);
    }
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        // Query building and execution
        return $this->query($criteria);
    }
}

// External Service
final class JwtService
{
    public function encode(array $payload): string
    public function decode(string $token): array
}

// Security
final class AccessChecker implements AccessCheckerInterface
{
    public function userHasPermission($userId, string $permission): bool
}
```

**Communication Flow:**
- **Implements**: Domain layer interfaces
- **Handles**: External systems
- **Provides**: Technical capabilities

### 🎯 Rules:
- ✅ Implement domain interfaces
- ✅ Handle external integrations
- ✅ Manage technical concerns
- ✅ Optimize performance
- ❌ No business logic
- ❌ No application workflows
- ❌ No HTTP handling

---

## 📦 Shared Layer (Common)

### 📍 Location: `src/Shared/`

**Responsibilities:**
- Common utilities
- Cross-cutting concerns
- Reusable components
- Infrastructure-agnostic code

**Key Components:**
```php
// Value Objects
final readonly class Message
{
    public function __construct(
        public readonly string $key,
        public readonly array $params = [],
        public readonly ?string $domain = null
    ) {}
}

// Request Handling
final readonly class RequestParams
{
    public function getFilter(): RawParams
    public function getPagination(): PaginationParams
    public function getSort(): SortParams
}

// Validation
final class RequestValidator
{
    public static function onlyAllowed(array $filters, array $allowedKeys): RawParams
}

// Exceptions
abstract class HttpException extends Exception
final class BadRequestException extends HttpException
final class NotFoundException extends HttpException
```

**Communication Flow:**
- **Used by**: All layers
- **Provides**: Common functionality
- **Maintains**: Reusability

### 🎯 Rules:
- ✅ Infrastructure-agnostic
- ✅ Reusable across layers
- ✅ No layer-specific logic
- ✅ Well-tested utilities
- ❌ No business rules
- ❌ No HTTP concerns
- ❌ No database access

---

## 🔄 Communication Patterns

### 📋 Dependency Flow
```
API Layer → Application Layer → Domain Layer ← Infrastructure Layer
    ↑              ↑              ↑              ↑
    └──────────────┴──────────────┴──────────────┘
                    Shared Layer (used by all)
```

### 🎯 Dependency Rules
- **API Layer**: Depends on Application + Shared
- **Application Layer**: Depends on Domain + Shared
- **Domain Layer**: Depends only on Shared (pure)
- **Infrastructure Layer**: Depends on Domain + Shared
- **Shared Layer**: No dependencies on other layers

### 🔧 Dependency Injection
```php
// API Layer
final class BrandCreateAction
{
    public function __construct(
        private BrandApplicationService $brandService,  // Application
        private ResponseFactory $responseFactory       // Shared
    ) {}
}

// Application Layer
final class BrandApplicationService
{
    public function __construct(
        private BrandRepositoryInterface $brandRepository,  // Domain interface
        private BrandDomainService $brandDomainService     // Domain
    ) {}
}
```

---

## 🎯 Benefits of Layered Architecture

### 🏗️ Maintainability
- Clear separation of concerns
- Easy to locate and modify code
- Reduced coupling between components

### 🔄 Testability
- Each layer can be tested independently
- Easy to mock dependencies
- Clear test boundaries

### 📈 Scalability
- Layers can be scaled independently
- Easy to replace implementations
- Supports microservices evolution

### 👥 Team Collaboration
- Different teams can work on different layers
- Clear interfaces between teams
- Reduced merge conflicts

---

## 📚 Best Practices

### ✅ Do's
- Keep layers independent and loosely coupled
- Use interfaces to define contracts between layers
- Implement dependency inversion principle
- Handle exceptions at appropriate layer boundaries
- Use DTOs for data transfer between layers

### ❌ Don'ts
- Skip layers (no direct API → Domain calls)
- Mix concerns from different layers
- Create circular dependencies
- Expose internal layer details
- Ignore layer boundaries for convenience

---

## 📚 Related Documentation

- [Domain-Driven Design](ddd.md)
- [Design Patterns](patterns.md)
- [Brand Domain](../domain/brand.md)
- [API Endpoints](../api/brand-endpoints.md)
