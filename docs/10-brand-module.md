# Brand Module (API v1)

## Routes

### Production Routes (with authentication)
Defined in `config/common/routes.php` under `/v1`:

- `GET /v1/brand` -> `BrandListAction` (permission: `brand.index`)
- `POST /v1/brand/data` -> `BrandDataAction` (permission: `brand.data`)
- `GET /v1/brand/{id}` -> `BrandViewAction` (permission: `brand.view`)
- `POST /v1/brand/create` -> `BrandCreateAction` (permission: `brand.create`)
- `PUT /v1/brand/{id}` -> `BrandUpdateAction` (permission: `brand.update`)
- `DELETE /v1/brand/{id}` -> `BrandDeleteAction` (permission: `brand.delete`)

### Testing Routes (without authentication)
For development and testing under `/test`:

- `GET /test/brands` -> `BrandListAction`
- `POST /test/brands` -> `BrandCreateAction`
- `GET /test/brands/{id}` -> `BrandViewAction`
- `PUT /test/brands/{id}` -> `BrandUpdateAction`
- `DELETE /test/brands/{id}` -> `BrandDeleteAction`

## Domain Architecture

### Entity Layer
- **Brand Entity**: Rich domain object with business logic
  - Uses `StatusDelegationTrait` for business methods
  - Provides `canBeDeleted()`, `canBeUpdated()`, etc.
  - Encapsulates status transition rules

### Value Objects
- **Status**: Immutable value object with business rules
  - Status transition validation
  - `isValidForCreation()` method
  - `canTransitionTo()` method

### Application Layer
- **BrandInputValidator**: Input format validation
- **BrandValidator**: Business rules validation
  - `validateForCreation()`: Status validation
  - `validateForUpdate()`: Business rule validation
  - `validateForDelete()`: Entity-based validation

### Service Layer
- **BrandService**: Domain operations orchestration
  - `get()`: Get brand data as array
  - `create()`: Create brand
  - `update()`: Update brand
  - `delete()`: Delete brand
  - `getEntity()`: Get Brand entity (for business validation)
  - `createEntity()`: Create Brand entity
  - `saveEntity()`: Save Brand entity

### Repository Layer
- **BrandRepositoryInterface**: Domain contract
- **DbBrandRepository**: Concrete implementation
  - Array-based data access
  - JSON field normalization
  - Audit trail management

## Business Rules

### Status Validation
- **Creation**: Only DRAFT (1) and ACTIVE (2) allowed
- **Updates**: Based on `ALLOWED_UPDATE_STATUS_LIST`
- **Deletion**: Only non-ACTIVE brands can be deleted

### Entity Validation
- Uses entity business methods for validation
- `canBeDeleted()` checks status before deletion
- `canBeUpdated()` checks status before update

## Service and Repository

- `App\Domain\Brand\BrandService`
- `App\Domain\Brand\BrandRepositoryInterface`
- `App\Infrastructure\Persistence\Brand\DbBrandRepository`

## Filtering and sorting

Repository supports:

- filters: `id`, `name` (LIKE/ILIKE), `status`, `sync_mdb`
- sorting: `id`, `name`, `status`

## Audit Trail

All brand operations include audit trail in `detail_info.change_log`:
- `created_at` / `created_by`: Creation tracking
- `updated_at` / `updated_by`: Update tracking
- `previous_status` / `new_status`: Status change tracking

## Error Handling

- **ValidationException**: Input validation errors
- **NotFoundException**: Resource not found
- **ForbiddenException**: Access denied
- **Translated messages**: Internationalized error messages
