<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Validation Exception
 * 
 * This exception is thrown when input validation fails. It automatically maps
 * to HTTP 422 Unprocessable Entity status code and is commonly used for
 * form validation, data validation, and business rule validation failures.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic usage with validation errors
 * throw new ValidationException(
 *     errors: [
 *         'email' => ['Invalid email format'],
 *         'password' => ['Password too short']
 *     ]
 * );
 * 
 * @example
 * // With custom message using named arguments
 * throw new ValidationException(
 *     translate: 'Custom validation failed message'
 * );
 * 
 * @example
 * // With localized message using named arguments
 * throw new ValidationException(
 *     translate: new Message(
 *         key: 'validation.complex_failed',
 *         params: ['step' => 'email_validation']
 *     ),
 *     errors: $validationErrors
 * );
 * 
 * @example
 * // In form validation
 * public function validateRegistration(RegistrationRequest $request): array
 * {
 *     $errors = [];
 *     
 *     if (!filter_var($request->email, FILTER_VALIDATE_EMAIL)) {
 *         $errors['email'] = ['Invalid email format'];
 *     }
 *     
 *     if (strlen($request->password) < 8) {
 *         $errors['password'] = ['Password must be at least 8 characters'];
 *     }
 *     
 *     if (!empty($errors)) {
 *         throw new ValidationException(
 *             translate: new Message(
 *                 key: 'registration.validation_failed',
 *                 params: ['field_count' => count($errors)]
 *             ),
 *             errors: $errors
 *         );
 *     }
 *     
 *     return [];
 * }
 * 
 * @example
 * // In service layer validation
 * public function createProduct(CreateProductCommand $command): Product
 * {
 *     $validator = new ProductValidator();
 *     $validationResult = $validator->validate($command);
 *     
 *     if (!$validationResult->isValid()) {
 *         throw new ValidationException(
 *             translate: new Message(
 *                 key: 'product.validation_failed',
 *                 params: ['product_name' => $command->name]
 *             ),
 *             errors: $validationResult->getErrors(),
 *             previous: $validationResult->getException()
 *         );
 *     }
 *     
 *     return $this->repository->save($command);
 * }
 */
final class ValidationException extends HttpException
{
    /**
     * Validation Exception constructor
     * 
     * Creates a new validation exception with validation errors, optional
     * custom message, and previous exception for chaining.
     * 
     * @param array|null $errors Validation errors array (optional)
     * @param Message|string|null $translate Error message or Message object for localization
     * @param \Throwable|null $previous Previous exception for chaining (optional)
     * 
     * @example
     * // With validation errors only
     * throw new ValidationException(
     *     errors: [
     *         'name' => ['Name is required'],
     *         'email' => ['Email is invalid']
     *     ]
     * );
     * 
     * @example
     * // With custom message using named arguments
     * throw new ValidationException(
     *     translate: 'Form validation failed'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new ValidationException(
     *     translate: new Message(
     *         key: 'validation.business_rule_failed',
     *         params: ['rule' => 'unique_email']
     *     )
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->complexValidation($data);
     * } catch (RuleException $e) {
     *         throw new ValidationException(
     *             translate: 'Complex validation failed',
     *             previous: $e
     *         );
     * }
     * 
     * @example
     * // All parameters with named arguments
     * throw new ValidationException(
     *     errors: $validationErrors,
     *     translate: new Message(
     *         key: 'validation.multiple_errors',
     *         params: ['count' => count($validationErrors)]
     *     ),
     *     previous: $originalException
     * );
     */
    public function __construct(?array $errors = null, Message|string $translate = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'validation.failed');
        parent::__construct(Status::UNPROCESSABLE_ENTITY, $message, $errors, null, $previous);
    }
}
