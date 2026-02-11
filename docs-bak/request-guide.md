# Request Processing Guide

## 📋 Overview

Request processing utilities provide a structured way to handle HTTP request data, including parameter parsing, pagination, sorting, and data validation. These components ensure consistent request handling across the application.

---

## 🏗️ Request Architecture

### Directory Structure

```
src/Shared/Request/
├── DataParserInterface.php    # Interface for data parsing
├── PaginationParams.php       # Pagination parameter handling
├── RawParams.php             # Raw request parameter processing
├── RequestDataParser.php     # Request data parsing implementation
├── RequestParams.php         # Complete request parameter handling
└── SortParams.php           # Sorting parameter handling
```

### Design Principles

#### **1. **Type Safety**
- Strong typing with PHP 8+ features
- Immutable parameter objects
- Validation in constructors

#### **2. **Consistency**
- Standardized parameter handling
- Consistent API across all request types
- Predictable behavior

#### **3. **Flexibility**
- Configurable parameter names
- Extensible validation rules
- Custom parsing logic

#### **4. **Performance**
- Efficient parsing algorithms
- Minimal memory usage
- Lazy evaluation where possible

---

## 📁 Request Components

### 1. RequestParams

**Purpose**: Complete request parameter handling with validation and filtering

```php
<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Exception\BadRequestException;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Request Parameters Handler
 */
final readonly class RequestParams
{
    /**
     * @param array<string, mixed> $params Raw request parameters
     * @param array<string, mixed> $filters Filter parameters
     * @param array<string, string> $sort Sorting parameters
     * @param PaginationParams $pagination Pagination parameters
     */
    public function __construct(
        public readonly array $params,
        public readonly array $filters,
        public readonly array $sort,
        public readonly PaginationParams $pagination
    ) {}

    /**
     * Create from HTTP request
     */
    public static function fromRequest(
        array $queryParams,
        array $bodyParams = [],
        ?TranslatorInterface $translator = null
    ): self {
        $params = array_merge($queryParams, $bodyParams);
        
        // Validate and extract filters
        $filters = self::extractFilters($params, $translator);
        
        // Validate and extract sorting
        $sort = self::extractSort($params, $translator);
        
        // Create pagination parameters
        $pagination = PaginationParams::fromArray($params, $translator);

        return new self(
            params: $params,
            filters: $filters,
            sort: $sort,
            pagination: $pagination
        );
    }

    /**
     * Extract filter parameters
     */
    private static function extractFilters(array $params, ?TranslatorInterface $translator): array
    {
        $filters = [];
        
        // Extract filter parameters (those starting with 'filter_')
        foreach ($params as $key => $value) {
            if (str_starts_with($key, 'filter_')) {
                $filterKey = substr($key, 7); // Remove 'filter_' prefix
                
                // Validate filter key
                if (!self::isValidFilterKey($filterKey)) {
                    throw BadRequestException::invalidParameter(
                        $key,
                        'Invalid filter key'
                    );
                }
                
                $filters[$filterKey] = self::normalizeFilterValue($value);
            }
        }
        
        return $filters;
    }

    /**
     * Extract sorting parameters
     */
    private static function extractSort(array $params, ?TranslatorInterface $translator): array
    {
        $sort = [];
        
        // Extract sort parameter
        $sortParam = $params['sort'] ?? '';
        
        if (!empty($sortParam)) {
            $sortFields = explode(',', $sortParam);
            
            foreach ($sortFields as $field) {
                $parts = explode(':', $field);
                $fieldName = $parts[0] ?? '';
                $direction = $parts[1] ?? 'asc';
                
                // Validate field name
                if (!self::isValidSortField($fieldName)) {
                    throw BadRequestException::invalidParameter(
                        'sort',
                        "Invalid sort field: {$fieldName}"
                    );
                }
                
                // Validate direction
                if (!in_array($direction, ['asc', 'desc'], true)) {
                    throw BadRequestException::invalidParameter(
                        'sort',
                        "Invalid sort direction: {$direction}"
                    );
                }
                
                $sort[$fieldName] = $direction;
            }
        }
        
        return $sort;
    }

    /**
     * Validate filter key
     */
    private static function isValidFilterKey(string $key): bool
    {
        // Allow alphanumeric, underscore, and dot
        return preg_match('/^[a-zA-Z0-9_.]+$/', $key) === 1;
    }

    /**
     * Validate sort field
     */
    private static function isValidSortField(string $field): bool
    {
        // Allow alphanumeric, underscore, and dot
        return preg_match('/^[a-zA-Z0-9_.]+$/', $field) === 1;
    }

    /**
     * Normalize filter value
     */
    private static function normalizeFilterValue(mixed $value): mixed
    {
        if (is_string($value)) {
            // Handle comma-separated values
            if (str_contains($value, ',')) {
                return array_map('trim', explode(',', $value));
            }
            
            // Handle boolean values
            $lower = strtolower($value);
            if (in_array($lower, ['true', '1', 'yes', 'on'], true)) {
                return true;
            }
            if (in_array($lower, ['false', '0', 'no', 'off'], true)) {
                return false;
            }
            
            // Handle empty string as null
            if ($value === '') {
                return null;
            }
        }
        
        return $value;
    }

    /**
     * Get filter value
     */
    public function getFilter(string $key, mixed $default = null): mixed
    {
        return $this->filters[$key] ?? $default;
    }

    /**
     * Check if has filters
     */
    public function hasFilters(): bool
    {
        return !empty($this->filters);
    }

    /**
     * Get sort direction
     */
    public function getSortDirection(string $field, string $default = 'asc'): string
    {
        return $this->sort[$field] ?? $default;
    }

    /**
     * Check if has sorting
     */
    public function hasSort(): bool
    {
        return !empty($this->sort);
    }

    /**
     * Get parameter value
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Check if has parameter
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'params' => $this->params,
            'filters' => $this->filters,
            'sort' => $this->sort,
            'pagination' => $this->pagination->toArray(),
        ];
    }
}
```

**Usage Example**:
```php
// In controller
public function actionIndex(): array
{
    $requestParams = RequestParams::fromRequest(
        $this->request->getQueryParams(),
        $this->request->getParsedBody(),
        $this->translator
    );
    
    // Get filters
    $status = $requestParams->getFilter('status');
    $category = $requestParams->getFilter('category');
    
    // Get sorting
    $sortBy = $requestParams->getSortDirection('created_at', 'desc');
    
    // Get pagination
    $page = $requestParams->pagination->page;
    $limit = $requestParams->pagination->limit;
    
    // Use in service
    $results = $this->service->list($requestParams);
    
    return $results->toArray();
}
```

---

### 2. PaginationParams

**Purpose**: Pagination parameter handling with validation

```php
<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Exception\BadRequestException;
use App\Shared\Enums\AppConstants;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Pagination Parameters
 */
final readonly class PaginationParams
{
    /**
     * @param int $page Page number (1-based)
     * @param int $limit Items per page
     * @param int $offset Database offset
     */
    public function __construct(
        public readonly int $page,
        public readonly int $limit,
        public readonly int $offset
    ) {}

    /**
     * Create from array parameters
     */
    public static function fromArray(
        array $params,
        ?TranslatorInterface $translator = null
    ): self {
        $page = (int) ($params['page'] ?? 1);
        $limit = (int) ($params['limit'] ?? AppConstants::DEFAULT_PAGE_SIZE);
        
        // Validate page number
        if ($page < 1) {
            throw BadRequestException::invalidParameter(
                'page',
                'Page number must be greater than 0'
            );
        }
        
        // Validate limit
        if ($limit < AppConstants::MIN_PAGE_SIZE || $limit > AppConstants::MAX_PAGE_SIZE) {
            throw BadRequestException::invalidParameter(
                'limit',
                "Limit must be between {AppConstants::MIN_PAGE_SIZE} and {AppConstants::MAX_PAGE_SIZE}"
            );
        }
        
        $offset = ($page - 1) * $limit;
        
        return new self(
            page: $page,
            limit: $limit,
            offset: $offset
        );
    }

    /**
     * Create with custom defaults
     */
    public static function create(
        int $page = 1,
        int $limit = AppConstants::DEFAULT_PAGE_SIZE
    ): self {
        return new self(
            page: max(1, $page),
            limit: max(AppConstants::MIN_PAGE_SIZE, min($limit, AppConstants::MAX_PAGE_SIZE)),
            offset: (max(1, $page) - 1) * max(AppConstants::MIN_PAGE_SIZE, min($limit, AppConstants::MAX_PAGE_SIZE))
        );
    }

    /**
     * Get next page parameters
     */
    public function nextPage(): self
    {
        return new self(
            page: $this->page + 1,
            limit: $this->limit,
            offset: $this->offset + $this->limit
        );
    }

    /**
     * Get previous page parameters
     */
    public function previousPage(): self
    {
        if ($this->page <= 1) {
            return $this;
        }
        
        return new self(
            page: $this->page - 1,
            limit: $this->limit,
            offset: $this->offset - $this->limit
        );
    }

    /**
     * Check if has next page
     */
    public function hasNextPage(int $totalItems): bool
    {
        return $this->offset + $this->limit < $totalItems;
    }

    /**
     * Check if has previous page
     */
    public function hasPreviousPage(): bool
    {
        return $this->page > 1;
    }

    /**
     * Get total pages
     */
    public function getTotalPages(int $totalItems): int
    {
        return (int) ceil($totalItems / $this->limit);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'page' => $this->page,
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }
}
```

**Usage Example**:
```php
// In repository
public function findAll(PaginationParams $pagination): array
{
    return $this->db->createQueryBuilder()
        ->select('*')
        ->from('example')
        ->offset($pagination->offset)
        ->limit($pagination->limit)
        ->fetchAll();
}

// In controller
$pagination = PaginationParams::fromArray($request->getQueryParams());
$items = $this->repository->findAll($pagination);
$total = $this->repository->count();

return [
    'data' => $items,
    'pagination' => [
        'page' => $pagination->page,
        'limit' => $pagination->limit,
        'total' => $total,
        'totalPages' => $pagination->getTotalPages($total),
        'hasNextPage' => $pagination->hasNextPage($total),
        'hasPreviousPage' => $pagination->hasPreviousPage(),
    ]
];
```

---

### 3. SortParams

**Purpose**: Sorting parameter handling with validation

```php
<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Exception\BadRequestException;

/**
 * Sorting Parameters
 */
final readonly class SortParams
{
    /**
     * @param array<string, string> $sort Sorting field => direction mapping
     */
    public function __construct(
        public readonly array $sort
    ) {}

    /**
     * Create from string parameter
     */
    public static function fromString(string $sortString): self
    {
        $sort = [];
        
        if (empty($sortString)) {
            return new self($sort);
        }
        
        $fields = explode(',', $sortString);
        
        foreach ($fields as $field) {
            $parts = explode(':', $field);
            $fieldName = $parts[0] ?? '';
            $direction = $parts[1] ?? 'asc';
            
            // Validate field name
            if (empty($fieldName)) {
                throw BadRequestException::invalidParameter(
                    'sort',
                    'Sort field cannot be empty'
                );
            }
            
            // Validate field name format
            if (!preg_match('/^[a-zA-Z][a-zA-Z0-9_]*$/', $fieldName)) {
                throw BadRequestException::invalidParameter(
                    'sort',
                    "Invalid sort field format: {$fieldName}"
                );
            }
            
            // Validate direction
            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw BadRequestException::invalidParameter(
                    'sort',
                    "Invalid sort direction: {$direction}. Use 'asc' or 'desc'"
                );
            }
            
            $sort[$fieldName] = $direction;
        }
        
        return new self($sort);
    }

    /**
     * Create from array
     */
    public static function fromArray(array $sortArray): self
    {
        $validatedSort = [];
        
        foreach ($sortArray as $field => $direction) {
            if (!is_string($field) || !is_string($direction)) {
                throw BadRequestException::invalidParameter(
                    'sort',
                    'Sort field and direction must be strings'
                );
            }
            
            if (!in_array($direction, ['asc', 'desc'], true)) {
                throw BadRequestException::invalidParameter(
                    'sort',
                    "Invalid sort direction: {$direction}"
                );
            }
            
            $validatedSort[$field] = $direction;
        }
        
        return new self($validatedSort);
    }

    /**
     * Get sort direction for field
     */
    public function getDirection(string $field, string $default = 'asc'): string
    {
        return $this->sort[$field] ?? $default;
    }

    /**
     * Check if has sorting for field
     */
    public function hasField(string $field): bool
    {
        return array_key_exists($field, $this->sort);
    }

    /**
     * Check if has any sorting
     */
    public function hasSorting(): bool
    {
        return !empty($this->sort);
    }

    /**
     * Get all fields
     */
    public function getFields(): array
    {
        return array_keys($this->sort);
    }

    /**
     * Add field
     */
    public function withField(string $field, string $direction = 'asc'): self
    {
        if (!in_array($direction, ['asc', 'desc'], true)) {
            throw new BadRequestException::invalidParameter(
                'sort',
                "Invalid sort direction: {$direction}"
            );
        }
        
        $newSort = $this->sort;
        $newSort[$field] = $direction;
        
        return new self($newSort);
    }

    /**
     * Remove field
     */
    public function withoutField(string $field): self
    {
        $newSort = $this->sort;
        unset($newSort[$field]);
        
        return new self($newSort);
    }

    /**
     * Convert to string
     */
    public function toString(): string
    {
        $parts = [];
        
        foreach ($this->sort as $field => $direction) {
            $parts[] = "{$field}:{$direction}";
        }
        
        return implode(',', $parts);
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return $this->sort;
    }

    /**
     * Convert to database order by array
     */
    public function toOrderBy(): array
    {
        $orderBy = [];
        
        foreach ($this->sort as $field => $direction) {
            $orderBy[$field] = strtoupper($direction);
        }
        
        return $orderBy;
    }
}
```

**Usage Example**:
```php
// In controller
$sortString = $request->getQueryParam('sort', 'created_at:desc');
$sortParams = SortParams::fromString($sortString);

// In repository
public function findAll(SortParams $sort): array
{
    $query = $this->db->createQueryBuilder()
        ->select('*')
        ->from('example');
    
    // Apply sorting
    foreach ($sort->toOrderBy() as $field => $direction) {
        $query->addOrderBy([$field => $direction]);
    }
    
    return $query->fetchAll();
}

// Dynamic sorting
$sort = SortParams::fromString('name:asc,created_at:desc')
    ->withField('status', 'desc')
    ->withoutField('name');
```

---

### 4. RawParams

**Purpose**: Raw request parameter processing with advanced filtering

```php
<?php

declare(strict_types=1);

namespace App\Shared\Request;

use App\Shared\Exception\BadRequestException;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Raw Request Parameters Handler
 */
final readonly class RawParams
{
    /**
     * @param array<string, mixed> $rawParams Raw request parameters
     * @param array<string, mixed> $processedParams Processed parameters
     * @param array<string, string> $errors Validation errors
     */
    public function __construct(
        public readonly array $rawParams,
        public readonly array $processedParams,
        public readonly array $errors
    ) {}

    /**
     * Create from request data
     */
    public static function fromRequestData(
        array $queryParams,
        array $bodyParams = [],
        ?TranslatorInterface $translator = null
    ): self {
        $rawParams = array_merge($queryParams, $bodyParams);
        $processedParams = [];
        $errors = [];
        
        foreach ($rawParams as $key => $value) {
            try {
                $processedParams[$key] = self::processParameter($key, $value);
            } catch (BadRequestException $e) {
                $errors[$key] = $e->getMessage();
            }
        }
        
        return new self(
            rawParams: $rawParams,
            processedParams: $processedParams,
            errors: $errors
        );
    }

    /**
     * Process individual parameter
     */
    private static function processParameter(string $key, mixed $value): mixed
    {
        // Skip empty values
        if ($value === null || $value === '') {
            return null;
        }
        
        // Process based on parameter name patterns
        if (str_starts_with($key, 'date_')) {
            return self::processDateParameter($value);
        }
        
        if (str_starts_with($key, 'number_')) {
            return self::processNumberParameter($value);
        }
        
        if (str_starts_with($key, 'bool_')) {
            return self::processBooleanParameter($value);
        }
        
        if (str_starts_with($key, 'array_')) {
            return self::processArrayParameter($value);
        }
        
        // Default processing
        return self::processDefaultParameter($value);
    }

    /**
     * Process date parameter
     */
    private static function processDateParameter(mixed $value): ?string
    {
        if (is_string($value)) {
            // Validate date format
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                return $value;
            }
            
            // Try to parse datetime
            $dateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $value);
            if ($dateTime) {
                return $dateTime->format('Y-m-d H:i:s');
            }
        }
        
        throw BadRequestException::invalidParameter(
            'date parameter',
            'Invalid date format. Use YYYY-MM-DD or YYYY-MM-DD HH:MM:SS'
        );
    }

    /**
     * Process number parameter
     */
    private static function processNumberParameter(mixed $value): int|float
    {
        if (is_numeric($value)) {
            return str_contains($value, '.') ? (float) $value : (int) $value;
        }
        
        throw BadRequestException::invalidParameter(
            'number parameter',
            'Invalid number format'
        );
    }

    /**
     * Process boolean parameter
     */
    private static function processBooleanParameter(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', '1', 'yes', 'on'], true);
        }
        
        if (is_int($value)) {
            return $value > 0;
        }
        
        throw BadRequestException::invalidParameter(
            'boolean parameter',
            'Invalid boolean value. Use true, false, 1, 0, yes, no, on, off'
        );
    }

    /**
     * Process array parameter
     */
    private static function processArrayParameter(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            // Handle comma-separated values
            if (str_contains($value, ',')) {
                return array_map('trim', explode(',', $value));
            }
            
            // Handle JSON array
            if (str_starts_with($value, '[') && str_ends_with($value, ']')) {
                $decoded = json_decode($value, true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            }
        }
        
        throw BadRequestException::invalidParameter(
            'array parameter',
            'Invalid array format. Use comma-separated values or JSON array'
        );
    }

    /**
     * Process default parameter
     */
    private static function processDefaultParameter(mixed $value): mixed
    {
        if (is_string($value)) {
            // Trim whitespace
            $value = trim($value);
            
            // Convert empty string to null
            if ($value === '') {
                return null;
            }
        }
        
        return $value;
    }

    /**
     * Check if has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get error for parameter
     */
    public function getError(string $key): ?string
    {
        return $this->errors[$key] ?? null;
    }

    /**
     * Get processed parameter
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->processedParams[$key] ?? $default;
    }

    /**
     * Check if has parameter
     */
    public function has(string $key): bool
    {
        return array_key_exists($key, $this->processedParams);
    }

    /**
     * Get all processed parameters
     */
    public function getAll(): array
    {
        return $this->processedParams;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'raw' => $this->rawParams,
            'processed' => $this->processedParams,
            'errors' => $this->errors,
        ];
    }
}
```

**Usage Example**:
```php
// In controller
$rawParams = RawParams::fromRequestData(
    $this->request->getQueryParams(),
    $this->request->getParsedBody(),
    $this->translator
);

if ($rawParams->hasErrors()) {
    throw ValidationException::fromValidationResult($rawParams->getErrors());
}

// Get processed parameters
$startDate = $rawParams->get('date_start');
$minPrice = $rawParams->get('number_min_price');
$isActive = $rawParams->get('bool_active');
$tags = $rawParams->get('array_tags');

// Use in service
$results = $this->service->search($rawParams->getAll());
```

---

### 5. DataParserInterface & RequestDataParser

**Purpose**: Interface and implementation for request data parsing

```php
<?php

declare(strict_types=1);

namespace App\Shared\Request;

/**
 * Data Parser Interface
 */
interface DataParserInterface
{
    /**
     * Parse request data
     */
    public function parse(array $data): array;

    /**
     * Validate parsed data
     */
    public function validate(array $data): array;
}

/**
 * Request Data Parser Implementation
 */
final class RequestDataParser implements DataParserInterface
{
    public function __construct(
        private array $rules = [],
        private array $filters = []
    ) {}

    public function parse(array $data): array
    {
        $parsed = [];
        
        foreach ($data as $key => $value) {
            $parsed[$key] = $this->applyFilters($key, $value);
        }
        
        return $parsed;
    }

    public function validate(array $data): array
    {
        $errors = [];
        
        foreach ($this->rules as $field => $rules) {
            $value = $data[$field] ?? null;
            
            foreach ($rules as $rule => $ruleValue) {
                $error = $this->validateRule($field, $value, $rule, $ruleValue);
                if ($error) {
                    $errors[$field] = $error;
                    break; // Stop at first error for field
                }
            }
        }
        
        return $errors;
    }

    private function applyFilters(string $key, mixed $value): mixed
    {
        $filters = $this->filters[$key] ?? [];
        
        foreach ($filters as $filter) {
            $value = $this->applyFilter($value, $filter);
        }
        
        return $value;
    }

    private function applyFilter(mixed $value, string $filter): mixed
    {
        return match ($filter) {
            'trim' => is_string($value) ? trim($value) : $value,
            'lowercase' => is_string($value) ? strtolower($value) : $value,
            'uppercase' => is_string($value) ? strtoupper($value) : $value,
            'int' => is_numeric($value) ? (int) $value : $value,
            'float' => is_numeric($value) ? (float) $value : $value,
            'bool' => $this->toBool($value),
            'null' => $value === '' ? null : $value,
            default => $value,
        };
    }

    private function toBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', '1', 'yes', 'on'], true);
        }
        
        return (bool) $value;
    }

    private function validateRule(string $field, mixed $value, string $rule, mixed $ruleValue): ?string
    {
        return match ($rule) {
            'required' => $this->validateRequired($value, $ruleValue),
            'min' => $this->validateMin($value, $ruleValue),
            'max' => $this->validateMax($value, $ruleValue),
            'email' => $this->validateEmail($value, $ruleValue),
            'url' => $this->validateUrl($value, $ruleValue),
            'regex' => $this->validateRegex($value, $ruleValue),
            default => null,
        };
    }

    private function validateRequired(mixed $value, bool $required): ?string
    {
        if ($required && ($value === null || $value === '')) {
            return 'This field is required';
        }
        
        return null;
    }

    private function validateMin(mixed $value, int $min): ?string
    {
        if (is_string($value) && strlen($value) < $min) {
            return "Minimum length is {$min} characters";
        }
        
        if (is_numeric($value) && $value < $min) {
            return "Minimum value is {$min}";
        }
        
        return null;
    }

    private function validateMax(mixed $value, int $max): ?string
    {
        if (is_string($value) && strlen($value) > $max) {
            return "Maximum length is {$max} characters";
        }
        
        if (is_numeric($value) && $value > $max) {
            return "Maximum value is {$max}";
        }
        
        return null;
    }

    private function validateEmail(mixed $value, bool $required): ?string
    {
        if ($required && ($value === null || $value === '')) {
            return 'Email is required';
        }
        
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
            return 'Invalid email format';
        }
        
        return null;
    }

    private function validateUrl(mixed $value, bool $required): ?string
    {
        if ($required && ($value === null || $value === '')) {
            return 'URL is required';
        }
        
        if ($value !== null && !filter_var($value, FILTER_VALIDATE_URL)) {
            return 'Invalid URL format';
        }
        
        return null;
    }

    private function validateRegex(mixed $value, string $pattern): ?string
    {
        if ($value !== null && !preg_match($pattern, $value)) {
            return 'Invalid format';
        }
        
        return null;
    }
}
```

**Usage Example**:
```php
// In controller
$parser = new RequestDataParser(
    rules: [
        'name' => ['required' => true, 'min' => 2, 'max' => 255],
        'email' => ['required' => true, 'email' => true],
        'age' => ['min' => 0, 'max' => 120],
    ],
    filters: [
        'name' => ['trim', 'lowercase'],
        'email' => ['trim', 'lowercase'],
        'age' => ['int'],
        'active' => ['bool'],
    ]
);

$data = $parser->parse($request->getParsedBody());
$errors = $parser->validate($data);

if (!empty($errors)) {
    throw ValidationException::fromValidationResult($errors);
}

// Use parsed data
$user = $this->service->create($data);
```

---

## 🔧 Integration Patterns

### 1. **Controller Integration**
```php
final class ExampleController
{
    public function __construct(
        private ExampleApplicationService $service,
        private TranslatorInterface $translator
    ) {}

    public function actionIndex(): array
    {
        $requestParams = RequestParams::fromRequest(
            $this->request->getQueryParams(),
            $this->request->getParsedBody(),
            $this->translator
        );

        $results = $this->service->list($requestParams);
        return $results->toArray();
    }

    public function actionCreate(): array
    {
        $rawParams = RawParams::fromRequestData(
            $this->request->getQueryParams(),
            $this->request->getParsedBody(),
            $this->translator
        );

        if ($rawParams->hasErrors()) {
            throw ValidationException::fromValidationResult($rawParams->getErrors());
        }

        $result = $this->service->create($rawParams->getAll());
        return $result->toArray();
    }
}
```

### 2. **Service Integration**
```php
final class ExampleApplicationService
{
    public function list(RequestParams $params): PaginatedResult
    {
        $criteria = new SearchCriteria(
            filters: $params->filters,
            sort: $params->sort,
            page: $params->pagination->page,
            limit: $params->pagination->limit
        );

        return $this->repository->findAll($criteria);
    }

    public function create(array $data): ExampleResponse
    {
        $command = new CreateExampleCommand(
            name: $data['name'],
            email: $data['email'],
            status: $data['status'] ?? 'active'
        );

        return $this->handleCreate($command);
    }
}
```

### 3. **Repository Integration**
```php
final class ExampleRepository
{
    public function findAll(SearchCriteria $criteria): PaginatedResult
    {
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from('example');

        // Apply filters
        foreach ($criteria->filters as $field => $value) {
            $query->andWhere([$field => $value]);
        }

        // Apply sorting
        foreach ($criteria->sort as $field => $direction) {
            $query->addOrderBy([$field => $direction]);
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
}
```

---

## 🚀 Best Practices

### 1. **Parameter Validation**
```php
// ✅ Validate in constructor
public function __construct(
    public readonly int $page,
    public readonly int $limit
) {
    if ($page < 1) {
        throw new BadRequestException('Page must be greater than 0');
    }
}

// ❌ Validate later
$page = $params['page'];
if ($page < 1) {
    throw new BadRequestException('Page must be greater than 0');
}
```

### 2. **Type Safety**
```php
// ✅ Use readonly properties
final readonly class RequestParams
{
    public function __construct(
        public readonly array $filters,
        public readonly array $sort
    ) {}
}

// ❌ Use mutable properties
class RequestParams
{
    public array $filters;
    public array $sort;
}
```

### 3. **Error Handling**
```php
// ✅ Provide specific error messages
throw BadRequestException::invalidParameter(
    'page',
    'Page number must be greater than 0'
);

// ❌ Use generic messages
throw new BadRequestException('Invalid parameter');
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- Use readonly properties to prevent copying
- Avoid unnecessary object creation
- Process parameters only when needed

### 2. **Validation Overhead**
- Validate only required parameters
- Use efficient validation methods
- Cache validation rules when possible

### 3. **Parsing Performance**
- Use built-in PHP functions
- Avoid complex regex patterns
- Process parameters in single pass

---

## 🎯 Summary

Request processing utilities provide a structured, type-safe way to handle HTTP request data in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: Strong typing prevents runtime errors
- **✅ Validation**: Built-in validation and error handling
- **🔄 Consistency**: Standardized parameter handling
- **🧪 Testability**: Easy to unit test individual components
- **📦 Modularity**: Each component has a single responsibility
- **🚀 Performance**: Efficient parsing and validation

By following the patterns and best practices outlined in this guide, you can build robust, maintainable request processing for your Yii3 API application! 🚀
