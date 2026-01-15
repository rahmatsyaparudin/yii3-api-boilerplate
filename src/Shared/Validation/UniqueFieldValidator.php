<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Exception\ValidationException;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Global Unique Field Validator
 * 
 * Reusable validator for checking unique values across different entities and fields
 * Following DDD best practices for shared validation logic
 */
final class UniqueFieldValidator
{
    public function __construct(
        private TranslatorInterface $translator
    ) {
    }

    /**
     * Validate unique field using repository callback
     * 
     * @param string $field The field name (e.g., 'name', 'email', 'code')
     * @param mixed $value The value to validate
     * @param callable $finder Function that finds existing entity by field value
     * @param string $resource Resource name for error messages
     * @param int|null $excludeId ID to exclude from uniqueness check
     * @throws ValidationException
     */
    public function validateUniqueField(
        string $field,
        mixed $value, 
        callable $finder, 
        string $resource, 
        ?int $excludeId = null
    ): void {
        $existingEntity = $finder($value);
        
        if ($existingEntity !== null && $existingEntity['id'] !== $excludeId) {
            throw new ValidationException(
                errors: [
                    $field => $this->translator->translate(
                        'already_exists', 
                        [
                            'resource' => $resource, 
                            'value' => $value
                        ], 
                        'validation'
                    )
                ]
            );
        }
    }

    /**
     * Validate field length (minimum characters)
     * 
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @param int $minLength Minimum length requirement
     * @throws ValidationException
     */
    public function validateFieldLength(string $field, mixed $value, int $minLength = 3): void
    {
        if (is_string($value) && strlen($value) < $minLength) {
            throw new ValidationException(
                errors: [
                    $field => $this->translator->translate(
                        'min_length', 
                        [
                            'min' => $minLength,
                            'resource' => $field
                        ], 
                        'validation'
                    )
                ]
            );
        }
    }

    /**
     * Validate field is required
     * 
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @throws ValidationException
     */
    public function validateRequiredField(string $field, mixed $value): void
    {
        if ($value === null || $value === '' || (is_array($value) && empty($value))) {
            throw new ValidationException(
                errors: [
                    $field => $this->translator->translate(
                        'required', 
                        ['resource' => $field], 
                        'validation'
                    )
                ]
            );
        }
    }

    /**
     * Validate field format (email, url, etc.)
     * 
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @param string $format Format type ('email', 'url', 'numeric', etc.)
     * @throws ValidationException
     */
    public function validateFieldFormat(string $field, mixed $value, string $format): void
    {
        $isValid = match ($format) {
            'email' => filter_var($value, FILTER_VALIDATE_EMAIL) !== false,
            'url' => filter_var($value, FILTER_VALIDATE_URL) !== false,
            'numeric' => is_numeric($value),
            'integer' => is_int($value) || (is_string($value) && ctype_digit($value)),
            'alpha' => is_string($value) && ctype_alpha($value),
            'alphanumeric' => is_string($value) && ctype_alnum($value),
            default => true
        };

        if (!$isValid) {
            throw new ValidationException(
                errors: [
                    $field => $this->translator->translate(
                        $format, 
                        ['resource' => $field], 
                        'validation'
                    )
                ]
            );
        }
    }

    /**
     * Validate field with multiple rules
     * 
     * @param string $field The field name
     * @param mixed $value The value to validate
     * @param callable $finder Function that finds existing entity by field value
     * @param string $resource Resource name for error messages
     * @param array $options Validation options
     * @throws ValidationException
     */
    public function validateField(
        string $field,
        mixed $value, 
        callable $finder, 
        string $resource, 
        array $options = []
    ): void {
        $excludeId = $options['excludeId'] ?? null;
        $minLength = $options['minLength'] ?? 3;
        $required = $options['required'] ?? false;
        $format = $options['format'] ?? null;

        // Required validation
        if ($required) {
            $this->validateRequiredField($field, $value);
        }

        // Skip other validations if value is empty and not required
        if ($value === null || $value === '') {
            return;
        }

        // Length validation (for strings)
        if (is_string($value) && $minLength > 0) {
            $this->validateFieldLength($field, $value, $minLength);
        }

        // Format validation
        if ($format !== null) {
            $this->validateFieldFormat($field, $value, $format);
        }

        // Uniqueness validation
        $this->validateUniqueField($field, $value, $finder, $resource, $excludeId);
    }
}
