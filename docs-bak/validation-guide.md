# Validation Guide

## 📋 Overview

Validation utilities provide a structured way to validate data throughout the Yii3 API application. These components ensure data integrity and provide meaningful error messages for invalid input.

---

## 🏗️ Validation Architecture

### Directory Structure

```
src/Shared/Validation/
├── AbstractValidator.php    # Base validator class
└── ValidationContext.php    # Validation context and state
```

### Design Principles

#### **1. **Extensibility**
- Base validator for custom implementations
- Flexible validation rules
- Composable validation logic

#### **2. **Context Awareness**
- Validation context for additional information
- Conditional validation based on context
- Stateful validation processing

#### **3. **Error Handling**
- Detailed error messages
- Error aggregation
- Localization support

#### **4. **Performance**
- Efficient validation algorithms
- Minimal overhead
- Early termination on failures

---

## 📁 Validation Components

### 1. AbstractValidator

**Purpose**: Base class for creating custom validators

```php
<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Exception\ValidationException;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Abstract Validator Base Class
 */
abstract class AbstractValidator
{
    public function __construct(
        protected ?TranslatorInterface $translator = null,
        protected array $options = []
    ) {}

    /**
     * Validate value
     */
    abstract public function validate(mixed $value, ValidationContext $context = null): ValidationResult;

    /**
     * Check if validator supports the given value type
     */
    abstract public function supports(mixed $value): bool;

    /**
     * Get validator name
     */
    public function getName(): string
    {
        return (new \ReflectionClass($this))->getShortName();
    }

    /**
     * Create validation exception
     */
    protected function createException(string $message, array $params = []): ValidationException
    {
        if ($this->translator) {
            $message = $this->translator->translate($message, $params, 'validation');
        }

        return new ValidationException($message);
    }

    /**
     * Create validation result
     */
    protected function createResult(bool $isValid, array $errors = []): ValidationResult
    {
        return new ValidationResult($isValid, $errors);
    }

    /**
     * Check if option exists
     */
    protected function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Get option value
     */
    protected function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Set option value
     */
    protected function setOption(string $key, mixed $value): void
    {
        $this->options[$key] = $value;
    }

    /**
     * Get all options
     */
    protected function getOptions(): array
    {
        return $this->options;
    }

    /**
     * Merge options
     */
    protected function mergeOptions(array $options): void
    {
        $this->options = array_merge($this->options, $options);
    }
}

/**
 * Validation Result
 */
final class ValidationResult
{
    public function __construct(
        public readonly bool $isValid,
        public readonly array $errors = []
    ) {}

    /**
     * Check if validation passed
     */
    public function passes(): bool
    {
        return $this->isValid;
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool
    {
        return !$this->isValid;
    }

    /**
     * Get errors
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * Get first error
     */
    public function getFirstError(): ?string
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Check if has errors
     */
    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }

    /**
     * Get error count
     */
    public function getErrorCount(): int
    {
        return count($this->errors);
    }

    /**
     * Create successful result
     */
    public static function success(): self
    {
        return new self(true);
    }

    /**
     * Create failed result
     */
    public static function failure(array $errors): self
    {
        return new self(false, $errors);
    }

    /**
     * Merge with another result
     */
    public function merge(ValidationResult $other): self
    {
        $isValid = $this->isValid && $other->isValid;
        $errors = array_merge($this->errors, $other->errors);

        return new self($isValid, $errors);
    }
}

/**
 * String Validator Example
 */
final class StringValidator extends AbstractValidator
{
    public function validate(mixed $value, ValidationContext $context = null): ValidationResult
    {
        if (!$this->supports($value)) {
            return ValidationResult::failure(['Value must be a string']);
        }

        $errors = [];
        $string = (string) $value;

        // Length validation
        if ($this->hasOption('min_length') && strlen($string) < $this->getOption('min_length')) {
            $errors[] = $this->translator
                ? $this->translator->translate('string.min', [
                    'field' => $context?->getField() ?? 'value',
                    'min' => $this->getOption('min_length')
                ], 'validation')
                : "Minimum length is {$this->getOption('min_length')} characters";
        }

        if ($this->hasOption('max_length') && strlen($string) > $this->getOption('max_length')) {
            $errors[] = $this->translator
                ? $this->translator->translate('string.max', [
                    'field' => $context?->getField() ?? 'value',
                    'max' => $this->getOption('max_length')
                ], 'validation')
                : "Maximum length is {$this->getOption('max_length')} characters";
        }

        // Pattern validation
        if ($this->hasOption('pattern') && !preg_match($this->getOption('pattern'), $string)) {
            $errors[] = $this->translator
                ? $this->translator->translate('format.invalid', [
                    'field' => $context?->getField() ?? 'value'
                ], 'validation')
                : 'Invalid format';
        }

        // Email validation
        if ($this->getOption('email', false) && !filter_var($string, FILTER_VALIDATE_EMAIL)) {
            $errors[] = $this->translator
                ? $this->translator->translate('format.email', [
                    'field' => $context?->getField() ?? 'value'
                ], 'validation')
                : 'Invalid email format';
        }

        // URL validation
        if ($this->getOption('url', false) && !filter_var($string, FILTER_VALIDATE_URL)) {
            $errors[] = $this->translator
                ? $this->translator->translate('format.url', [
                    'field' => $context?->getField() ?? 'value'
                ], 'validation')
                : 'Invalid URL format';
        }

        return ValidationResult::failure($errors);
    }

    public function supports(mixed $value): bool
    {
        return is_string($value) || is_numeric($value) || (is_object($value) && method_exists($value, '__toString'));
    }
}

/**
 * Number Validator Example
 */
final class NumberValidator extends AbstractValidator
{
    public function validate(mixed $value, ValidationContext $context = null): ValidationResult
    {
        if (!$this->supports($value)) {
            return ValidationResult::failure(['Value must be a number']);
        }

        $errors = [];
        $number = is_numeric($value) ? (float) $value : 0;

        // Type validation
        if ($this->getOption('integer', false) && !is_int($value) && $value !== (int) $value) {
            $errors[] = $this->translator
                ? $this->translator->translate('number.integer', [
                    'field' => $context?->getField() ?? 'value'
                ], 'validation')
                : 'Value must be an integer';
        }

        // Range validation
        if ($this->hasOption('min') && $number < $this->getOption('min')) {
            $errors[] = $this->translator
                ? $this->translator->translate('number.min', [
                    'field' => $context?->getField() ?? 'value',
                    'min' => $this->getOption('min')
                ], 'validation')
                : "Minimum value is {$this->getOption('min')}";
        }

        if ($this->hasOption('max') && $number > $this->getOption('max')) {
            $errors[] = $this->translator
                ? $this->translator->translate('number.max', [
                    'field' => $context?->getField() ?? 'value',
                    'max' => $this->getOption('max')
                ], 'validation')
                : "Maximum value is {$this->getOption('max')}";
        }

        // Positive validation
        if ($this->getOption('positive', false) && $number <= 0) {
            $errors[] = $this->translator
                ? $this->translator->translate('number.positive', [
                    'field' => $context?->getField() ?? 'value'
                ], 'validation')
                : 'Value must be positive';
        }

        // Negative validation
        if ($this->getOption('negative', false) && $number >= 0) {
            $errors[] = $this->translator
                ? $this->translator->translate('number.negative', [
                    'field' => $context?->getField() ?? 'value'
                ], 'validation')
                : 'Value must be negative';
        }

        // Divisible by validation
        if ($this->hasOption('divisible_by') && $number % $this->getOption('divisible_by') !== 0) {
            $errors[] = $this->translator
                ? $this->translator->translate('number.divisible_by', [
                    'field' => $context?->getField() ?? 'value',
                    'divisor' => $this->getOption('divisible_by')
                ], 'validation')
                : "Value must be divisible by {$this->getOption('divisible_by')}";
        }

        return ValidationResult::failure($errors);
    }

    public function supports(mixed $value): bool
    {
        return is_numeric($value);
    }
}

/**
 * Array Validator Example
 */
final class ArrayValidator extends AbstractValidator
{
    public function validate(mixed $value, ValidationContext $context = null): ValidationResult
    {
        if (!$this->supports($value)) {
            return ValidationResult::failure(['Value must be an array']);
        }

        $errors = [];
        $array = (array) $value;

        // Count validation
        if ($this->hasOption('min_items') && count($array) < $this->getOption('min_items')) {
            $errors[] = $this->translator
                ? $this->translator->translate('array.min_items', [
                    'field' => $context?->getField() ?? 'value',
                    'min' => $this->getOption('min_items')
                ], 'validation')
                : "Array must have at least {$this->getOption('min_items')} items";
        }

        if ($this->hasOption('max_items') && count($array) > $this->getOption('max_items')) {
            $errors[] = $this->translator
                ? $this->translator->translate('array.max_items', [
                    'field' => $context?->getField() ?? 'value',
                    'max' => $this->getOption('max_items')
                ], 'validation')
                : "Array must have at most {$this->getOption('max_items')} items";
        }

        // Required keys validation
        if ($this->hasOption('required_keys')) {
            $requiredKeys = $this->getOption('required_keys');
            $missingKeys = array_diff($requiredKeys, array_keys($array));
            
            if (!empty($missingKeys)) {
                $errors[] = $this->translator
                    ? $this->translator->translate('array.required_keys', [
                        'field' => $context?->getField() ?? 'value',
                        'keys' => implode(', ', $missingKeys)
                    ], 'validation')
                    : "Array must contain keys: " . implode(', ', $missingKeys);
            }
        }

        // Nested validation
        if ($this->hasOption('nested_validator') && $this->hasOption('nested_rules')) {
            $nestedValidator = $this->getOption('nested_validator');
            $nestedRules = $this->getOption('nested_rules');
            
            foreach ($array as $index => $item) {
                $nestedContext = new ValidationContext(
                    field: $context?->getField() ?? 'value',
                    index: $index,
                    parent: $context
                );
                
                $result = $nestedValidator->validate($item, $nestedContext);
                if ($result->fails()) {
                    foreach ($result->getErrors() as $error) {
                        $errors[] = "[{$index}] {$error}";
                    }
                }
            }
        }

        return ValidationResult::failure($errors);
    }

    public function supports(mixed $value): bool
    {
        return is_array($value);
    }
}
```

---

### 2. ValidationContext

**Purpose**: Validation context and state management

```php
<?php

declare(strict_types=1);

namespace App\Shared\Validation;

/**
 * Validation Context
 */
final readonly class ValidationContext
{
    public function __construct(
        public readonly ?string $field = null,
        public readonly mixed $index = null,
        public readonly ?ValidationContext $parent = null,
        public readonly array $data = [],
        public readonly array $options = []
    ) {}

    /**
     * Get field name with full path
     */
    public function getFullPath(): string
    {
        $parts = [];
        
        if ($this->parent) {
            $parts[] = $this->parent->getFullPath();
        }
        
        if ($this->field) {
            $parts[] = $this->field;
        }
        
        if ($this->index !== null) {
            $parts[] = "[{$this->index}]";
        }
        
        return implode('', $parts);
    }

    /**
     * Get data value
     */
    public function getData(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }

    /**
     * Get option value
     */
    public function getOption(string $key, mixed $default = null): mixed
    {
        return $this->options[$key] ?? $default;
    }

    /**
     * Check if has data
     */
    public function hasData(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * Check if has option
     */
    public function hasOption(string $key): bool
    {
        return array_key_exists($key, $this->options);
    }

    /**
     * Create child context
     */
    public function createChild(string $field, mixed $index = null, array $data = []): self
    {
        return new self(
            field: $field,
            index: $index,
            parent: $this,
            data: array_merge($this->data, $data),
            options: $this->options
        );
    }

    /**
     * Create with additional data
     */
    public function withData(array $data): self
    {
        return new self(
            field: $this->field,
            index: $this->index,
            parent: $this->parent,
            data: array_merge($this->data, $data),
            options: $this->options
        );
    }

    /**
     * Create with additional options
     */
    public function withOptions(array $options): self
    {
        return new self(
            field: $this->field,
            index: $this->index,
            parent: $this->parent,
            data: $this->data,
            options: array_merge($this->options, $options)
        );
    }

    /**
     * Get root context
     */
    public function getRoot(): self
    {
        return $this->parent?->getRoot() ?? $this;
    }

    /**
     * Get all parent contexts
     */
    public function getParents(): array
    {
        $parents = [];
        $current = $this->parent;
        
        while ($current) {
            $parents[] = $current;
            $current = $current->parent;
        }
        
        return $parents;
    }

    /**
     * Get depth level
     */
    public function getDepth(): int
    {
        return count($this->getParents());
    }

    /**
     * Check if is root context
     */
    public function isRoot(): bool
    {
        return $this->parent === null;
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'index' => $this->index,
            'full_path' => $this->getFullPath(),
            'data' => $this->data,
            'options' => $this->options,
            'depth' => $this->getDepth(),
            'is_root' => $this->isRoot(),
        ];
    }
}
```

---

## 🔧 Integration Patterns

### 1. **Controller Validation**
```php
final class ExampleController
{
    public function __construct(
        private ValidatorFactory $validatorFactory,
        private TranslatorInterface $translator
    ) {}

    public function actionCreate(): array
    {
        $data = $this->request->getParsedBody();
        
        // Create validation context
        $context = new ValidationContext(
            field: 'user',
            data: $data,
            options: ['locale' => $this->request->getHeaderLine('Accept-Language')]
        );
        
        // Create validators
        $validators = [
            'name' => new StringValidator($this->translator, [
                'min_length' => 2,
                'max_length' => 100,
                'pattern' => '/^[a-zA-Z\s]+$/'
            ]),
            'email' => new StringValidator($this->translator, [
                'email' => true,
                'max_length' => 255
            ]),
            'age' => new NumberValidator($this->translator, [
                'integer' => true,
                'min' => 0,
                'max' => 120
            ]),
        ];
        
        // Validate
        $result = ValidationResult::success();
        
        foreach ($validators as $field => $validator) {
            $fieldContext = $context->createChild($field);
            $fieldResult = $validator->validate($data[$field] ?? null, $fieldContext);
            $result = $result->merge($fieldResult);
        }
        
        if ($result->fails()) {
            throw ValidationException::fromValidationResult([
                $context->getFullPath() => $result->getErrors()
            ]);
        }
        
        // Process valid data
        $command = new CreateExampleCommand(
            name: $data['name'],
            email: $data['email'],
            age: $data['age']
        );
        
        return $this->service->create($command)->toArray();
    }
}
```

### 2. **Service Validation**
```php
final class ExampleApplicationService
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
        private ValidatorFactory $validatorFactory
    ) {}

    public function create(CreateExampleCommand $command): ExampleResponse
    {
        // Business validation
        $context = new ValidationContext(
            field: 'example',
            data: ['email' => $command->email]
        );
        
        $emailValidator = new StringValidator(
            options: ['email' => true]
        );
        
        $result = $emailValidator->validate($command->email, $context);
        
        if ($result->fails()) {
            throw ValidationException::fromValidationResult([
                'email' => $result->getErrors()
            ]);
        }
        
        // Check for existing email
        if ($this->repository->findByEmail($command->email) !== null) {
            throw ConflictException::duplicateResource('Example', 'email', $command->email);
        }
        
        // Create entity
        $example = new Example(
            name: $command->name,
            email: $command->email,
            age: $command->age
        );
        
        $this->repository->save($example);
        
        return ExampleResponse::fromEntity($example);
    }
}
```

### 3. **Custom Validator Example**
```php
final class PasswordValidator extends AbstractValidator
{
    public function validate(mixed $value, ValidationContext $context = null): ValidationResult
    {
        if (!$this->supports($value)) {
            return ValidationResult::failure(['Password must be a string']);
        }

        $password = (string) $value;
        $errors = [];

        // Length validation
        if (strlen($password) < ($this->getOption('min_length', 8))) {
            $errors[] = 'Password must be at least 8 characters long';
        }

        // Complexity validation
        if ($this->getOption('require_uppercase', true) && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if ($this->getOption('require_lowercase', true) && !preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter';
        }

        if ($this->getOption('require_numbers', true) && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($this->getOption('require_symbols', true) && !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        // Common passwords check
        if ($this->getOption('check_common', true) && $this->isCommonPassword($password)) {
            $errors[] = 'Password is too common, please choose a different one';
        }

        return ValidationResult::failure($errors);
    }

    public function supports(mixed $value): bool
    {
        return is_string($value);
    }

    private function isCommonPassword(string $password): bool
    {
        $commonPasswords = [
            'password', '123456', '123456789', 'qwerty', 'abc123',
            'password123', 'admin', 'letmein', 'welcome', 'monkey'
        ];

        return in_array(strtolower($password), $commonPasswords, true);
    }
}
```

---

## 🚀 Best Practices

### 1. **Validator Design**
```php
// ✅ Extend AbstractValidator
final class CustomValidator extends AbstractValidator
{
    public function validate(mixed $value, ValidationContext $context = null): ValidationResult
    {
        // Validation logic
    }
}

// ❌ Standalone function
function validate($value) {
    // Validation logic
}
```

### 2. **Error Handling**
```php
// ✅ Use ValidationResult
$result = $validator->validate($value);
if ($result->fails()) {
    throw ValidationException::fromValidationResult($result->getErrors());
}

// ❌ Throw exceptions directly
if (!isValid($value)) {
    throw new ValidationException('Invalid value');
}
```

### 3. **Context Usage**
```php
// ✅ Use context for field information
$context = new ValidationContext(field: 'user.email');
$result = $validator->validate($value, $context);

// ❌ Ignore context
$result = $validator->validate($value);
```

---

## 📊 Performance Considerations

### 1. **Validation Overhead**
- Use early termination for failed validations
- Cache validation rules when possible
- Avoid expensive regex patterns

### 2. **Memory Usage**
- Use readonly properties
- Avoid creating unnecessary objects
- Reuse validator instances

### 3. **Processing Speed**
- Use built-in PHP functions
- Optimize regex patterns
- Batch validations when possible

---

## 🎯 Summary

Validation utilities provide a structured, extensible way to validate data in the Yii3 API application. Key benefits include:

- **🔍 Extensibility**: Easy to create custom validators
- **📝 Context Awareness**: Rich validation context information
- **🌐 Localization**: Built-in translation support
- **🧪 Testability**: Easy to unit test validators
- **📦 Composability**: Combine multiple validators
- **⚡ Performance**: Efficient validation processing

By following the patterns and best practices outlined in this guide, you can build robust, maintainable validation for your Yii3 API application! 🚀
