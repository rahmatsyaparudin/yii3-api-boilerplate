# Data Transfer Objects (Dto) Guide

## 📋 Overview

Data Transfer Objects (DTOs) are simple data structures used to transfer data between processes or layers. In this Yii3 API application, DTOs provide a clean way to structure data for API communication and internal data transfer.

---

## 🏗️ DTO Architecture

### Directory Structure

```
src/Shared/Dto/
├── PaginatedResult.php    # Paginated query results
└── SearchCriteria.php     # Search and filtering criteria
```

### Design Principles

#### **1. **Immutability**
- DTOs are immutable using PHP 8+ readonly properties
- Prevents accidental state changes
- Ensures data integrity

#### **2. **Type Safety**
- Strong typing with PHP 8+ features
- Prevents runtime errors
- Enables IDE auto-completion

#### **3. **Validation**
- Built-in validation in constructors
- Type hints enforce data types
- Optional validation methods

#### **4. **Serialization**
- Easy conversion to/from arrays
- JSON serialization support
- API response formatting

---

## 📁 DTO Components

### 1. PaginatedResult

**Purpose**: Standardized structure for paginated query results

```php
<?php

declare(strict_types=1);

namespace App\Shared\Dto;

/**
 * Data Transfer Object for paginated results
 */
final readonly class PaginatedResult
{
    /**
     * @param array<array<string, mixed>> $data Array of data items
     * @param int $total Total number of items
     * @param int $page Current page number
     * @param int $pageSize Number of items per page
     * @param int $totalPages Total number of pages
     */
    public function __construct(
        public readonly array $data,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageSize,
        public readonly int $totalPages
    ) {
    }

    /**
     * Create from array data
     */
    public static function fromArray(
        array $data,
        int $total,
        int $page,
        int $pageSize
    ): self {
        $totalPages = (int) ceil($total / $pageSize);

        return new self(
            data: $data,
            total: $total,
            page: $page,
            pageSize: $pageSize,
            totalPages: $totalPages
        );
    }

    /**
     * Create empty result
     */
    public static function empty(int $page = 1, int $pageSize = 20): self
    {
        return new self(
            data: [],
            total: 0,
            page: $page,
            pageSize: $pageSize,
            totalPages: 0
        );
    }

    /**
     * Check if has data
     */
    public function hasData(): bool
    {
        return !empty($this->data);
    }

    /**
     * Check if has next page
     */
    public function hasNextPage(): bool
    {
        return $this->page < $this->totalPages;
    }

    /**
     * Check if has previous page
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * Get next page number
     */
    public function getNextPage(): ?int
    {
        return $this->hasNextPage() ? $this->page + 1 : null;
    }

    /**
     * Get previous page number
     */
    public function getPreviousPage(): ?int
    {
        return $this->hasPreviousPage() ? $this->page - 1 : null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'total' => $this->total,
            'page' => $this->page,
            'pageSize' => $this->pageSize,
            'totalPages' => $this->totalPages,
            'hasNextPage' => $this->hasNextPage(),
            'hasPreviousPage' => $this->hasPreviousPage(),
            'nextPage' => $this->getNextPage(),
            'previousPage' => $this->getPreviousPage(),
        ];
    }
}
```

**Usage Example**:
```php
// In repository
public function findAll(SearchCriteria $criteria): PaginatedResult
{
    $query = $this->buildQuery($criteria);
    $total = $this->countTotal($criteria);
    
    $data = $this->executeQuery($query)
        ->offset($criteria->getOffset())
        ->limit($criteria->getLimit())
        ->fetchAll();
    
    return PaginatedResult::fromArray(
        $data,
        $total,
        $criteria->getPage(),
        $criteria->getLimit()
    );
}

// In controller
public function actionIndex(SearchCriteria $criteria): array
{
    $results = $this->service->list($criteria);
    return $results->toArray();
}
```

---

### 2. SearchCriteria

**Purpose**: Standardized structure for search, filtering, and pagination parameters

```php
<?php

declare(strict_types=1);

namespace App\Shared\Dto;

/**
 * Data Transfer Object for search criteria
 */
final readonly class SearchCriteria
{
    /**
     * @param array<string, mixed> $filters Filter criteria
     * @param array<string, string> $sort Sorting criteria
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     */
    public function __construct(
        public readonly array $filters = [],
        public readonly array $sort = [],
        public readonly int $page = 1,
        public readonly int $limit = 20
    ) {
    }

    /**
     * Create from request parameters
     */
    public static function fromArray(array $params): self
    {
        $filters = $params['filters'] ?? [];
        $sort = $params['sort'] ?? [];
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? 20);

        // Validate page number
        if ($page < 1) {
            $page = 1;
        }

        // Validate limit
        if ($limit < 1 || $limit > 100) {
            $limit = 20;
        }

        return new self(
            filters: $filters,
            sort: $sort,
            page: $page,
            limit: $limit
        );
    }

    /**
     * Create from HTTP request
     */
    public static function fromRequest(array $queryParams): self
    {
        return self::fromArray($queryParams);
    }

    /**
     * Get offset for database query
     */
    public function getOffset(): int
    {
        return ($this->page - 1) * $this->limit;
    }

    /**
     * Add filter
     */
    public function withFilter(string $key, mixed $value): self
    {
        $filters = $this->filters;
        $filters[$key] = $value;

        return new self(
            filters: $filters,
            sort: $this->sort,
            page: $this->page,
            limit: $this->limit
        );
    }

    /**
     * Add sorting
     */
    public function withSort(string $field, string $direction): self
    {
        $sort = $this->sort;
        $sort[$field] = $direction;

        return new self(
            filters: $filters,
            sort: $sort,
            page: $this->page,
            limit: $this->limit
        );
    }

    /**
     * Set page
     */
    public function withPage(int $page): self
    {
        return new self(
            filters: $filters,
            sort: $sort,
            page: max(1, $page),
            limit: $this->limit
        );
    }

    /**
     * Set limit
     */
    public function withLimit(int $limit): self
    {
        return new self(
            filters: $filters,
            sort: $sort,
            page: $this->page,
            limit: max(1, min($limit, 100))
        );
    }

    /**
     * Check if has filters
     */
    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    /**
     * Check if has sorting
     */
    public function hasSort(): bool
    {
        return !empty($this->sort);
    }

    /**
     * Get filter value
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * Get sort direction
     */
    public function getSort(string $field, string $default = 'asc'): string
    {
        return $this->sort[$field] ?? $default;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'filters' => $this->filters,
            'sort' => $this->sort,
            'page' => $this->page,
            'limit' => $this->limit,
            'offset' => $this->getOffset(),
        ];
    }
}
```

**Usage Example**:
```php
// In controller
public function actionIndex(): array
{
    $criteria = SearchCriteria::fromRequest($this->request->getQueryParams());
    
    // Add filters dynamically
    if ($this->request->getQueryParam('status')) {
        $criteria = $criteria->withFilter('status', $this->request->getQueryParam('status'));
    }
    
    // Add sorting
    $criteria = $criteria->withSort('created_at', 'desc');
    
    // Set pagination
    $criteria = $criteria->withPage(2)->withLimit(50);
    
    $results = $this->service->list($criteria);
    return $results->toArray();
}

// In repository
public function findAll(SearchCriteria $criteria): PaginatedResult
{
    $query = $this->createQueryBuilder();
    
    // Apply filters
    foreach ($criteria->filters as $field => $value) {
        $query->where($field, $value);
    }
    
    // Apply sorting
    foreach ($criteria->sort as $field => $direction) {
        $query->orderBy($field, $direction);
    }
    
    // Get total count
    $total = $query->count();
    
    // Get paginated results
    $data = $query
        ->offset($criteria->getOffset())
        ->limit($criteria->limit)
        ->fetchAll();
    
    return PaginatedResult::fromArray(
        $data,
        $total,
        $criteria->page,
        $criteria->limit
    );
}
```

---

## 🔧 Integration Patterns

### 1. **Controller Usage**
```php
final class ExampleController
{
    public function __construct(
        private ExampleApplicationService $service
    ) {}
    
    public function actionIndex(): array
    {
        $criteria = SearchCriteria::fromRequest($this->request->getQueryParams());
        
        // Apply business logic filters
        if ($this->getUser()->hasRole('admin')) {
            $criteria = $criteria->withFilter('include_deleted', true);
        }
        
        $results = $this->service->list($criteria);
        return $results->toArray();
    }
}
```

### 2. **Service Usage**
```php
final class ExampleApplicationService
{
    public function __construct(
        private ExampleRepositoryInterface $repository
    ) {}
    
    public function list(SearchCriteria $criteria): PaginatedResult
    {
        // Apply business rules
        $criteria = $this->applyBusinessRules($criteria);
        
        return $this->repository->findAll($criteria);
    }
    
    private function applyBusinessRules(SearchCriteria $criteria): SearchCriteria
    {
        // Example: Only show active records for regular users
        if (!$this->currentUser->isAdmin()) {
            $criteria = $criteria->withFilter('status', 'active');
        }
        
        return $criteria;
    }
}
```

### 3. **Repository Usage**
```php
final class ExampleRepository
{
    public function __construct(
        private ConnectionInterface $db
    ) {}
    
    public function findAll(SearchCriteria $criteria): PaginatedResult
    {
        $query = $this->db->createQueryBuilder()
            ->select('*')
            ->from('example');
        
        // Apply filters
        foreach ($criteria->filters as $field => $value) {
            if (is_array($value)) {
                $query->andWhere([$field => $value]);
            } else {
                $query->andWhere([$field => $value]);
            }
        }
        
        // Apply sorting
        foreach ($criteria->sort as $field => $direction) {
            $query->addOrderBy([$field => $direction]);
        }
        
        // Get total count
        $totalQuery = clone $query;
        $total = $totalQuery->count();
        
        // Get paginated results
        $data = $query
            ->offset($criteria->getOffset())
            ->limit($criteria->limit)
            ->fetchAll();
        
        return PaginatedResult::fromArray(
            $data,
            $total,
            $criteria->page,
            $criteria->limit
        );
    }
}
```

---

## 🚀 Best Practices

### 1. **Immutability**
```php
// ✅ Use readonly properties
final readonly class CreateUserRequest
{
    public function __construct(
        public readonly string $name,
        public readonly string $email
    ) {}
}

// ❌ Avoid mutable properties
class CreateUserRequest
{
    public string $name;
    public string $email;
}
```

### 2. **Validation**
```php
// ✅ Validate in constructor
final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Invalid email address');
        }
    }
}

// ❌ Avoid external validation
$email = new Email($input);
if (!$email->isValid()) {
    // Validation should be in constructor
}
```

### 3. **Type Safety**
```php
// ✅ Use specific types
final readonly class CreateUserRequest
{
    public function __construct(
        public readonly string $name,
        public readonly int $age,
        public readonly bool $active
    ) {}
}

// ❌ Avoid generic types
final readonly class CreateUserRequest
{
    public function __construct(
        public readonly $name,
        public readonly $age,
        public readonly $active
    ) {}
}
```

### 4. **Serialization**
```php
// ✅ Provide toArray method
public function toArray(): array
{
    return [
        'name' => $this->name,
        'email' => $this->email,
    ];
}

// ❌ Avoid direct property access
echo json_encode($dto);
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- DTOs are lightweight and memory-efficient
- Use readonly properties to prevent copying
- Avoid unnecessary object creation

### 2. **Serialization**
- Implement efficient toArray() methods
- Use JSON serialization for API responses
- Cache serialized data when appropriate

### 3. **Validation**
- Validate in constructor to fail fast
- Use built-in PHP functions for validation
- Avoid complex validation logic in DTOs

---

## 🎯 Summary

DTOs provide a clean, type-safe way to transfer data between layers in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: Strong typing prevents runtime errors
- **🔄 Immutability**: Readonly properties ensure data integrity
- **🧪 Testability**: Easy to unit test with predictable behavior
- **📦 Modularity**: Each DTO has a single responsibility
- **🚀 Performance**: Lightweight and efficient data transfer

By following the patterns and best practices outlined in this guide, you can build robust, maintainable DTOs for your Yii3 API application! 🚀
