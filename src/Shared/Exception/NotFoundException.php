<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Not Found Exception
 * 
 * This exception is thrown when a requested resource cannot be found.
 * It automatically maps to HTTP 404 Not Found status code and is commonly
 * used in repositories, services, and controllers when looking up entities.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic usage with default message
 * throw new NotFoundException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new NotFoundException(
 *     translate: 'User not found'
 * );
 * 
 * @example
 * // With localized message using named arguments
 * throw new NotFoundException(
 *     translate: new Message(
 *         key: 'resource.not_found',
 *         params: ['resource' => 'User', 'id' => $userId]
 *     )
 * );
 * 
 * @example
 * // With exception chaining using named arguments
 * throw new NotFoundException(
 *     translate: 'Profile not found',
 *     previous: $originalException
 * );
 * 
 * @example
 * // In repository
 * public function findById(int $id): User
 * {
 *     $user = $this->query->findOne(['id' => $id]);
 *     if (!$user) {
 *         throw new NotFoundException(
 *             translate: new Message(
 *                 key: 'user.not_found',
 *                 params: ['id' => $id]
 *             )
 *         );
 *     }
 *     return $user;
 * }
 * 
 * @example
 * // In service layer
 * public function getUserProfile(int $userId): UserProfile
 * {
 *     try {
 *         $user = $this->userRepository->findById($userId);
 *         return $this->profileRepository->findByUserId($userId);
 *     } catch (NotFoundException $e) {
 *         throw new NotFoundException(
 *             translate: new Message(
 *                 key: 'user.profile.not_found',
 *                 params: ['user_id' => $userId]
 *             ),
 *             previous: $e
 *         );
 *     }
 * }
 * 
 * @example
 * // In controller
 * public function actionView(int $id): array
 * {
 *     $user = $this->userService->findById($id);
 *     return $this->serializer->toArray($user);
 * }
 */
final class NotFoundException extends HttpException
{
    /**
     * Not Found Exception constructor
     * 
     * Creates a new not found exception with optional custom message,
     * error details, and previous exception for chaining.
     * 
     * @param Message|string|null $translate Error message or Message object for localization
     * @param array|null $errors Additional error details (optional)
     * @param \Throwable|null $previous Previous exception for chaining (optional)
     * 
     * @example
     * // Default not found
     * throw new NotFoundException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new NotFoundException(
     *     translate: 'Product not found'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new NotFoundException(
     *         translate: new Message(
     *             key: 'product.not_found',
     *             params: ['sku' => $sku]
     *         )
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $entity = $this->repository->find($id);
     *     if (!$entity) {
     *         throw new NotFoundException('Entity not found');
     *     }
     * } catch (DatabaseException $e) {
     *         throw new NotFoundException(
     *             translate: 'Failed to locate resource',
     *             previous: $e
     *         );
     * }
     * 
     * @example
     * // All parameters with named arguments
     * throw new NotFoundException(
     *     translate: new Message(
     *         key: 'order.not_found',
     *         params: ['order_id' => $orderId]
     *     ),
     *     errors: ['order_id' => ['Invalid order ID']],
     *     previous: $databaseException
     * );
     */
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'http.not_found');
            
        parent::__construct(Status::NOT_FOUND, $message, $errors, null, $previous);
    }
}
