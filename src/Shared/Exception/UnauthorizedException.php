<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Unauthorized Exception
 * 
 * This exception is thrown when authentication fails or when a client
 * attempts to access a resource without proper authentication credentials.
 * It automatically maps to HTTP 401 Unauthorized status code.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic usage with default message
 * throw new UnauthorizedException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new UnauthorizedException(
 *     translate: 'Invalid credentials provided'
 * );
 * 
 * @example
 * // With localized message using named arguments
 * throw new UnauthorizedException(
 *     translate: new Message(
 *         key: 'auth.invalid_token',
 *         params: ['reason' => 'expired']
 *     )
 * );
 * 
 * @example
 * // In authentication service
 * public function authenticate(string $token): User
 * {
 *     if (!$this->tokenValidator->isValid($token)) {
 *         throw new UnauthorizedException(
 *             translate: new Message(
 *                 key: 'auth.token_invalid',
 *                 params: ['token_type' => 'JWT']
 *             )
 *         );
 *     }
 *     
 *     return $this->userRepository->findByToken($token);
 * }
 * 
 * @example
 * // In middleware
 * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
 * {
 *     $token = $request->getHeaderLine('Authorization');
 *     
 *     if (empty($token)) {
 *         throw new UnauthorizedException(
 *             translate: new Message(
 *                 key: 'auth.token_missing',
 *                 params: ['header' => 'Authorization']
 *             )
 *         );
 *     }
 *     
 *     try {
 *         $user = $this->authService->authenticate($token);
 *         return $handler->handle($request->withAttribute('user', $user));
 *     } catch (TokenException $e) {
 *         throw new UnauthorizedException(
 *             translate: 'Authentication failed',
 *             previous: $e
 *         );
 *     }
 * }
 * 
 * @example
 * // In API controller
 * public function actionProfile(): array
 * {
 *     $user = $this->getCurrentUser();
 *     
 *     if (!$user->isEmailVerified()) {
 *         throw new UnauthorizedException(
 *             translate: new Message(
 *                 key: 'auth.email_not_verified',
 *                 params: ['email' => $user->getEmail()]
 *             )
 *         );
 *     }
 *     
 *     return $this->serializer->toArray($user);
 * }
 */
final class UnauthorizedException extends HttpException
{
    /**
     * Unauthorized Exception constructor
     * 
     * Creates a new unauthorized exception with optional custom message,
     * error details, and previous exception for chaining.
     * 
     * @param Message|string|null $translate Error message or Message object for localization
     * @param array|null $errors Additional error details (optional)
     * @param \Throwable|null $previous Previous exception for chaining (optional)
     * 
     * @example
     * // Default unauthorized
     * throw new UnauthorizedException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new UnauthorizedException(
     *     translate: 'Access denied - invalid credentials'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new UnauthorizedException(
     *     translate: new Message(
     *         key: 'auth.session_expired',
 *         params: ['session_id' => $sessionId]
     *     )
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->validateCredentials($credentials);
     * } catch (CredentialException $e) {
     *         throw new UnauthorizedException(
     *             translate: 'Credential validation failed',
     *             previous: $e
     *         );
     * }
     * 
     * @example
     * // All parameters with named arguments
     * throw new UnauthorizedException(
     *     translate: new Message(
     *         key: 'auth.multiple_attempts',
     *         params: ['attempts' => $attemptCount, 'max_attempts' => 3]
     *     ),
     *     errors: ['auth' => ['Too many failed attempts']],
     *     previous: $securityException
     * );
     */
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : new Message($translate ?? 'http.unauthorized');
            
        parent::__construct(Status::UNAUTHORIZED, $message, $errors, null, $previous);
    }
}
