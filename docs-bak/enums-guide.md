# Enums Guide

## 📋 Overview

Enumerations (Enums) provide a way to define a set of named constants that represent a fixed set of values. In this Yii3 API application, enums are used to define status values, constants, and other fixed data sets that are used across different layers.

---

## 🏗️ Enum Architecture

### Directory Structure

```
src/Shared/Enums/
├── AppConstants.php    # Application-wide constants
└── RecordStatus.php     # Record status enumeration
```

### Design Principles

#### **1. **Type Safety**
- PHP 8.1+ enum features for strong typing
- Prevents invalid values
- Enables IDE auto-completion

#### **2. **Immutability**
- Enums are immutable by nature
- Prevents accidental state changes
- Ensures consistency

#### **3. **Self-Documentation**
- Named constants are self-explanatory
- Reduces magic numbers and strings
- Improves code readability

#### **4. **Extensibility**
- Easy to add new values
- Backward compatible changes
- Centralized management

---

## 📁 Enum Components

### 1. AppConstants

**Purpose**: Application-wide constants and configuration values

```php
<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Application-wide constants
 */
final class AppConstants
{
    // API Configuration
    public const string API_VERSION = 'v1';
    public const string API_PREFIX = '/api';
    public const int API_DEFAULT_PAGE_SIZE = 20;
    public const int API_MAX_PAGE_SIZE = 100;
    public const int API_MIN_PAGE_SIZE = 1;
    
    // Cache Configuration
    public const string CACHE_PREFIX = 'yii3_api_';
    public const int CACHE_DEFAULT_TTL = 3600; // 1 hour
    public const int CACHE_LONG_TTL = 86400;   // 24 hours
    public const int CACHE_SHORT_TTL = 300;   // 5 minutes
    
    // Security Configuration
    public const int PASSWORD_MIN_LENGTH = 8;
    public const int PASSWORD_MAX_LENGTH = 128;
    public const int TOKEN_EXPIRY_TIME = 3600; // 1 hour
    public const int REFRESH_TOKEN_EXPIRY = 86400; // 24 hours
    
    // File Upload Configuration
    public const int MAX_FILE_SIZE = 10485760; // 10MB
    public const array ALLOWED_FILE_TYPES = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    public const string UPLOAD_PATH = 'uploads/';
    
    // Rate Limiting
    public const int RATE_LIMIT_REQUESTS = 100;
    public const int RATE_LIMIT_WINDOW = 3600; // 1 hour
    public const int RATE_LIMIT_BURST = 10;
    
    // Pagination
    public const int DEFAULT_PAGE_SIZE = 20;
    public const int MAX_PAGE_SIZE = 100;
    public const int MIN_PAGE_SIZE = 1;
    
    // Date Formats
    public const string DATE_FORMAT = 'Y-m-d';
    public const string DATETIME_FORMAT = 'Y-m-d H:i:s';
    public const string TIME_FORMAT = 'H:i:s';
    public const string ISO_DATETIME_FORMAT = 'c';
    
    // Validation Rules
    public const int MAX_STRING_LENGTH = 255;
    public const int MAX_TEXT_LENGTH = 65535;
    public const int MAX_URL_LENGTH = 2048;
    
    // Database Configuration
    public const string DB_CONNECTION_TIMEOUT = '30';
    public const int DB_MAX_CONNECTIONS = 100;
    public const int DB_QUERY_TIMEOUT = 30;
    
    // Logging Configuration
    public const string LOG_CHANNEL = 'api';
    public const string LOG_LEVEL = 'info';
    public const int LOG_MAX_FILES = 30;
    
    // Email Configuration
    public const string EMAIL_FROM_ADDRESS = 'noreply@example.com';
    public const string EMAIL_FROM_NAME = 'Yii3 API';
    public const int EMAIL_QUEUE_LIMIT = 100;
    
    // Session Configuration
    public const int SESSION_TIMEOUT = 3600; // 1 hour
    public const string SESSION_COOKIE_NAME = 'yii3_session';
    public const bool SESSION_SECURE = true;
    
    // API Response Configuration
    public const int RESPONSE_SUCCESS_CODE = 200;
    public const int RESPONSE_CREATED_CODE = 201;
    public const int RESPONSE_NO_CONTENT_CODE = 204;
    public const int RESPONSE_BAD_REQUEST_CODE = 400;
    public const int RESPONSE_UNAUTHORIZED_CODE = 401;
    public const int RESPONSE_FORBIDDEN_CODE = 403;
    public const int RESPONSE_NOT_FOUND_CODE = 404;
    public const int RESPONSE_CONFLICT_CODE = 409;
    public const int RESPONSE_UNPROCESSABLE_ENTITY_CODE = 422;
    public const int RESPONSE_TOO_MANY_REQUESTS_CODE = 429;
    public const int RESPONSE_INTERNAL_ERROR_CODE = 500;
    
    // Feature Flags
    public const bool FEATURE_REGISTRATION_ENABLED = true;
    public const bool FEATURE_EMAIL_VERIFICATION_REQUIRED = true;
    public const bool FEATURE_PASSWORD_RESET_ENABLED = true;
    public const bool FEATURE_SOCIAL_LOGIN_ENABLED = false;
    
    // Business Rules
    public const int MAX_LOGIN_ATTEMPTS = 5;
    public const int LOGIN_LOCKOUT_DURATION = 900; // 15 minutes
    public const int PASSWORD_EXPIRY_DAYS = 90;
    public const int INACTIVE_ACCOUNT_DAYS = 365;
    
    // External Services
    public const string EXTERNAL_API_TIMEOUT = '30';
    public const int EXTERNAL_API_RETRIES = 3;
    public const int EXTERNAL_API_RETRY_DELAY = 1000; // milliseconds
    
    // Monitoring and Analytics
    public const string METRICS_PREFIX = 'yii3_api_';
    public const int METRICS_SAMPLE_RATE = 100; // percentage
    public const bool PERFORMANCE_MONITORING_ENABLED = true;
    
    // Development Configuration
    public const bool DEBUG_MODE_ENABLED = false;
    public const bool PROFILING_ENABLED = false;
    public const bool QUERY_LOGGING_ENABLED = false;
    
    /**
     * Get all API configuration constants
     */
    public static function getApiConfig(): array
    {
        return [
            'version' => self::API_VERSION,
            'prefix' => self::API_PREFIX,
            'default_page_size' => self::API_DEFAULT_PAGE_SIZE,
            'max_page_size' => self::API_MAX_PAGE_SIZE,
            'min_page_size' => self::API_MIN_PAGE_SIZE,
        ];
    }
    
    /**
     * Get all cache configuration constants
     */
    public static function getCacheConfig(): array
    {
        return [
            'prefix' => self::CACHE_PREFIX,
            'default_ttl' => self::CACHE_DEFAULT_TTL,
            'long_ttl' => self::CACHE_LONG_TTL,
            'short_ttl' => self::CACHE_SHORT_TTL,
        ];
    }
    
    /**
     * Get all security configuration constants
     */
    public static function getSecurityConfig(): array
    {
        return [
            'password_min_length' => self::PASSWORD_MIN_LENGTH,
            'password_max_length' => self::PASSWORD_MAX_LENGTH,
            'token_expiry_time' => self::TOKEN_EXPIRY_TIME,
            'refresh_token_expiry' => self::REFRESH_TOKEN_EXPIRY,
        ];
    }
    
    /**
     * Get all file upload configuration constants
     */
    public static function getFileConfig(): array
    {
        return [
            'max_file_size' => self::MAX_FILE_SIZE,
            'allowed_file_types' => self::ALLOWED_FILE_TYPES,
            'upload_path' => self::UPLOAD_PATH,
        ];
    }
    
    /**
     * Get all rate limiting configuration constants
     */
    public static function getRateLimitConfig(): array
    {
        return [
            'requests' => self::RATE_LIMIT_REQUESTS,
            'window' => self::RATE_LIMIT_WINDOW,
            'burst' => self::RATE_LIMIT_BURST,
        ];
    }
    
    /**
     * Get all pagination configuration constants
     */
    public static function getPaginationConfig(): array
    {
        return [
            'default_page_size' => self::DEFAULT_PAGE_SIZE,
            'max_page_size' => self::MAX_PAGE_SIZE,
            'min_page_size' => self::MIN_PAGE_SIZE,
        ];
    }
    
    /**
     * Get all date format constants
     */
    public static function getDateFormats(): array
    {
        return [
            'date' => self::DATE_FORMAT,
            'datetime' => self::DATETIME_FORMAT,
            'time' => self::TIME_FORMAT,
            'iso_datetime' => self::ISO_DATETIME_FORMAT,
        ];
    }
    
    /**
     * Get all validation rule constants
     */
    public static function getValidationRules(): array
    {
        return [
            'max_string_length' => self::MAX_STRING_LENGTH,
            'max_text_length' => self::MAX_TEXT_LENGTH,
            'max_url_length' => self::MAX_URL_LENGTH,
        ];
    }
    
    /**
     * Get all response code constants
     */
    public static function getResponseCodes(): array
    {
        return [
            'success' => self::RESPONSE_SUCCESS_CODE,
            'created' => self::RESPONSE_CREATED_CODE,
            'no_content' => self::RESPONSE_NO_CONTENT_CODE,
            'bad_request' => self::RESPONSE_BAD_REQUEST_CODE,
            'unauthorized' => self::RESPONSE_UNAUTHORIZED_CODE,
            'forbidden' => self::RESPONSE_FORBIDDEN_CODE,
            'not_found' => self::RESPONSE_NOT_FOUND_CODE,
            'conflict' => self::RESPONSE_CONFLICT_CODE,
            'unprocessable_entity' => self::RESPONSE_UNPROCESSABLE_ENTITY_CODE,
            'too_many_requests' => self::RESPONSE_TOO_MANY_REQUESTS_CODE,
            'internal_error' => self::RESPONSE_INTERNAL_ERROR_CODE,
        ];
    }
    
    /**
     * Get all feature flag constants
     */
    public static function getFeatureFlags(): array
    {
        return [
            'registration_enabled' => self::FEATURE_REGISTRATION_ENABLED,
            'email_verification_required' => self::FEATURE_EMAIL_VERIFICATION_REQUIRED,
            'password_reset_enabled' => self::FEATURE_PASSWORD_RESET_ENABLED,
            'social_login_enabled' => self::FEATURE_SOCIAL_LOGIN_ENABLED,
        ];
    }
    
    /**
     * Get all business rule constants
     */
    public static function getBusinessRules(): array
    {
        return [
            'max_login_attempts' => self::MAX_LOGIN_ATTEMPTS,
            'login_lockout_duration' => self::LOGIN_LOCKOUT_DURATION,
            'password_expiry_days' => self::PASSWORD_EXPIRY_DAYS,
            'inactive_account_days' => self::INACTIVE_ACCOUNT_DAYS,
        ];
    }
}
```

**Usage Example**:
```php
// Using constants directly
$pageSize = AppConstants::API_DEFAULT_PAGE_SIZE;
$maxFileSize = AppConstants::MAX_FILE_SIZE;

// Using configuration methods
$apiConfig = AppConstants::getApiConfig();
$cacheConfig = AppConstants::getCacheConfig();

// In validation
if (strlen($password) < AppConstants::PASSWORD_MIN_LENGTH) {
    throw new ValidationException('Password too short');
}

// In API responses
return $this->responseFactory->success(
    data: $data,
    httpCode: AppConstants::RESPONSE_CREATED_CODE
);
```

---

### 2. RecordStatus

**Purpose**: Enumeration for record status values with built-in validation and methods

```php
<?php

declare(strict_types=1);

namespace App\Shared\Enums;

/**
 * Record status enumeration
 */
enum RecordStatus: string
{
    case ACTIVE = 'active';
    case INACTIVE = 'inactive';
    case DELETED = 'deleted';
    case ARCHIVED = 'archived';
    case PENDING = 'pending';
    case SUSPENDED = 'suspended';
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case EXPIRED = 'expired';
    case BLOCKED = 'blocked';
    
    /**
     * Get all active statuses (not deleted or blocked)
     */
    public static function getActiveStatuses(): array
    {
        return [
            self::ACTIVE->value,
            self::INACTIVE->value,
            self::PENDING->value,
            self::SUSPENDED->value,
            self::DRAFT->value,
            self::PUBLISHED->value,
            self::EXPIRED->value,
        ];
    }
    
    /**
     * Get all visible statuses (can be shown in UI)
     */
    public static function getVisibleStatuses(): array
    {
        return [
            self::ACTIVE->value,
            self::INACTIVE->value,
            self::PENDING->value,
            self::DRAFT->value,
            self::PUBLISHED->value,
            self::EXPIRED->value,
        ];
    }
    
    /**
     * Get all inactive statuses
     */
    public static function getInactiveStatuses(): array
    {
        return [
            self::INACTIVE->value,
            self::DELETED->value,
            self::ARCHIVED->value,
            self::SUSPENDED->value,
            self::BLOCKED->value,
            self::EXPIRED->value,
        ];
    }
    
    /**
     * Get all system statuses (not user-facing)
     */
    public static function getSystemStatuses(): array
    {
        return [
            self::DELETED->value,
            self::ARCHIVED->value,
            self::BLOCKED->value,
        ];
    }
    
    /**
     * Check if status is active
     */
    public function isActive(): bool
    {
        return $this === self::ACTIVE;
    }
    
    /**
     * Check if status is inactive
     */
    public function isInactive(): bool
    {
        return $this === self::INACTIVE;
    }
    
    /**
     * Check if status is deleted
     */
    public function isDeleted(): bool
    {
        return $this === self::DELETED;
    }
    
    /**
     * Check if status is archived
     */
    public function isArchived(): bool
    {
        return $this === self::ARCHIVED;
    }
    
    /**
     * Check if status is pending
     */
    public function isPending(): bool
    {
        return $this === self::PENDING;
    }
    
    /**
     * Check if status is suspended
     */
    public function isSuspended(): bool
    {
        return $this === self::SUSPENDED;
    }
    
    /**
     * Check if status is draft
     */
    public function isDraft(): bool
    {
        return $this === self::DRAFT;
    }
    
    /**
     * Check if status is published
     */
    public function isPublished(): bool
    {
        return $this === self::PUBLISHED;
    }
    
    /**
     * Check if status is expired
     */
    public function isExpired(): bool
    {
        return $this === self::EXPIRED;
    }
    
    /**
     * Check if status is blocked
     */
    public function isBlocked(): bool
    {
        return $this === self::BLOCKED;
    }
    
    /**
     * Check if status allows modification
     */
    public function allowsModification(): bool
    {
        return match($this) {
            self::ACTIVE,
            self::INACTIVE,
            self::PENDING,
            self::DRAFT => true,
            self::DELETED,
            self::ARCHIVED,
            self::SUSPENDED,
            self::BLOCKED,
            self::PUBLISHED,
            self::EXPIRED => false,
        };
    }
    
    /**
     * Check if status is visible in UI
     */
    public function isVisible(): bool
    {
        return !in_array($this->value, self::getSystemStatuses(), true);
    }
    
    /**
     * Check if status can be transitioned to another status
     */
    public function canTransitionTo(self $newStatus): bool
    {
        return match([$this, $newStatus]) {
            [self::DRAFT, self::PUBLISHED] => true,
            [self::PUBLISHED, self::DRAFT] => true,
            [self::ACTIVE, self::INACTIVE] => true,
            [self::INACTIVE, self::ACTIVE] => true,
            [self::ACTIVE, self::SUSPENDED] => true,
            [self::SUSPENDED, self::ACTIVE] => true,
            [self::ACTIVE, self::DELETED] => true,
            [self::INACTIVE, self::DELETED] => true,
            [self::SUSPENDED, self::DELETED] => true,
            [self::PENDING, self::ACTIVE] => true,
            [self::PENDING, self::INACTIVE] => true,
            [self::PENDING, self::DELETED] => true,
            [self::PUBLISHED, self::ARCHIVED] => true,
            [self::ARCHIVED, self::PUBLISHED] => true,
            default => false,
        };
    }
    
    /**
     * Get allowed transitions from current status
     */
    public function getAllowedTransitions(): array
    {
        return match($this) {
            self::DRAFT => [self::PUBLISHED, self::DELETED],
            self::PUBLISHED => [self::DRAFT, self::ARCHIVED],
            self::ACTIVE => [self::INACTIVE, self::SUSPENDED, self::DELETED],
            self::INACTIVE => [self::ACTIVE, self::DELETED],
            self::PENDING => [self::ACTIVE, self::INACTIVE, self::DELETED],
            self::SUSPENDED => [self::ACTIVE, self::DELETED],
            self::ARCHIVED => [self::PUBLISHED],
            default => [],
        };
    }
    
    /**
     * Get status label for display
     */
    public function getLabel(): string
    {
        return match($this) {
            self::ACTIVE => 'Active',
            self::INACTIVE => 'Inactive',
            self::DELETED => 'Deleted',
            self::ARCHIVED => 'Archived',
            self::PENDING => 'Pending',
            self::SUSPENDED => 'Suspended',
            self::DRAFT => 'Draft',
            self::PUBLISHED => 'Published',
            self::EXPIRED => 'Expired',
            self::BLOCKED => 'Blocked',
        };
    }
    
    /**
     * Get status description
     */
    public function getDescription(): string
    {
        return match($this) {
            self::ACTIVE => 'The record is active and fully functional',
            self::INACTIVE => 'The record is inactive but can be reactivated',
            self::DELETED => 'The record has been deleted and cannot be recovered',
            self::ARCHIVED => 'The record is archived and read-only',
            self::PENDING => 'The record is pending approval or activation',
            self::SUSPENDED => 'The record is temporarily suspended',
            self::DRAFT => 'The record is a draft and not yet published',
            self::PUBLISHED => 'The record is published and publicly visible',
            self::EXPIRED => 'The record has expired and is no longer valid',
            self::BLOCKED => 'The record is blocked and cannot be accessed',
        };
    }
    
    /**
     * Get status color for UI
     */
    public function getColor(): string
    {
        return match($this) {
            self::ACTIVE => 'green',
            self::INACTIVE => 'gray',
            self::DELETED => 'red',
            self::ARCHIVED => 'purple',
            self::PENDING => 'yellow',
            self::SUSPENDED => 'orange',
            self::DRAFT => 'blue',
            self::PUBLISHED => 'green',
            self::EXPIRED => 'red',
            self::BLOCKED => 'red',
        };
    }
    
    /**
     * Get status icon for UI
     */
    public function getIcon(): string
    {
        return match($this) {
            self::ACTIVE => '✓',
            self::INACTIVE => '○',
            self::DELETED => '✗',
            self::ARCHIVED => '📦',
            self::PENDING => '⏳',
            self::SUSPENDED => '⚠',
            self::DRAFT => '📝',
            self::PUBLISHED => '🌐',
            self::EXPIRED => '⏰',
            self::BLOCKED => '🚫',
        };
    }
    
    /**
     * Create from string value
     */
    public static function fromString(string $value): self
    {
        return self::from($value);
    }
    
    /**
     * Check if string value is valid
     */
    public static function isValid(string $value): bool
    {
        return in_array($value, array_column(self::cases(), 'value'), true);
    }
    
    /**
     * Get all cases as array
     */
    public static function toArray(): array
    {
        return array_map(
            fn(self $case) => [
                'value' => $case->value,
                'label' => $case->getLabel(),
                'description' => $case->getDescription(),
                'color' => $case->getColor(),
                'icon' => $case->getIcon(),
            ],
            self::cases()
        );
    }
}
```

**Usage Example**:
```php
// Creating status
$status = RecordStatus::ACTIVE;
$status = RecordStatus::fromString('active');

// Checking status
if ($status->isActive()) {
    echo "Record is active";
}

if ($status->allowsModification()) {
    // Allow modification
}

// Status transitions
if ($status->canTransitionTo(RecordStatus::INACTIVE)) {
    $newStatus = RecordStatus::INACTIVE;
}

// Getting status information
$label = $status->getLabel();        // "Active"
$description = $status->getDescription(); // "The record is active..."
$color = $status->getColor();       // "green"
$icon = $status->getIcon();         // "✓"

// In database queries
$activeRecords = $repository->findByStatus(RecordStatus::ACTIVE);
$visibleRecords = $repository->findByStatuses(RecordStatus::getVisibleStatuses());

// In validation
if (!RecordStatus::isValid($inputStatus)) {
    throw new ValidationException('Invalid status');
}

// In API responses
return [
    'status' => $record->getStatus()->value,
    'status_label' => $record->getStatus()->getLabel(),
    'status_color' => $record->getStatus()->getColor(),
];
```

---

## 🔧 Integration Patterns

### 1. **Entity Usage**
```php
final class Example
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        private RecordStatus $status = RecordStatus::ACTIVE
    ) {}
    
    public function getStatus(): RecordStatus
    {
        return $this->status;
    }
    
    public function canBeDeleted(): bool
    {
        return $this->status->allowsModification();
    }
    
    public function changeStatus(RecordStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new InvalidStatusTransitionException(
                current: $this->status,
                new: $newStatus
            );
        }
        
        $this->status = $newStatus;
    }
}
```

### 2. **Repository Usage**
```php
final class ExampleRepository
{
    public function findByStatus(RecordStatus $status): array
    {
        return $this->db->createQueryBuilder()
            ->select('*')
            ->from('example')
            ->where('status', $status->value)
            ->fetchAll();
    }
    
    public function findByStatuses(array $statuses): array
    {
        return $this->db->createQueryBuilder()
            ->select('*')
            ->from('example')
            ->where(['status' => $statuses])
            ->fetchAll();
    }
    
    public function findActive(): array
    {
        return $this->findByStatuses(RecordStatus::getActiveStatuses());
    }
    
    public function findVisible(): array
    {
        return $this->findByStatuses(RecordStatus::getVisibleStatuses());
    }
}
```

### 3. **Service Usage**
```php
final class ExampleApplicationService
{
    public function activate(int $id): void
    {
        $example = $this->repository->findById($id);
        
        if ($example === null) {
            throw new NotFoundException('Example', 'id', $id);
        }
        
        if ($example->getStatus()->isActive()) {
            return; // Already active
        }
        
        $example->changeStatus(RecordStatus::ACTIVE);
        $this->repository->save($example);
    }
    
    public function delete(int $id): void
    {
        $example = $this->repository->findById($id);
        
        if ($example === null) {
            throw new NotFoundException('Example', 'id', $id);
        }
        
        if (!$example->canBeDeleted()) {
            throw new CannotDeleteException('Example cannot be deleted in current status');
        }
        
        $example->changeStatus(RecordStatus::DELETED);
        $this->repository->save($example);
    }
}
```

### 4. **Controller Usage**
```php
final class ExampleController
{
    public function actionIndex(): array
    {
        $criteria = SearchCriteria::fromRequest($this->request->getQueryParams());
        
        // Filter by status if provided
        $statusParam = $this->request->getQueryParam('status');
        if ($statusParam && RecordStatus::isValid($statusParam)) {
            $criteria = $criteria->withFilter('status', $statusParam);
        }
        
        $results = $this->service->list($criteria);
        
        // Add status information to results
        $data = array_map(
            fn($item) => [
                ...$item,
                'status_info' => [
                    'label' => RecordStatus::fromString($item['status'])->getLabel(),
                    'color' => RecordStatus::fromString($item['status'])->getColor(),
                    'icon' => RecordStatus::fromString($item['status'])->getIcon(),
                ]
            ],
            $results->data
        );
        
        return [
            ...$results->toArray(),
            'data' => $data
        ];
    }
}
```

---

## 🚀 Best Practices

### 1. **Type Safety**
```php
// ✅ Use enum types
public function __construct(
    private RecordStatus $status
) {}

// ❌ Avoid string types
public function __construct(
    private string $status
) {}
```

### 2. **Validation**
```php
// ✅ Validate enum values
if (!RecordStatus::isValid($input)) {
    throw new ValidationException('Invalid status');
}

// ❌ Avoid manual validation
if (!in_array($input, ['active', 'inactive'])) {
    throw new ValidationException('Invalid status');
}
```

### 3. **Constants Usage**
```php
// ✅ Use constants for configuration
$pageSize = AppConstants::API_DEFAULT_PAGE_SIZE;

// ❌ Avoid magic numbers
$pageSize = 20;
```

### 4. **Status Transitions**
```php
// ✅ Use built-in transition validation
if ($currentStatus->canTransitionTo($newStatus)) {
    // Allow transition
}

// ❌ Avoid manual transition logic
if ($currentStatus === 'active' && $newStatus === 'inactive') {
    // Allow transition
}
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- Enums are memory-efficient
- Constants are loaded once
- Avoid unnecessary object creation

### 2. **Database Queries**
- Use enum values in database queries
- Index status columns for performance
- Filter by status groups when possible

### 3. **Caching**
- Cache enum arrays for repeated use
- Cache configuration values
- Use static methods for frequently accessed data

---

## 🎯 Summary

Enums provide a type-safe, self-documenting way to define constants and status values in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: Strong typing prevents invalid values
- **📖 Self-Documentation**: Named constants are self-explanatory
- **🔄 Immutability**: Enums are immutable by nature
- **🧪 Testability**: Easy to unit test with predictable behavior
- **📦 Centralized Management**: All constants in one place
- **🚀 Performance**: Efficient memory usage and fast access

By following the patterns and best practices outlined in this guide, you can build robust, maintainable enums for your Yii3 API application! 🚀
