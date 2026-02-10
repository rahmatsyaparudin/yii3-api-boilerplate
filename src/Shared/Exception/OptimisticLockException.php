<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Optimistic Lock Exception
 * 
 * This exception is thrown when an optimistic locking conflict occurs during
 * concurrent updates to the same resource. It returns HTTP 409 Conflict status code
 * and is typically used in repository or service layers to handle concurrent modifications.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic optimistic lock failure
 * throw new OptimisticLockException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new OptimisticLockException(
 *     translate: 'Resource was modified by another user'
 * );
 * 
 * @example
 * // With localized message and data using named arguments
 * throw new OptimisticLockException(
 *     translate: Message::create(
 *         key: 'optimistic.lock.failed',
 *         params: ['resource' => 'User', 'id' => 123]
 *     ),
 *     data: [
 *         'resource_type' => 'User',
 *         'resource_id' => 123,
 *         'current_version' => 5,
 *         'expected_version' => 3,
 *         'conflict_timestamp' => '2024-01-01T12:00:00Z'
 *     ]
 * );
 * 
 * @example
 * // In repository during update
 * public function update(User $user): void
 * {
 *     $current = $this->findById($user->getId());
 *     if ($current->getVersion() !== $user->getVersion()) {
 *         throw new OptimisticLockException(
 *             translate: Message::create(
 *                 key: 'optimistic.lock.failed',
 *                 params: ['resource' => 'User', 'id' => $user->getId()]
 *             ),
 *             data: [
 *                 'resource_id' => $user->getId(),
 *                 'current_version' => $current->getVersion(),
 *                 'expected_version' => $user->getVersion()
 *             ]
 *         );
 *     }
 *     $this->save($user);
 * }
 * 
 * @example
 * // In service layer with exception chaining
 * try {
 *     $this->repository->update($entity);
 * } catch (OptimisticLockException $e) {
 *     throw new OptimisticLockException(
 *         translate: Message::create(
 *             key: 'optimistic.lock.retry_failed',
 *             params: ['attempts' => 3]
 *         ),
 *         data: $e->getData(),
 *         previous: $e
 *     );
 * }
 */
final class OptimisticLockException extends HttpException
{
    /**
     * Optimistic Lock Exception constructor
     * 
     * Creates a new exception with HTTP 409 Conflict status code for optimistic
     * locking conflicts. Supports both string messages and Message objects.
     * 
     * @param Message|string|null $translate Error message or Message object
     * @param array|null $data Additional conflict data (versions, timestamps, etc.)
     * @param \Throwable|null $previous Previous exception for chaining
     * 
     * @example
     * // Default optimistic lock exception
     * throw new OptimisticLockException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new OptimisticLockException(
     *     translate: 'Resource was modified by another user. Please refresh and try again.'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new OptimisticLockException(
     *     translate: Message::create(
     *         key: 'optimistic.lock.failed',
     *         params: ['resource' => 'User', 'id' => 123]
     *     )
     * );
     * 
     * @example
     * // With conflict data using named arguments
     * throw new OptimisticLockException(
     *     translate: 'Concurrent modification detected',
     *     data: [
     *         'resource_type' => 'Product',
     *         'resource_id' => 456,
     *         'current_version' => 7,
     *         'expected_version' => 5,
     *         'modified_by' => 'user_789',
     *     ]
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->validateVersion($entity);
     * } catch (VersionMismatchException $e) {
     *     throw new OptimisticLockException(
     *         translate: 'Version validation failed',
     *         data: ['validation_error' => $e->getMessage()],
     *         previous: $e
     *     );
     * }
     */
    public function __construct(Message|string $translate = null, ?array $data = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : Message::create($translate ?? 'optimistic.lock.failed');
        parent::__construct(Status::CONFLICT, $message, $data, null, $previous);
    }
}
