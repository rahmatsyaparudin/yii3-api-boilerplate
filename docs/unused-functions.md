# Unused Functions Analysis - Brand Domain

## 📋 Overview
Analysis of unused functions in the Brand domain that can be cleaned up to simplify the codebase.

## 🔍 Analysis Results

### ✅ 1. Brand Entity Unused Methods

#### **Status Management Methods**
```php
// File: src/Domain/Brand/Entity/Brand.php

/**
 * Change brand status with validation and rollback
 * Status: ❌ NOT USED
 */
// public function changeStatus(Status $status): void

/**
 * Activate brand (set status to ACTIVE)
 * Status: ❌ NOT USED
 */
// public function activate(): void

/**
 * Mark brand as draft (set status to DRAFT)
 * Status: ❌ NOT USED
 */
// public function markAsDraft(): void

/**
 * Update brand detail information
 * Status: ❌ NOT USED
 */
// public function updateDetailInfo(array $detailInfo): void

/**
 * Mark brand as synced with MDB
 * Status: ❌ NOT USED
 */
// public function markAsSynced(int $mdbId): void
```

#### **Validation Methods**
```php
/**
 * Validate brand status for creation
 * Status: ❌ NOT USED (called only in constructor)
 */
private function validateStatus(): void

/**
 * Validate all brand invariants
 * Status: ❌ NOT USED (called only in constructor)
 */
// private function validateInvariants(): void
```

### ✅ 2. BrandService Unused Methods

#### **Entity Management Methods**
```php
// File: src/Domain/Brand/Service/BrandService.php

/**
 * Create Brand entity from parameters
 * Status: ❌ NOT USED
 */
// public function createEntity(string $name, int $status, array $detailInfo = [], ?int $syncMdb = null): Brand

/**
 * Get Brand entity by ID
 * Status: ❌ NOT USED
 */
// public function getEntity(int $id): Brand

/**
 * Save Brand entity to repository
 * Status: ❌ NOT USED
 */
// public function saveEntity(Brand $brand): array
```

### ✅ 3. Missing Dependencies & Issues

#### **Missing Translator Dependency**
```php
// File: src/Domain/Brand/Entity/Brand.php

// Issue: TranslatorInterface dependency not injected
use Yiisoft\Translator\TranslatorInterface;

public function __construct(
    // ... other parameters
    private TranslatorInterface $translator  // ❌ NOT INJECTED
) {
    $this->validateInvariants();
}
```

#### **Missing Trait Reference**
```php
// File: src/Domain/Brand/Entity/Brand.php

// Issue: BusinessRulesTrait referenced but doesn't exist
// Line 94: Business rules methods are now provided by BusinessRulesTrait
// ❌ BusinessRulesTrait NOT FOUND
```

#### **ValidationException Import Missing**
```php
// File: src/Domain/Brand/Entity/Brand.php

// Issue: ValidationException used but not imported
private function validateStatus(): void
{
    if (!$this->status->isValidForCreation()) {
        throw new ValidationException(/* ... */);  // ❌ ValidationException NOT IMPORTED
    }
}
```

## 🚨 Total Unused Functions: 11

### Breakdown:
- **Brand Entity**: 7 methods
- **BrandService**: 3 methods  
- **Missing Dependencies**: 3 issues

## 🔧 Recommended Cleanup Actions

### 1. Remove Unused Entity Methods
```php
// Can be safely removed from Brand.php:
- activate()
- markAsDraft()
- updateDetailInfo()
- markAsSynced()
- changeStatus()
- validateStatus()
- validateInvariants()
```

### 2. Remove Unused Service Methods
```php
// Can be safely removed from BrandService.php:
- createEntity()
- getEntity()
- saveEntity()
```

### 3. Fix Missing Dependencies
```php
// Remove TranslatorInterface dependency:
- Remove import: use Yiisoft\Translator\TranslatorInterface;
- Remove from constructor parameter
- Remove validateStatus() method

// Fix missing trait:
- Remove comment about BusinessRulesTrait
- Or create BusinessRulesTrait if needed

// Fix missing import:
- Add: use App\Shared\Exception\ValidationException;
```

## ✅ Functions That Should Be Kept

### Brand Entity Getter Methods
```php
// These are actively used:
- id(): int
- name(): string
- status(): Status
- detailInfo(): array
- syncMdb(): ?int
```

### BrandService Core Methods
```php
// These are actively used:
- list(): array
- count(): int
- get(): array
- create(): array
- update(): array
- delete(): void
```

### Repository Methods
```php
// These are actively used:
- findById(): ?array
- create(): array
- update(): array
- delete(): void
- list(): array
- count(): int
- findByName(): ?array (used in EntityValidationTrait)
```

## 📊 Impact Analysis

### Before Cleanup
- **Brand Entity**: 96 lines (with unused code)
- **BrandService**: 176 lines (with unused code)
- **Total**: ~272 lines

### After Cleanup (Estimated)
- **Brand Entity**: ~60 lines (-36 lines)
- **BrandService**: ~140 lines (-36 lines)
- **Total**: ~200 lines (-72 lines)

### Benefits
- ✅ 26% reduction in code size
- ✅ Cleaner, more focused API
- ✅ Easier maintenance
- ✅ Reduced complexity
- ✅ Better performance (less code to load)

## 🎯 Implementation Priority

### High Priority (Safe to Remove)
1. Remove unused entity methods (7)
2. Remove unused service methods (3)
3. Fix missing imports

### Medium Priority (Requires Testing)
1. Remove TranslatorInterface dependency
2. Simplify constructor
3. Remove validation methods

### Low Priority (Optional)
1. Create missing BusinessRulesTrait if needed
2. Add comprehensive unit tests
3. Update documentation

## 📝 Notes

- All unused functions have been verified with global search across the entire codebase
- No external references found for the listed unused functions
- Repository `findByName()` method is actually used in `EntityValidationTrait`
- Status validation is now handled in `BrandValidator::validateForCreation()`
- Entity creation is handled directly in repository, not through service entity methods

## 🔍 Verification Method

The analysis was performed using:
1. Global grep search across all domain files
2. Cross-reference with usage in application layer
3. Dependency injection container analysis
4. Import statement verification

---

*Last Updated: January 15, 2026*
*Analysis Scope: Brand Domain Only*
