<?php

declare(strict_types=1);

namespace App\Shared\Validation;

// Vendor Layer
use Yiisoft\Validator\Result;
use Yiisoft\Validator\Validator;

// Shared Layer
use App\Shared\Exception\ValidationException;
use App\Shared\Request\RawParams;

/**
 * Abstract Validator Base Class
 * 
 * This abstract class provides a foundation for implementing context-aware
 * validators using Yii3 Validator framework. It handles the common validation
 * workflow including error formatting and exception throwing.
 * 
 * @package App\Shared\Validation
 * 
 * @example
 * // Concrete validator implementation
 * class UserValidator extends AbstractValidator
 * {
 *     protected function rules(string $context): array
 *     {
 *         return match($context) {
 *             ValidationContext::CREATE => [
 *                 'name' => [new Required(), new HasLength(max: 255)],
 *                 'email' => [new Required(), new Email()],
 *             ],
 *             ValidationContext::UPDATE => [
 *                 'name' => [new HasLength(max: 255)],
 *                 'email' => [new Email()],
 *             ],
 *             default => []
 *         };
 *     }
 * }
 * 
 * @example
 * // Using validator in service
 * $validator = new UserValidator();
 * $data = new RawParams($request->getParsedBody());
 * $validator->validate(ValidationContext::CREATE, $data);
 * 
 * @example
 * // With custom validator configuration
 * class ProductValidator extends AbstractValidator
 * {
 *     protected function buildValidator(): Validator
 *     {
 *         $validator = parent::buildValidator();
 *         $validator->addRule(new CustomBusinessRule());
 *         return $validator;
 *     }
 * }
 * 
 * @example
 * // Context-specific validation
 * $validator = new OrderValidator();
 * $validator->validate(ValidationContext::APPROVE, $orderData);
 * $validator->validate(ValidationContext::REJECT, $orderData);
 * 
 * @example
 * // In controller with error handling
 * try {
 *     $validator->validate(ValidationContext::CREATE, $data);
 *     return $this->service->create($data->all());
 * } catch (ValidationException $e) {
 *     return $this->errorResponse($e->getErrors());
 * }
 */
abstract class AbstractValidator
{
    /**
     * Validate data with context
     * 
     * Performs validation of the provided data using rules specific
     * to the given context. Throws ValidationException if validation fails.
     * 
     * @param string $context Validation context (e.g., ValidationContext::CREATE)
     * @param RawParams $data Data to validate as RawParams object
     * @throws ValidationException If validation fails
     * 
     * @example
     * // Basic validation
     * $validator = new UserValidator();
     * $data = new RawParams(['name' => 'John', 'email' => 'john@example.com']);
     * $validator->validate(ValidationContext::CREATE, $data);
     * 
     * @example
     * // In service layer
     * public function createUser(array $userData): User
     * {
     *     $data = new RawParams($userData);
     *     $this->validator->validate(ValidationContext::CREATE, $data);
     *     return $this->repository->create($userData);
     * }
     * 
     * @example
     * // Context-specific validation
     * $validator = new ProductValidator();
     * $validator->validate(ValidationContext::UPDATE, $updateData);
     * $validator->validate(ValidationContext::DELETE, $deleteData);
     * 
     * @example
     * // With error handling
     * try {
     *     $validator->validate(ValidationContext::CREATE, $data);
     *     // Process valid data...
     * } catch (ValidationException $e) {
     *     $this->logger->error('Validation failed', ['errors' => $e->getErrors()]);
     *     throw $e;
     * }
     */
    final public function validate(string $context, RawParams $data): void
    {
        // Convert RawParams to array for Yii3 validator
        $dataArray = $data->toArray();
        
        $result = $this->buildValidator()->validate($dataArray, $this->rules($context));

        if (!$result->isValid()) {
            throw new ValidationException(
                $this->formatErrors($result)
            );
        }
    }

    /**
     * Build validator instance
     * 
     * Creates and configures the Yii3 Validator instance.
     * Can be overridden in child classes to add custom rules or configuration.
     * 
     * @return Validator Configured validator instance
     * 
     * @example
     * // Default validator
     * $validator = $this->buildValidator();
     * $result = $validator->validate($data, $rules);
     * 
     * @example
     * // Custom validator configuration
     * protected function buildValidator(): Validator
     * {
     *     $validator = parent::buildValidator();
     *     $validator->addRule(new CustomRule());
     *     $validator->setTranslationSource($this->translator);
     *     return $validator;
     * }
     * 
     * @example
     * // With custom error messages
     * protected function buildValidator(): Validator
     * {
     *     $validator = parent::buildValidator();
     *     $validator->addRule(new Required(), message: 'This field is required');
     *     return $validator;
     * }
     */
    protected function buildValidator(): Validator
    {
        return new Validator();
    }

    /**
     * Format validation errors
     * 
     * Converts Yii3 Validator Result errors into a standardized
     * array format suitable for API responses and logging.
     * 
     * @param Result $result Validation result with errors
     * @return array Formatted error array
     * 
     * @example
     * // Error format output
     * [
     *     ['field' => 'name', 'message' => 'Name is required'],
     *     ['field' => 'email', 'message' => 'Email is invalid']
     * ]
     * 
     * @example
     * // In ValidationException
     * $errors = $this->formatErrors($result);
     * throw new ValidationException($errors);
     * 
     * @example
     * // Custom error formatting
     * protected function formatErrors(Result $result): array
     * {
     *     $errors = parent::formatErrors($result);
     *     foreach ($errors as &$error) {
     *         $error['code'] = $this->getErrorCode($error['field']);
     *     }
     *     return $errors;
     * }
     * 
     * @example
     * // For API response
     * $formattedErrors = $this->formatErrors($result);
     * return [
     *     'success' => false,
     *     'errors' => $formattedErrors
     * ];
     */
    private function formatErrors(Result $result): array
    {
        $errors = [];
        foreach ($result->getErrorMessagesIndexedByPath() as $property => $errorList) {
            foreach ($errorList as $message) {
                $errors[] = [
                    'field'   => $property,
                    'message' => $message,
                ];
            }
        }
        return $errors;
    }

    /**
     * Define validation rules by context
     * 
     * Abstract method that must be implemented by child classes
     * to provide validation rules for different contexts.
     * 
     * @param string $context Validation context
     * @return array Array of validation rules
     * 
     * @example
     * // Basic implementation
     * protected function rules(string $context): array
     * {
     *     return match($context) {
     *         ValidationContext::CREATE => [
     *             'name' => [new Required(), new HasLength(max: 255)],
     *             'email' => [new Required(), new Email()],
     *         ],
     *         ValidationContext::UPDATE => [
     *             'name' => [new HasLength(max: 255)],
     *             'email' => [new Email()],
     *         ],
     *         default => []
     *     };
     * }
     * 
     * @example
     * // Complex validation rules
     * protected function rules(string $context): array
     * {
     *     $baseRules = [
     *         'status' => [new In(['active', 'inactive'])],
     *     ];
     *     
     *     return match($context) {
     *         ValidationContext::CREATE => array_merge($baseRules, [
     *             'name' => [new Required(), new HasLength(max: 255)],
     *             'email' => [new Required(), new Email()],
     *         ]),
     *         ValidationContext::UPDATE => array_merge($baseRules, [
     *             'name' => [new HasLength(max: 255)],
     *             'email' => [new Email()],
     *         ]),
     *         ValidationContext::SEARCH => [
     *             'name' => [new HasLength(max: 100)],
     *             'status' => [new In(['active', 'inactive', 'all'])],
     *         ],
     *         default => $baseRules
     *     };
     * }
     * 
     * @example
     * // Conditional rules
     * protected function rules(string $context): array
     * {
     *     $rules = [];
     *     
     *     if ($context === ValidationContext::CREATE) {
     *         $rules['password'] = [new Required(), new HasLength(min: 8)];
     *         $rules['password_confirm'] = [new Required(), new EqualTo('password')];
     *     }
     *     
     *     if (in_array($context, [ValidationContext::CREATE, ValidationContext::UPDATE])) {
     *         $rules['email'] = [new Required(), new Email(), new Unique('users')];
     *     }
     *     
     *     return $rules;
     * }
     */
    abstract protected function rules(string $context): array;
}
