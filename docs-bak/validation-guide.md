# Validation Guide

## 📋 Overview

Input validation in this application is built on top of [`yiisoft/validator`](https://github.com/yiisoft/validator). Each API module defines an `*InputValidator` that extends `App\Shared\Core\Validation\AbstractValidator` and declares per-context rule sets (`CREATE`, `UPDATE`, `DELETE`, `SEARCH`, ...) using Yiisoft rule objects. On failure a `ValidationException` (HTTP 422) with a normalized error list is thrown.

---

## 🏗️ Validation Architecture

### Directory Structure

```
src/Shared/Core/Validation/
├── AbstractValidator.php              # Base validator wrapping Yiisoft\Validator
├── ValidationContextInterface.php     # Context constants (CREATE/UPDATE/DELETE/SEARCH/APPROVE/REJECT)
└── Rules/
    ├── HasNoDependencies.php          # Custom rule: record must not be referenced elsewhere
    ├── HasNoDependenciesHandler.php   # Rule handler (DB check)
    ├── UniqueValue.php                # Custom rule: value must be unique in a table column
    └── UniqueValueHandler.php         # Rule handler (DB check)

src/Shared/Common/Context/
└── ValidationContext.php              # Concrete context class (implements the interface constants)

src/Api/V1/{Module}/Validation/
└── {Module}InputValidator.php         # Per-module validators, e.g. ExampleInputValidator
```

### Design Principles

#### **1. **Context Awareness**
- Validation rules are selected by `ValidationContext` constant
- Different rules per operation (create vs update vs search)
- Custom contexts can be added per module

#### **2. **Fail-Fast**
- `validate()` throws `ValidationException` on the first invalid payload
- `StopOnError` rule group stops a field's rules at the first error

#### **3. **Error Handling**
- Errors are normalized to `[['field' => ..., 'message' => ...], ...]`
- `ValidationException` maps to HTTP 422 automatically
- `Message` value objects provide localization keys

#### **4. **Performance**
- Rules are plain objects — cheap to build per request
- `skipOnEmpty` skips expensive rules for absent values
- `StopOnError` avoids running heavy rules (e.g. DB lookups) when cheap checks already failed

---

## 📁 Validation Components

### 1. AbstractValidator

**Purpose**: Base class for all input validators. Wraps `Yiisoft\Validator\ValidatorInterface`, exposes a `validate()` entry point and normalizes errors.

**Location**: `src/Shared/Core/Validation/AbstractValidator.php`

```php
<?php

declare(strict_types=1);

namespace App\Shared\Core\Validation;

use App\Shared\Core\Exception\ValidationException;
use App\Shared\Core\Request\RawParams;
use App\Shared\Core\ValueObject\LockVersionConfig;
use Yiisoft\Validator\Result;
use Yiisoft\Validator\ValidatorInterface;

abstract class AbstractValidator
{
    protected array $data = [];
    protected mixed $id   = null;

    public function __construct(
        protected LockVersionConfig $lockVersionConfig,
        protected ValidatorInterface $validator
    ) {
    }

    /**
     * Validate $data against the rule set for $context.
     * Throws ValidationException (HTTP 422) when invalid.
     */
    final public function validate(string $context, RawParams $data): void
    {
        $this->data = $data->toArray();
        $this->id   = $this->data['id'] ?? null;

        $result = $this->validator->validate($this->data, $this->rules($context));

        if (!$result->isValid()) {
            throw new ValidationException($this->formatErrors($result));
        }
    }

    /** Normalizes Yiisoft errors to [['field' => 'a.b', 'message' => '...'], ...] */
    private function formatErrors(Result $result): array { /* ... */ }

    /** Whether optimistic locking is enabled for this validator (via LockVersionConfig). */
    protected function isOptimisticLockEnabled(): bool { /* ... */ }
    protected function shouldValidateOptimisticLock(): bool { /* ... */ }

    abstract protected function rules(string $context): array;
}
```

**Key points**:

- `validate()` signature: `validate(string $context, RawParams $data): void` — note the argument order: **context first, then a `RawParams` object** (actions pass the filtered `RawParams`, e.g. `$params` from `RequestParams`).
- `$this->data` holds the payload array; `$this->id` is `$data['id'] ?? null` — use it in rules such as `UniqueValue(ignoreId: $this->data['id'] ?? null)` to exclude the record being updated.
- `shouldValidateOptimisticLock()` consults `LockVersionConfig` (params `app/optimisticLock`: `enabled` + `disabledValues`) — use it with `new Required(when: fn () => $this->shouldValidateOptimisticLock())` for `lock_version`.

---

### 2. ValidationContext

**Purpose**: Constants that select which rule set a validator applies.

**Locations**: `src/Shared/Core/Validation/ValidationContextInterface.php` and `src/Shared/Common/Context/ValidationContext.php`

```php
namespace App\Shared\Core\Validation;

interface ValidationContextInterface
{
    public const SEARCH  = 'search';
    public const CREATE  = 'create';
    public const UPDATE  = 'update';
    public const DELETE  = 'delete';
    public const APPROVE = 'approve';
    public const REJECT  = 'reject';
}
```

```php
namespace App\Shared\Common\Context;

use App\Shared\Core\Validation\ValidationContextInterface;

final class ValidationContext implements ValidationContextInterface
{
    // Constants are inherited from the interface.
    // Add module-specific contexts here when needed, e.g.:
    // public const CREATE_DO = 'create_do';
}
```

Use `App\Shared\Common\Context\ValidationContext` in validators and actions:

```php
$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::CREATE,
);
```

---

### 3. Custom Rules

Two custom `Yiisoft\Validator\RuleInterface` rules ship in `src/Shared/Core/Validation/Rules/`:

#### UniqueValue

Checks that a column value does not already exist in a table.

```php
final class UniqueValue implements RuleInterface
{
    public function __construct(
        public string $table,
        public string $column,
        public mixed $ignoreId = null,     // exclude this id (for updates)
        public string $idColumn = 'id',
        public string $message = 'Data ini sudah ada di database.',
    ) {}

    public function getName(): string    { return 'uniqueValue'; }
    public function getHandler(): string { return UniqueValueHandler::class; }
}
```

#### HasNoDependencies

Checks (on delete) that no other table rows reference the record. `map` is `table => [fk columns]`.

```php
#[\Attribute(\Attribute::TARGET_PROPERTY)]
final class HasNoDependencies implements RuleInterface
{
    public function __construct(
        public array $map,
        public string $message = 'Data sedang digunakan di modul lain.',
    ) {}

    public function getName(): string    { return 'hasNoDependencies'; }
    public function getHandler(): string { return HasNoDependenciesHandler::class; }
}
```

**Handler registration** (`config/common/di/validator.php`):

```php
return [
    RuleHandlerResolverInterface::class => static fn (ContainerInterface $container) =>
        new SimpleRuleHandlerContainer([
            UniqueValueHandler::class => $container->get(UniqueValueHandler::class),
        ]),

    UniqueValueHandler::class => [
        '__construct()' => [
            'db'         => Reference::to(ConnectionInterface::class),
            'translator' => Reference::to(TranslatorInterface::class),
        ],
    ],

    ValidatorInterface::class => Validator::class,
];
```

---

### 4. Module Input Validator

**Purpose**: Concrete per-module validator declaring rules per context.

**Example** (condensed from `src/Api/V1/Example/Validation/ExampleInputValidator.php`):

```php
<?php

declare(strict_types=1);

namespace App\Api\V1\Example\Validation;

use App\Shared\Common\Context\ValidationContext;
use App\Shared\Core\Enums\RecordStatus;
use App\Shared\Core\Validation\AbstractValidator;
use App\Shared\Core\Validation\Rules\UniqueValue;
use Yiisoft\Validator\Rule\In;
use Yiisoft\Validator\Rule\Integer;
use Yiisoft\Validator\Rule\Length;
use Yiisoft\Validator\Rule\Required;
use Yiisoft\Validator\Rule\StopOnError;
use Yiisoft\Validator\Rule\StringValue;

final class ExampleInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [
                'name' => [
                    new StopOnError([
                        new Required(),
                        new StringValue(),
                        new Length(min: 3, max: 255),
                        new UniqueValue(
                            table: 'example',
                            column: 'name',
                            ignoreId: null
                        ),
                    ]),
                ],
                'status' => [
                    new Required(),
                    new Integer(),
                    new In(RecordStatus::draftOnlyStates()),
                ],
            ],
            ValidationContext::UPDATE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                ],
                'name' => [
                    new StopOnError([
                        new StringValue(skipOnEmpty: true),
                        new Length(min: 3, max: 255, skipOnEmpty: true),
                        new UniqueValue(
                            table: 'example',
                            column: 'name',
                            ignoreId: $this->data['id'] ?? null // exclude current record
                        ),
                    ]),
                ],
                'status' => [
                    new Integer(skipOnEmpty: true),
                    new In(RecordStatus::searchableStates()),
                ],
                'lock_version' => [
                    new Required(when: fn () => $this->shouldValidateOptimisticLock()),
                    new Integer(min: 1, skipOnEmpty: true),
                ],
            ],
            ValidationContext::DELETE => [
                'id' => [
                    new Required(),
                    new Integer(min: 1),
                    // Optionally guard the delete:
                    // new HasNoDependencies(map: ['other_table' => ['example_id']]),
                ],
            ],
            ValidationContext::SEARCH => [
                'id'        => [new Integer(skipOnEmpty: true)],
                'name'      => [
                    new StringValue(skipOnEmpty: true),
                    new Length(min: 1, max: 100, skipOnEmpty: true),
                ],
                'status'    => [new Integer(skipOnEmpty: true)],
                'sync_mdb'  => [new Integer(skipOnEmpty: true)],
                'page'      => [new Integer(min: 1, skipOnEmpty: true)],
                'page_size' => [new Integer(min: 1, max: 200, skipOnEmpty: true)],
                'sort_by'   => [new StringValue(skipOnEmpty: true)],
                'sort_dir'  => [new In(['asc', 'desc'], skipOnEmpty: true)],
            ],
            default => [],
        };
    }
}
```

---

## 🔧 Integration Patterns

### 1. **Action Validation**

Actions receive a `RequestParams` object from the `payload` request attribute (set by `RequestParamsMiddleware`), narrow it with `onlyAllowed()`, then validate.

**Create** (`src/Api/V1/Example/Action/ExampleCreateAction.php`):

```php
final class ExampleCreateAction
{
    private const ALLOWED_KEYS = ['name', 'status', 'sync_mdb'];

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        /** @var RequestParams $payload */
        $payload = $request->getAttribute('payload');

        $params = $payload->getRawParams()
            ->onlyAllowed(allowedKeys: self::ALLOWED_KEYS)
            ->with('status', RecordStatus::DRAFT->value)
            ->sanitize();

        // Throws ValidationException (HTTP 422) on failure
        $this->inputValidator->validate(
            data: $params,
            context: ValidationContext::CREATE,
        );

        $command = CreateExampleCommand::create(
            name: (string) $params->get('name'),
            status: $params->get('status'),
            detailInfo: $params->get('detail_info')
        );

        $response = $this->applicationService->create(command: $command);
        // ...
    }
}
```

**Update** (`ExampleUpdateAction.php`) — merges the route `id` into the payload so `UniqueValue` can exclude the current record:

```php
$id      = $currentRoute->getArgument('id');
$payload = $request->getAttribute('payload');

$payload->ensureExists(resource: $resource); // 400 when payload is empty

$params = $payload->getRawParams()
    ->onlyAllowed(allowedKeys: ['name', 'status', 'lock_version'])
    ->with('id', $id)
    ->sanitize();

$this->inputValidator->validate(
    data: $params,
    context: ValidationContext::UPDATE,
);
```

**Search** (`ExampleDataAction.php`) — validates the `filter` block, not the raw params:

```php
$filter = $payload->getFilter()
    ->onlyAllowed(allowedKeys: ['id', 'name', 'status'])
    ->with('status', RecordStatus::DRAFT->value);

$this->inputValidator->validate(
    data: $filter,
    context: ValidationContext::SEARCH,
);
```

### 2. **Optimistic Lock Validation**

`lock_version` is only required when optimistic locking is enabled for the validator (`LockVersionConfig`, params `app/optimisticLock`):

```php
'lock_version' => [
    new Required(when: fn () => $this->shouldValidateOptimisticLock()),
    new Integer(min: 1, skipOnEmpty: true),
],
```

### 3. **Delete Guard**

Prevent deleting a record that is still referenced:

```php
ValidationContext::DELETE => [
    'id' => [
        new Required(),
        new Integer(min: 1),
        new HasNoDependencies(
            map: [
                'other_table'   => ['example_id'],
                'another_table' => ['example_id'],
            ],
            message: 'Data tidak bisa dihapus karena masih digunakan di tabel lain.'
        ),
    ],
],
```

---

## 🚀 Best Practices

### 1. **Validator Design**
```php
// ✅ Extend AbstractValidator and declare rules per context
final class ProductInputValidator extends AbstractValidator
{
    protected function rules(string $context): array
    {
        return match ($context) {
            ValidationContext::CREATE => [/* ... */],
            default => [],
        };
    }
}

// ❌ Ad-hoc validation scattered in actions
if (strlen($data['name']) < 3) { /* ... */ }
```

### 2. **Optional Fields**
```php
// ✅ Use skipOnEmpty for optional fields
'name' => [
    new StringValue(skipOnEmpty: true),
    new Length(min: 3, max: 255, skipOnEmpty: true),
],
```

### 3. **Stop On Error**
```php
// ✅ Wrap a field's rules in StopOnError to fail fast
//    (avoids hitting the DB in UniqueValue when Required already failed)
'name' => [
    new StopOnError([
        new Required(),
        new StringValue(),
        new UniqueValue(table: 'example', column: 'name'),
    ]),
],
```

### 4. **Error Handling**
```php
// ✅ Let validate() throw — the exception responder renders HTTP 422
$this->inputValidator->validate(data: $params, context: ValidationContext::CREATE);

// ❌ Catching and re-wrapping validation errors manually
try { /* validate */ } catch (ValidationException $e) { /* wrap */ }
```

---

## 📊 Performance Considerations

### 1. **Validation Overhead**
- Use `StopOnError` so `UniqueValue`/`HasNoDependencies` DB checks only run when cheap rules pass
- Use `skipOnEmpty` to skip rules for absent optional fields

### 2. **Memory Usage**
- Rule objects are created per `rules()` call — keep them lightweight
- `validate()` stores only the payload array in `$this->data`

### 3. **Processing Speed**
- Put cheap rules (`Required`, `Integer`, `Length`) before expensive ones inside `StopOnError`
- Restrict `SEARCH` filters with `onlyAllowed()` before validating

---

## 🎯 Summary

Validation in this application is context-driven and built on `yiisoft/validator`. Key points:

- **🔍 Extensibility**: New modules add an `*InputValidator` extending `AbstractValidator`
- **📝 Context Awareness**: Rule sets are keyed by `ValidationContext` constants
- **🌐 Localization**: Errors flow through `ValidationException`/`Message` translation keys
- **🧪 Testability**: Rules are declarative arrays — easy to assert per context
- **📦 Composability**: Mix built-in Yiisoft rules with custom `UniqueValue`/`HasNoDependencies`
- **⚡ Performance**: `StopOnError` + `skipOnEmpty` keep validation cheap

By following the patterns and best practices outlined in this guide, you can build robust, maintainable validation for your Yii3 API application! 🚀
