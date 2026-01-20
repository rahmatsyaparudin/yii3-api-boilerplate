# Validation

## Validator Base

`App\Shared\Validation\AbstractValidator` wraps `yiisoft/validator`.

## Validation Layers

### 1. Input Validation (Format)
- **BrandInputValidator**: Input format validation using Yii3 rules
  - Required fields validation
  - Type checking
  - Length constraints
  - Format validation

### 2. Business Validation (Rules)
- **BrandValidator**: Business rules validation
  - `validateForCreation()`: Status validation for creation
  - `validateForUpdate()`: Business rule validation for updates
  - `validateForDelete()`: Entity-based validation for deletion

## Entity-Based Validation

### Status Validation
```php
// In BrandValidator::validateForCreation()
if ($data->status !== null) {
    $status = Status::fromInt($data->status);
    if (!$status->isValidForCreation()) {
        throw new ValidationException(/* ... */);
    }
}
```

### Business Rules Validation
```php
// In BrandValidator::validateForUpdate()
$status = Status::fromInt($brand['status']);
if (!$status->canBeUpdated()) {
    throw new ValidationException(/* ... */);
}
```

### Entity Validation
```php
// In BrandValidator::validateForDelete()
public function validateForDelete(Brand $brand): void
{
    if (!$brand->canBeDeleted()) {
        throw new ValidationException(/* ... */);
    }
}
```

## Validation Contexts

- `ValidationContext::CREATE`: Creation validation
- `ValidationContext::UPDATE`: Update validation
- `ValidationContext::DELETE`: Delete validation
- `ValidationContext::SEARCH`: Search validation

## Business Rules

### Status Rules
- **Creation**: Only DRAFT (1) and ACTIVE (2) allowed
- **Updates**: Based on status transition rules
- **Deletion**: Only non-ACTIVE brands can be deleted

### Cross-Entity Validation
- **Unique Field Validation**: Brand name uniqueness
- **Dependency Checking**: Check if brand has dependencies (TODO)

## Error Formatting

`App\Shared\Helper\ValidationHelper` formats validator results for API responses.

### Error Structure
```json
{
    "code": 422,
    "success": false,
    "message": "Validation failed",
    "errors": {
        "field": "Error message",
        "brand": "Business rule error message"
    }
}
```

## Internationalization

All validation messages are translated using `TranslatorInterface`:
- `status.invalid_on_creation`: Status validation error
- `status.forbid_update`: Status update forbidden
- `cannot_delete_active`: Cannot delete active brand

## Validation Flow

1. **Input Validation**: Format and type checking
2. **Business Validation**: Domain rules validation
3. **Entity Validation**: Entity business methods
4. **Cross-Entity Validation**: Uniqueness and dependencies
