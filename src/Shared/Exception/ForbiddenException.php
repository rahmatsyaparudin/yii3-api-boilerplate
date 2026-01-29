<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Forbidden Exception
 * 
 * This exception is thrown when a client is authenticated but does not
 * have permission to access the requested resource. It automatically maps
 * to HTTP 403 Forbidden status code and is commonly used for authorization
 * checks and permission validation.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic usage with default message
 * throw new ForbiddenException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new ForbiddenException(
 *     translate: 'Access denied - insufficient permissions'
 * );
 * 
 * @example
 * // With localized message using named arguments
 * throw new ForbiddenException(
 *     translate: new Message(
 *         key: 'auth.insufficient_permissions',
 *         params: ['resource' => 'admin_panel']
 *     )
 * );
 * 
 * @example
 * // In authorization service
 * public function checkPermission(User $user, string $permission): void
 * {
 *     if (!$this->permissionService->hasPermission($user, $permission)) {
 *         throw new ForbiddenException(
 *             translate: new Message(
 *                 key: 'auth.permission_denied',
 *                 params: ['permission' => $permission, 'user_id' => $user->getId()]
 *             )
 *         );
 *     }
 * }
 * 
 * @example
 * // In controller with role-based access
 * public function actionDelete(int $id): array
 * {
 *     $user = $this->getCurrentUser();
 *     $resource = $this->resourceService->findById($id);
 *     
 *     if (!$this->authorizationService->canDelete($user, $resource)) {
 *         throw new ForbiddenException(
 *             translate: new Message(
 *                 key: 'auth.cannot_delete_resource',
 *                 params: ['resource' => $resource->getType(), 'resource_id' => $id]
 *             )
 *         );
 *     }
 *     
 *     $this->resourceService->delete($resource);
 *     return ['success' => true];
 * }
 * 
 * @example
 * // In middleware for role checking
 * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
 * {
 *     $user = $request->getAttribute('user');
 *     $requiredRole = $this->getRequiredRole($request);
 *     
 *     if (!$user->hasRole($requiredRole)) {
 *         throw new ForbiddenException(
 *             translate: new Message(
 *                 key: 'auth.role_required',
 *                 params: ['required_role' => $requiredRole, 'user_role' => $user->getRole()]
 *             )
 *         );
 *     }
 *     
 *     return $handler->handle($request);
 * }
 * 
 * @example
 * // In service for resource ownership
 * public function updatePost(int $postId, UpdatePostCommand $command): Post
 * {
 *     $post = $this->postRepository->findById($postId);
 *     $user = $this->getCurrentUser();
 *     
 *     if ($post->getAuthorId() !== $user->getId() && !$user->isAdmin()) {
 *         throw new ForbiddenException(
 *             translate: new Message(
 *                 key: 'auth.not_owner',
 *                 params: ['resource' => 'post', 'resource_id' => $postId]
 *             )
 *         );
 *     }
 *     
 *     return $this->postRepository->update($post, $command);
 * }
 */
final class ForbiddenException extends HttpException
{
    /**
     * Forbidden Exception constructor
     * 
     * Creates a new forbidden exception with optional custom message,
     * error details, and previous exception for chaining.
     * 
     * @param Message|string|null $translate Error message or Message object for localization
     * @param array|null $errors Additional error details (optional)
     * @param \Throwable|null $previous Previous exception for chaining (optional)
     * 
     * @example
     * // Default forbidden
     * throw new ForbiddenException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new ForbiddenException(
     *     translate: 'You do not have permission to access this resource'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new ForbiddenException(
     *     translate: new Message(
     *         key: 'auth.access_denied',
     *         params: ['action' => 'delete', 'resource_type' => 'user']
     *     )
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->authorizationService->checkAccess($user, $resource);
     * } catch (AuthorizationException $e) {
     *         throw new ForbiddenException(
     *             translate: 'Authorization check failed',
     *             previous: $e
     *         );
     * }
     * 
     * @example
     * // All parameters with named arguments
     * throw new ForbiddenException(
     *     translate: new Message(
     *         key: 'auth.subscription_required',
     *         params: ['plan' => 'premium', 'feature' => 'advanced_analytics']
     *     ),
     *     errors: ['subscription' => ['Premium subscription required']],
     *     previous: $subscriptionException
     * );
     */
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'http.forbidden');
            
        parent::__construct(Status::FORBIDDEN, $message, $errors, null, $previous);
    }
}
