<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * No Changes Exception
 * 
 * This exception is thrown when an update operation doesn't result in any
 * actual changes to the resource. It returns HTTP 200 OK status code and is
 * typically used to indicate that no modifications were needed or applied.
 * This is useful for idempotent operations and optimization purposes.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic no changes exception
 * throw new NoChangesException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new NoChangesException(
 *     translate: 'No changes were made to the resource'
 * );
 * 
 * @example
 * // With localized message and data using named arguments
 * throw new NoChangesException(
 *     translate: Message::create(
     *         key: 'resource.no_changes',
     *         params: ['resource' => 'User', 'id' => 123]
     *     ),
     *     data: [
     *         'resource_type' => 'User',
     *         'resource_id' => 123,
     *         'unchanged_fields' => ['name', 'email'],
 *         'operation' => 'update'
     * ]
 * );
 * 
 * @example
 * // In service for idempotent updates
 * public function updateUser(User $user, array $data): User
 * {
 *     $changes = $this->detectChanges($user, $data);
 *     if (empty($changes)) {
     *         throw new NoChangesException(
     *             translate: Message::create(
     *                 key: 'user.no_changes',
     *                 params: ['id' => $user->getId()]
     *             ),
     *             data: [
     *                 'user_id' => $user->getId(),
     *                 'operation' => 'update',
     *                 'message' => 'No changes detected'
     *             ]
     *         );
     *     }
     
     $this->applyChanges($user, $changes);
     return $this->repository->save($user);
 * }
 * 
 * @example
 * // In repository for batch operations
 * public function batchUpdate(array $updates): array
 * {
     $results = [];
 *     foreach ($updates as $update) {
     *         $current = $this->findById($update['id']);
     *         if ($this->isUnchanged($current, $update)) {
     *             throw new NoChangesException(
     *                 translate: Message::create(
     *                     key: 'batch.no_changes',
     *                     params: ['id' => $update['id']]
     *                 ),
     *                 data: [
     *                     'batch_item_id' => $update['id'],
     *                     'status' => 'unchanged'
     *                 ]
     *             );
     *         }
     *         $results[] = $this->update($current, $update);
     *     }
     *     return $results;
 * }
 * 
 * @example
 * // Exception chaining for validation
 * try {
     *     $this->validateChanges($entity, $data);
 * } catch (NoChangesDetectedException $e) {
     *     throw new NoChangesException(
     *         translate: 'No changes detected in update request',
     *         data: ['validation_result' => 'no_changes'],
     *         previous: $e
     *     );
 * }
 */
final class NoChangesException extends HttpException
{
    /**
     * No Changes Exception constructor
     * 
     * Creates a new exception with HTTP 200 OK status code for operations
     * that result in no changes. Supports both string messages and Message objects.
     * 
     * @param Message|string|null $translate Error message or Message object
     * @param array|null $data Additional operation data
     * @param \Throwable|null $previous Previous exception for chaining
     * 
     * @example
     * // Default no changes exception
     * throw new NoChangesException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new NoChangesException(
     *         translate: 'No modifications were required'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new NoChangesException(
     *         translate: Message::create(
     *             key: 'resource.no_changes',
     *             params: ['resource' => 'User']
     *         )
     *     );
     * 
     * @example
     * // With operation data using named arguments
     * throw new NoChangesException(
     *         translate: 'Update operation resulted in no changes',
     *         data: [
     *             'resource_type' => 'Product',
     *             'resource_id' => 456,
     *             'operation' => 'update',
     *             'unchanged_fields' => ['name', 'description', 'price'],
     *             'timestamp' => '2024-01-01T12:00:00Z'
     *         ]
     *     );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->processUpdate($entity, $data);
     * } catch (NoChangesDetectedException $e) {
     *     throw new NoChangesException(
     *         translate: 'Update processing detected no changes',
     *         data: ['detection_result' => 'no_changes'],
     *         previous: $e
     *     );
     * }
     */
    public function __construct(Message|string $translate = null, ?array $data = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : Message::create($translate ?? 'resource.conflict');
            
        parent::__construct(Status::OK, $message, null, $data, $previous);
    }
}
