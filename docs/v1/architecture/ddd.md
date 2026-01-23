# Domain-Driven Design (DDD)

## 🎯 Overview

Yii3 API follows Domain-Driven Design principles to create a maintainable and scalable architecture that focuses on business logic and domain expertise.

## 🏗️ Core Concepts

### 📦 Bounded Context
Each domain module represents a bounded context with clear boundaries:
- **Brand Context**: Brand management business logic
- **Shared Context**: Common domain concepts shared across contexts

### 🎯 Entities
Objects with identity that persist over time:
```php
final class Brand
{
    private ?int $id;
    private string $name;
    private Status $status;
    private DetailInfo $detailInfo;
    
    // Business methods
    public function changeName(string $newName): void
    public function changeStatus(Status $newStatus): void
    public function canBeDeleted(): bool
}
```

### 💎 Value Objects
Objects defined by their values, without identity:
```php
final readonly class Status
{
    private function __construct(private RecordStatus $enum) {}
    
    public static function draft(): self
    public static function active(): self
    public function isActive(): bool
    public function allowsTransitionTo(Status $newStatus): bool
}

final readonly class DetailInfo
{
    public function __construct(public readonly array $data) {}
    
    public function toArray(): array
    public function toJson(): string
}
```

### 🏭 Aggregates
Clusters of domain objects treated as a single unit:
- **Brand Aggregate**: Root entity `Brand` with consistent boundaries
- **Repository Pattern**: One repository per aggregate root

### 📋 Domain Services
Stateless services that coordinate domain operations:
```php
final class BrandDomainService
{
    public function validateUniqueName(string $name): void
    public function canDeleteBrand(Brand $brand): void
    public function buildChangeLog(): array
}
```

### 🔄 Domain Events
Events that occur within the domain that other parts may react to:
```php
// Future implementation
final class BrandCreated implements DomainEvent
{
    public function __construct(
        public readonly BrandId $brandId,
        public readonly string $name,
        public readonly DateTimeImmutable $occurredAt
    ) {}
}
```

## 🎨 Strategic Design

### 🗺️ Ubiquitous Language
Common vocabulary shared between developers and domain experts:
- **Brand**: Company or product identifier
- **Status**: Current state (draft, active, inactive, etc.)
- **Detail Info**: Flexible metadata storage
- **Sync MDB**: Integration with external system

### 📊 Context Mapping
Relationships between different bounded contexts:
```
Brand Context
├── Shared Context (shared kernel)
├── API Context (anti-corruption layer)
└── Infrastructure Context (conformist)
```

## 🏗️ Tactical Design

### 📁 Repository Pattern
Abstract persistence mechanism for aggregates:
```php
interface BrandRepositoryInterface
{
    public function findById(int $id): ?Brand;
    public function findByName(string $name): ?Brand;
    public function save(Brand $brand): Brand;
    public function delete(Brand $brand): void;
    public function list(SearchCriteria $criteria): PaginatedResult;
}
```

### 🏭 Factory Pattern
Complex object creation logic:
```php
final class BrandFactory
{
    public static function create(array $data): Brand
    public static function reconstitute(array $row): Brand
}
```

### 📋 Specifications
Business rules that can be combined:
```php
interface BrandSpecification
{
    public function isSatisfiedBy(Brand $brand): bool;
}

final class ActiveBrandSpecification implements BrandSpecification
{
    public function isSatisfiedBy(Brand $brand): bool
    {
        return $brand->getStatus()->isActive();
    }
}
```

## 🔄 Domain Life Cycle

### 📝 Creation
```php
$brand = Brand::create(
    name: $data['name'],
    status: Status::from($data['status']),
    detailInfo: DetailInfo::fromArray($data['detail_info'] ?? [])
);
```

### 🔄 Modification
```php
$brand->changeName($newName);
$brand->changeStatus($newStatus);
```

### 💾 Persistence
```php
$savedBrand = $brandRepository->save($brand);
```

### 🗑️ Deletion
```php
if ($brand->canBeDeleted()) {
    $brandRepository->delete($brand);
}
```

## 🎯 Benefits

### 🏗️ Maintainability
- Clear separation of concerns
- Business logic isolated from infrastructure
- Easy to understand and modify

### 🔄 Flexibility
- Domain logic independent of persistence
- Easy to change implementation details
- Testable business rules

### 📚 Expressiveness
- Code reflects business language
- Self-documenting domain model
- Better collaboration with domain experts

### 🧪 Testability
- Isolated domain logic
- Easy unit testing
- Mock-friendly interfaces

## 📚 Best Practices

### ✅ Do's
- Keep domain objects pure (no infrastructure dependencies)
- Use rich domain models with behavior
- Implement business rules in entities/value objects
- Use invariants to maintain consistency
- Create specific exceptions for domain rules

### ❌ Don'ts
- Expose internal state unnecessarily
- Create anemic domain models
- Mix infrastructure concerns with domain logic
- Use primitive types for domain concepts
- Ignore business invariants

## 🔄 Evolution

Domain model evolves with business requirements:
1. **Start Simple**: Basic entities and value objects
2. **Add Behavior**: Business methods and rules
3. **Refine Boundaries**: Better context definitions
4. **Introduce Patterns**: Services, specifications, events
5. **Optimize Performance**: Caching, lazy loading, batching

---

## 📚 Related Documentation

- [Layer Architecture](layers.md)
- [Brand Domain](brand.md)
- [Value Objects](value-objects.md)
- [Entities](entities.md)
