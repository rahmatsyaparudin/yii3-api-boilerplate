# Architecture Overview

## Layers

- **API layer**: `src/Api/*` - HTTP endpoints and request/response handling
- **Application layer**: `src/Domain/*/Application/*` - Use cases and validation
- **Domain layer**: `src/Domain/*` - Business logic, entities, and domain services
- **Infrastructure layer**: `src/Infrastructure/*` - External concerns (DB, monitoring, etc.)
- **Shared utilities**: `src/Shared/*` - Cross-cutting concerns

## Domain-Driven Design (DDD) Pattern

### Entity Integration
- **Entities**: Rich domain objects with business logic (e.g., `Brand` entity)
- **Value Objects**: Immutable objects (e.g., `Status` value object)
- **Repositories**: Data access abstraction with array returns
- **Services**: Domain operations orchestration
- **Validators**: Input validation and business rules

### Business Rules
- **StatusDelegationTrait**: Provides business methods to entities
- **Status Value Object**: Encapsulates status transition logic
- **Entity Validation**: Business rules validation in domain layer

## Configuration

- Params: `config/common/params.php`
- Routes: `config/common/routes.php` (includes testing routes)
- Common DI: `config/common/di/*.php`
- Web DI / middleware pipeline: `config/web/di/application.php`

## Data Access

Repositories live in `src/Infrastructure/Persistence/*` and implement interfaces from `src/Domain/*`.

### Repository Pattern
- **Interface**: Domain layer defines contract
- **Implementation**: Infrastructure layer provides concrete implementation
- **Returns**: Arrays for data, Entities for business logic

## Middleware Pipeline

1. **RequestParamsMiddleware**: Parse and normalize request parameters
2. **CorsMiddleware**: Handle CORS headers
3. **RateLimitMiddleware**: Rate limiting
4. **SecureHeadersMiddleware**: Security headers
5. **AccessMiddleware**: Permission-based access control
6. **Monitoring Middleware**: Observability and logging

## Testing Routes

For development and testing, routes without authentication are available under `/test/*`:
- `/test/brands` - List brands (no auth required)
- `/test/brands/{id}` - Get brand by ID (no auth required)
- `/test/brands` - Create brand (no auth required)
- `/test/brands/{id}` - Update brand (no auth required)
- `/test/brands/{id}` - Delete brand (no auth required)
