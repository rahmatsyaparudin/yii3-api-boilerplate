<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Bad Request Exception
 * 
 * This exception is thrown when the client sends a request with malformed
 * syntax, invalid parameters, or when the server cannot understand the request.
 * It automatically maps to HTTP 400 Bad Request status code.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic usage with default message
 * throw new BadRequestException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new BadRequestException(
 *     translate: 'Invalid email format'
 * );
 * 
 * @example
 * // With Message value object for localization using named arguments
 * throw new BadRequestException(
 *     translate: new Message(
 *         key: 'validation.email_invalid',
 *         params: ['email' => $email]
 *     )
 * );
 * 
 * @example
 * // With validation errors using named arguments
 * throw new BadRequestException(
 *     translate: 'Validation failed',
 *     errors: [
 *         'email' => ['Invalid email format'],
 *         'password' => ['Password too short']
 *     ]
 * );
 * 
 * @example
 * // With all parameters using named arguments
 * throw new BadRequestException(
 *     translate: new Message(
 *         key: 'validation.required',
 *         params: ['field' => 'email']
 *     ),
 *     errors: ['email' => ['Email is required']],
 *     previous: $previousException
 * );
 * 
 * @example
 * // In controller validation
 * public function actionCreate(CreateUserRequest $request): array
 * {
 *     if (!$request->validate()) {
 *         throw new BadRequestException(
 *             translate: 'Validation failed',
 *             errors: $request->getErrors()
 *         );
 *     }
 *     
 *     return $this->service->create($request);
 * }
 * 
 * @example
 * // In service layer validation
 * public function processPayment(PaymentRequest $request): PaymentResponse
 * {
 *     if ($request->amount <= 0) {
 *         throw new BadRequestException(
 *             translate: new Message(
 *                 key: 'payment.amount_invalid',
 *                 params: ['amount' => $request->amount]
 *             )
 *         );
 *     }
 *     
 *     // Process payment...
 * }
 */
final class BadRequestException extends HttpException
{
    /**
     * Bad Request Exception constructor
     * 
     * Creates a new bad request exception with optional custom message,
     * validation errors, and previous exception for chaining.
     * 
     * @param Message|string|null $translate Error message or Message object for localization
     * @param array|null $errors Array of validation errors (optional)
     * @param \Throwable|null $previous Previous exception for chaining (optional)
     * 
     * @example
     * // Default bad request
     * throw new BadRequestException();
     * 
     * @example
     * // With custom string message using named arguments
     * throw new BadRequestException(
     *     translate: 'Invalid input data'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new BadRequestException(
     *     translate: new Message(
     *         key: 'validation.required',
     *         params: ['field' => 'email']
     *     )
     * );
     * 
     * @example
     * // With validation errors using named arguments
     * throw new BadRequestException(
     *     translate: 'Form validation failed',
     *     errors: [
     *         'name' => ['Name is required'],
     *         'email' => ['Email is invalid']
     *     ]
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->validateComplexData($data);
     * } catch (ValidationException $e) {
     *     throw new BadRequestException(
     *         translate: 'Data validation failed',
     *         previous: $e
     *     );
     * }
     * 
     * @example
     * // All parameters with named arguments
     * throw new BadRequestException(
     *     translate: new Message(
     *         key: 'validation.complex',
     *         params: ['step' => 'final']
     *     ),
     *     errors: $validationErrors,
     *     previous: $originalException
     * );
     */
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'http.bad_request');
            
        parent::__construct(Status::BAD_REQUEST, $message, $errors, null, $previous);
    }
}
