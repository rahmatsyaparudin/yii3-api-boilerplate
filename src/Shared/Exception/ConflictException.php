<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Conflict Exception
 * 
 * This exception is thrown when a request conflicts with the current state
 * of a resource or business rule. It returns HTTP 409 Conflict status code
 * and is used for various conflict scenarios including duplicate resources,
 * business rule violations, and state conflicts.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic conflict exception
 * throw new ConflictException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new ConflictException(
 *     translate: 'Resource already exists with the same identifier'
 * );
 * 
 * @example
 * // With localized message and errors using named arguments
 * throw new ConflictException(
 *     translate: Message::create(
 *         key: 'resource.duplicate',
 *         params: ['field' => 'email', 'value' => 'user@example.com']
 *     ),
 *     errors: [
     *         'field' => 'email',
     *         'value' => 'user@example.com',
     * 'existing_id' => 123
 *     ]
 * );
 * 
 * @example
 * // In service for duplicate resource check
 * if ($this->repository->findByEmail($email) !== null) {
 *     throw new ConflictException(
 *         translate: Message::create(
     *             key: 'user.email.exists',
     *             params: ['email' => $email]
     *         ),
     *         errors: [
     *             'field' => 'email',
     *             'value' => $email,
     *             'message' => 'Email address already in use'
     *         ]
     *     );
 * }
 * 
 * @example
 * // Business rule conflict
 * if ($order->getStatus() === OrderStatus::CANCELLED) {
 *     throw new ConflictException(
 *         translate: Message::create(
     *             key: 'order.cancelled_modification',
     *             params: ['order_id' => $order->getId()]
     *         ),
 *         errors: [
     *             'order_id' => $order->getId(),
     *             'status' => $order->getStatus()->value,
     *             'message' => 'Cannot modify cancelled order'
     *         ]
     *     );
 * }
 * 
 * @example
 * // Exception chaining for validation conflicts
 * try {
 *     $this->validateBusinessRules($data);
 * } catch (BusinessRuleException $e) {
 *     throw new ConflictException(
     *         translate: Message::create(
     *             key: 'business.rule.violation',
     *             params: ['rule' => $e->getRule()]
     *         ),
     *         errors: $e->getErrors(),
     *         previous: $e
     *     );
 * }
 */
final class ConflictException extends HttpException
{
    /**
     * Conflict Exception constructor
     * 
     * Creates a new exception with HTTP 409 Conflict status code for resource
     * or business rule conflicts. Supports both string messages and Message objects.
     * 
     * @param Message|string|null $translate Error message or Message object
     * @param array|null $errors Additional error details
     * @param \Throwable|null $previous Previous exception for chaining
     * 
     * @example
     * // Default conflict exception
     * throw new ConflictException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new ConflictException(
     *     translate: 'Resource conflict detected'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new ConflictException(
     *     translate: Message::create(
     *         key: 'resource.duplicate',
     *         params: ['field' => 'username']
     *     )
     * );
     * 
     * @example
     * // With error details using named arguments
     * throw new ConflictException(
     *     translate: 'Email address already exists',
     *     errors: [
     *         'field' => 'email',
     *         'value' => 'user@example.com',
     *         'existing_id' => 123,
     *         'conflict_type' => 'duplicate'
     *     ]
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->validateUniqueness($data);
     * } catch (ValidationException $e) {
     *     throw new ConflictException(
     *         translate: 'Validation conflict detected',
     *         errors: $e->getErrors(),
     *         previous: $e
     *     );
     * }
     */
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : Message::create($translate ?? 'resource.conflict');
            
        parent::__construct(Status::CONFLICT, $message, $errors, null, $previous);
    }
}
