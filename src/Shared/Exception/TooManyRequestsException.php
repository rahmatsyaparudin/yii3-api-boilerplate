<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Too Many Requests Exception
 * 
 * This exception is thrown when a client exceeds the rate limit for API requests.
 * It extends HttpException and returns HTTP 429 Too Many Requests status code.
 * Typically used by rate limiting middleware to enforce API usage policies.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic usage with default message
 * throw new TooManyRequestsException();
 * 
 * @example
 * // With custom message using named arguments
 * throw new TooManyRequestsException(
 *     translate: Message::create(
 *         key: 'rate_limit.exceeded',
 *         params: [
 *             'limit' => 100,
 *             'window' => 60,
 *             'retry_after' => 45
 *         ]
 *     )
 * );
 * 
 * @example
 * // In rate limiting middleware
 * if ($currentCount >= $this->maxRequests) {
 *     throw new TooManyRequestsException(
 *         translate: Message::create(
 *             key: 'rate_limit.exceeded',
 *             params: [
 *                 'seconds' => $this->windowSize,
 *                 'limit' => $this->maxRequests,
 *                 'remaining' => 0,
 *                 'reset' => $resetTime,
 *                 'retry_after' => $this->windowSize
 *             ]
 *         )
 *     );
 * }
 * 
 * @example
 * // With error details
 * throw new TooManyRequestsException(
 *     translate: 'API rate limit exceeded',
 *     errors: [
 *         'limit' => 100,
 *         'window' => 60,
 *         'retry_after' => 45,
 *         'reset_time' => '2024-01-01T12:01:00Z'
 *     ]
 * );
 * 
 * @example
 * // Exception chaining
 * try {
 *     $this->checkRateLimit($clientIp);
 * } catch (RateLimitExceededException $e) {
 *     throw new TooManyRequestsException(
 *         translate: Message::create(
 *             key: 'rate_limit.exceeded',
 *             params: ['error' => $e->getMessage()]
 *         ),
 *         previous: $e
 *     );
 * }
 */
final class TooManyRequestsException extends HttpException
{
    /**
     * Too Many Requests Exception constructor
     * 
     * Creates a new exception with HTTP 429 status code for rate limit violations.
     * Supports both string messages and Message objects for internationalization.
     * 
     * @param Message|string|null $translate Error message or Message object
     * @param array|null $errors Additional error details
     * @param \Throwable|null $previous Previous exception for chaining
     * 
     * @example
     * // Default exception
     * throw new TooManyRequestsException();
     * 
     * @example
     * // With custom message using named arguments
     * throw new TooManyRequestsException(
     *     translate: 'Rate limit exceeded. Please try again later.'
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new TooManyRequestsException(
     *     translate: Message::create(
     *         key: 'rate_limit.exceeded',
     *         params: [
     *             'limit' => 100,
     *             'window' => 60,
     *             'retry_after' => 30
     *         ]
     *     )
     * );
     * 
     * @example
     * // With error details using named arguments
     * throw new TooManyRequestsException(
     *     translate: Message::create(
     *         key: 'rate_limit.exceeded',
     *         params: ['limit' => 100]
     *     ),
     *     errors: [
     *         'current_count' => 101,
     *         'limit' => 100,
     *         'window_seconds' => 60,
     *         'retry_after' => 45
     *     ]
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->validateRateLimit($request);
     * } catch (\Exception $e) {
     *     throw new TooManyRequestsException(
     *         translate: 'Rate limit validation failed',
     *         errors: ['validation_error' => $e->getMessage()],
     *         previous: $e
     *     );
     * }
     */
    public function __construct(Message|string $translate = null, ?array $errors = null, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : Message::create($translate ?? 'http.too_many_requests');
            
        parent::__construct(Status::TOO_MANY_REQUESTS, $message, $errors, null, $previous);
    }
}
