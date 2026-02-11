# Value Objects Guide

## 📋 Overview

Value Objects provide immutable, type-safe representations of domain concepts with built-in validation. In this Yii3 API application, Value Objects ensure data integrity and prevent invalid states.

---

## 🏗️ Value Object Architecture

### Directory Structure

```
src/Shared/ValueObject/
└── Message.php    # Translation message value object
```

### Design Principles

#### **1. **Immutability**
- Readonly properties prevent modification
- No setter methods
- Safe sharing across the application

#### **2. **Type Safety**
- Strong typing with PHP 8+ features
- Validation in constructors
- Compile-time error detection

#### **3. **Value Semantics**
- Equality based on values, not identity
- No side effects
- Predictable behavior

#### **4. **Validation**
- Built-in validation logic
- Fail-fast construction
- Meaningful error messages

---

## 📁 Value Object Components

### 1. Message

**Purpose**: Translation message value object with localization support

```php
<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

/**
 * Translation Message Value Object
 */
final readonly class Message
{
    public function __construct(
        public readonly string $key,
        public readonly array $params = [],
        public readonly ?string $domain = null
    ) {}

    /**
     * Create message with key only
     */
    public static function create(string $key, ?string $domain = null): self
    {
        return new self(key: $key, domain: $domain);
    }

    /**
     * Create message with parameters
     */
    public static function withParams(string $key, array $params, ?string $domain = null): self
    {
        return new self(key: $key, params: $params, domain: $domain);
    }

    /**
     * Create message for error domain
     */
    public static function error(string $key, array $params = []): self
    {
        return new self(key: $key, params: $params, domain: 'error');
    }

    /**
     * Create message for success domain
     */
    public static function success(string $key, array $params = []): self
    {
        return new self(key: $key, params: $params, domain: 'success');
    }

    /**
     * Create message for validation domain
     */
    public static function validation(string $key, array $params = []): self
    {
        return new self(key: $key, params: $params, domain: 'validation');
    }

    /**
     * Create message for app domain
     */
    public static function app(string $key, array $params = []): self
    {
        return new self(key: $key, params: $params, domain: 'app');
    }

    /**
     * Get message key
     */
    public function getKey(): string
    {
        return $this->key;
    }

    /**
     * Get message parameters
     */
    public function getParams(): array
    {
        return $this->params;
    }

    /**
     * Get message domain
     */
    public function getDomain(): ?string
    {
        return $this->domain;
    }

    /**
     * Check if has parameters
     */
    public function hasParams(): bool
    {
        return !empty($this->params);
    }

    /**
     * Check if has domain
     */
    public function hasDomain(): bool
    {
        return $this->domain !== null;
    }

    /**
     * Get parameter value
     */
    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * Check if has parameter
     */
    public function hasParam(string $key): bool
    {
        return array_key_exists($key, $this->params);
    }

    /**
     * Create with additional parameters
     */
    public function withParams(array $params): self
    {
        return new self(
            key: $this->key,
            params: array_merge($this->params, $params),
            domain: $this->domain
        );
    }

    /**
     * Create with domain
     */
    public function withDomain(string $domain): self
    {
        return new self(
            key: $this->key,
            params: $this->params,
            domain: $domain
        );
    }

    /**
     * Create with key
     */
    public function withKey(string $key): self
    {
        return new self(
            key: $key,
            params: $this->params,
            domain: $this->domain
        );
    }

    /**
     * Merge with another message
     */
    public function merge(Message $other): self
    {
        return new self(
            key: $other->key ?: $this->key,
            params: array_merge($this->params, $other->params),
            domain: $other->domain ?: $this->domain
        );
    }

    /**
     * Convert to array
     */
    public function toArray(): array
    {
        return [
            'key' => $this->key,
            'params' => $this->params,
            'domain' => $this->domain,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Create from JSON
     */
    public static function fromJson(string $json): self
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new \InvalidArgumentException('Invalid JSON: ' . json_last_error_msg());
        }
        
        return new self(
            key: $data['key'] ?? '',
            params: $data['params'] ?? [],
            domain: $data['domain'] ?? null
        );
    }

    /**
     * Create from array
     */
    public static function fromArray(array $data): self
    {
        return new self(
            key: $data['key'] ?? '',
            params: $data['params'] ?? [],
            domain: $data['domain'] ?? null
        );
    }

    /**
     * Validate message structure
     */
    public function isValid(): bool
    {
        return !empty($this->key) && is_string($this->key);
    }

    /**
     * Get string representation
     */
    public function __toString(): string
    {
        $parts = [$this->key];
        
        if ($this->hasParams()) {
            $parts[] = '[' . implode(', ', array_map(
                fn($key, $value) => "{$key}: {$value}",
                array_keys($this->params),
                $this->params
            )) . ']';
        }
        
        if ($this->hasDomain()) {
            $parts[] = "@{$this->domain}";
        }
        
        return implode(' ', $parts);
    }

    /**
     * Check equality
     */
    public function equals(Message $other): bool
    {
        return $this->key === $other->key
            && $this->params === $other->params
            && $this->domain === $other->domain;
    }

    /**
     * Create empty message
     */
    public static function empty(): self
    {
        return new self(key: '', params: [], domain: null);
    }

    /**
     * Check if is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->key);
    }
}
```

**Usage Example**:
```php
// Create simple message
$message = Message::create('welcome');

// Create message with parameters
$message = Message::withParams('user.created', [
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);

// Create domain-specific message
$error = Message::error('resource.not_found', [
    'resource' => 'User',
    'field' => 'id',
    'value' => 123
]);

$success = Message::success('general.created', [
    'resource' => 'Example'
]);

// In exception
throw new NotFoundException(
    translate: Message::error('resource.not_found', [
        'resource' => 'User',
        'field' => 'id',
        'value' => $id
    ])
);

// In response factory
return $this->responseFactory->success(
    data: $data,
    translate: Message::success('resource.created', [
        'resource' => 'Example'
    ])
);
```

---

## 🔧 Custom Value Object Examples

### 1. Email Value Object

```php
<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {
        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Invalid email address: ' . $value);
        }
        
        if (strlen($value) > 255) {
            throw new \InvalidArgumentException('Email address too long (max 255 characters)');
        }
    }

    public function __toString(): string
    {
        return $this->value;
    }

    public function getLocalPart(): string
    {
        return explode('@', $this->value)[0];
    }

    public function getDomain(): string
    {
        return explode('@', $this->value)[1];
    }

    public function isFromDomain(string $domain): bool
    {
        return strtolower($this->getDomain()) === strtolower($domain);
    }

    public function equals(Email $other): bool
    {
        return strtolower($this->value) === strtolower($other->value);
    }
}
```

### 2. Money Value Object

```php
<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

final readonly class Money
{
    public function __construct(
        public readonly int $amount,
        public readonly string $currency = 'USD'
    ) {
        if ($amount < 0) {
            throw new \InvalidArgumentException('Amount cannot be negative');
        }
        
        if (!in_array($this->currency, ['USD', 'EUR', 'GBP', 'JPY'], true)) {
            throw new \InvalidArgumentException('Invalid currency: ' . $this->currency);
        }
    }

    public function getFormattedAmount(): string
    {
        return number_format($this->amount / 100, 2);
    }

    public function add(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot add different currencies');
        }
        
        return new Money($this->amount + $other->amount, $this->currency);
    }

    public function subtract(Money $other): Money
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot subtract different currencies');
        }
        
        $newAmount = $this->amount - $other->amount;
        if ($newAmount < 0) {
            throw new \InvalidArgumentException('Resulting amount cannot be negative');
        }
        
        return new Money($newAmount, $this->currency);
    }

    public function multiply(float $multiplier): Money
    {
        $newAmount = (int) round($this->amount * $multiplier);
        return new Money($newAmount, $this->currency);
    }

    public function isGreaterThan(Money $other): bool
    {
        if ($this->currency !== $other->currency) {
            throw new \InvalidArgumentException('Cannot compare different currencies');
        }
        
        return $this->amount > $other->amount;
    }

    public function equals(Money $other): bool
    {
        return $this->amount === $other->amount && $this->currency === $other->currency;
    }
}
```

### 3. Address Value Object

```php
<?php

declare(strict_types=1);

namespace App\Shared\ValueObject;

final readonly class Address
{
    public function __construct(
        public readonly string $street,
        public readonly string $city,
        public readonly string $state,
        public readonly string $postalCode,
        public readonly string $country
    ) {
        if (empty($this->street)) {
            throw new \InvalidArgumentException('Street is required');
        }
        
        if (empty($this->city)) {
            throw new \InvalidArgumentException('City is required');
        }
        
        if (empty($this->state)) {
            throw new \InvalidArgumentException('State is required');
        }
        
        if (empty($this->postalCode)) {
            throw new \InvalidArgumentException('Postal code is required');
        }
        
        if (empty($this->country)) {
            throw new \InvalidArgumentException('Country is required');
        }
        
        if (strlen($this->country) !== 2) {
            throw new \InvalidArgumentException('Country must be 2 characters (ISO 3166-1 alpha-2)');
        }
    }

    public function getFullAddress(): string
    {
        return implode(', ', [
            $this->street,
            $this->city,
            $this->state,
            $this->postalCode,
            $this->country
        ]);
    }

    public function isInCountry(string $country): bool
    {
        return strtoupper($this->country) === strtoupper($country);
    }

    public function equals(Address $other): bool
    {
        return $this->street === $other->street
            && $this->city === $other->city
            && $this->state === $other->state
            && $this->postalCode === $other->postalCode
            && $this->country === $other->country;
    }
}
```

---

## 🔧 Integration Patterns

### 1. **Entity Integration**
```php
final class User
{
    public function __construct(
        public readonly int $id,
        public readonly string $name,
        public readonly Email $email,
        public readonly ?Address $address = null,
        private readonly \DateTimeImmutable $createdAt = new \DateTimeImmutable()
    ) {}

    public function changeEmail(Email $newEmail): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            email: $newEmail,
            address: $this->address,
            createdAt: $this->createdAt
        );
    }

    public function updateAddress(Address $newAddress): self
    {
        return new self(
            id: $this->id,
            name: $this->name,
            email: $this->email,
            address: $newAddress,
            createdAt: $this->createdAt
        );
    }
}
```

### 2. **Service Integration**
```php
final class UserApplicationService
{
    public function create(CreateUserCommand $command): UserResponse
    {
        $email = new Email($command->email);
        
        if ($this->repository->findByEmail($email) !== null) {
            throw ConflictException::duplicateResource('User', 'email', $email->value);
        }
        
        $address = $command->address ? new Address(
            street: $command->address['street'],
            city: $command->address['city'],
            state: $command->address['state'],
            postalCode: $command->address['postalCode'],
            country: $command->address['country']
        ) : null;
        
        $user = new User(
            id: $this->idGenerator->generate(),
            name: $command->name,
            email: $email,
            address: $address
        );
        
        $this->repository->save($user);
        
        return UserResponse::fromEntity($user);
    }
}
```

### 3. **Controller Integration**
```php
final class UserController
{
    public function actionCreate(): array
    {
        $data = $this->request->getParsedBody();
        
        try {
            $email = new Email($data['email']);
            
            $address = null;
            if (!empty($data['address'])) {
                $address = new Address(
                    street: $data['address']['street'],
                    city: $data['address']['city'],
                    state: $data['address']['state'],
                    postalCode: $data['address']['postalCode'],
                    country: $data['address']['country']
                );
            }
            
            $command = new CreateUserCommand(
                name: $data['name'],
                email: $email->value,
                address: $address?->toArray()
            );
            
            $result = $this->service->create($command);
            
            return $result->toArray();
            
        } catch (\InvalidArgumentException $e) {
            throw ValidationException::fieldError('data', $e->getMessage());
        }
    }
}
```

---

## 🚀 Best Practices

### 1. **Immutability**
```php
// ✅ Use readonly properties
final readonly class Email
{
    public function __construct(
        public readonly string $value
    ) {}
}

// ❌ Use mutable properties
class Email
{
    public string $value;
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
            throw new \InvalidArgumentException('Invalid email');
        }
    }
}

// ❌ Validate separately
$email = new Email($value);
if (!$email->isValid()) {
    // Validation should be in constructor
}
```

### 3. **Equality**
```php
// ✅ Value-based equality
public function equals(Email $other): bool
{
    return strtolower($this->value) === strtolower($other->value);
}

// ❌ Identity-based equality
public function equals(Email $other): bool
{
    return $this === $other;
}
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- Value objects are lightweight
- Readonly properties prevent copying
- Avoid large objects in value objects

### 2. **Construction Overhead**
- Validation adds minimal overhead
- Fail-fast prevents invalid states
- Cache frequently used objects

### 3. **Comparison Performance**
- Value comparison is fast
- Use built-in comparison when possible
- Avoid expensive operations in equals()

---

## 🎯 Summary

Value Objects provide immutable, type-safe representations of domain concepts in the Yii3 API application. Key benefits include:

- **🛡️ Type Safety**: Strong typing prevents invalid states
- **🔄 Immutability**: Readonly properties ensure data integrity
- **✅ Validation**: Built-in validation in constructors
- **🧪 Testability**: Easy to unit test with predictable behavior
- **📦 Encapsulation**: Data and behavior together
- **🚀 Performance**: Efficient memory usage and comparison

By following the patterns and best practices outlined in this guide, you can build robust, maintainable value objects for your Yii3 API application! 🚀
