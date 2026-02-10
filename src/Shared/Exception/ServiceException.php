<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Service Exception
 * 
 * This exception is thrown when a service layer operation fails due to
 * business logic errors, external service failures, or other service-related issues.
 * It extends HttpException and supports customizable HTTP status codes and data.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Basic service error with default status
 * throw new ServiceException();
 * 
 * @example
 * // With custom message and status using named arguments
 * throw new ServiceException(
 *     translate: 'External service unavailable',
 *     code: Status::SERVICE_UNAVAILABLE
 * );
 * 
 * @example
 * // With localized message and data using named arguments
 * throw new ServiceException(
 *     translate: Message::create(
 *         key: 'service.payment_failed',
 *         params: ['provider' => 'Stripe', 'amount' => 99.99]
 *     ),
 *     data: [
 *         'provider' => 'Stripe',
 *         'amount' => 99.99,
 *         'currency' => 'USD',
 *         'error_code' => 'card_declined'
 *     ],
 *     code: Status::BAD_REQUEST
 * );
 * 
 * @example
 * // In service layer for external API failures
 * try {
 *     $result = $this->externalService->processPayment($paymentData);
 * } catch (\Exception $e) {
 *     throw new ServiceException(
 *         translate: Message::create(
 *             key: 'service.external_api_error',
 *             params: ['service' => 'payment', 'error' => $e->getMessage()]
 *         ),
 *         data: ['service' => 'payment', 'original_error' => $e->getMessage()],
 *         code: Status::SERVICE_UNAVAILABLE,
 *         previous: $e
 *     );
 * }
 * 
 * @example
 * // Business logic validation in service
 * if (!$this->userCanAccessResource($user, $resource)) {
 *     throw new ServiceException(
 *         translate: Message::create(
 *             key: 'service.access_denied',
 *             params: ['resource' => $resource->getId()]
 *         ),
 *         data: ['resource_id' => $resource->getId(), 'user_id' => $user->getId()],
 *         code: Status::FORBIDDEN
 *     );
 * }
 */
final class ServiceException extends HttpException
{
    /**
     * Service Exception constructor
     * 
     * Creates a new service exception with customizable HTTP status code,
     * message, and additional data. Supports both string messages and Message objects.
     * 
     * @param Message|string|null $translate Error message or Message object
     * @param array|null $data Additional service error data
     * @param int|null $code HTTP status code (default: 200 OK)
     * @param \Throwable|null $previous Previous exception for chaining
     * 
     * @example
     * // Default service exception
     * throw new ServiceException();
     * 
     * @example
     * // With custom message and status using named arguments
     * throw new ServiceException(
     *     translate: 'Service temporarily unavailable',
     *     code: Status::SERVICE_UNAVAILABLE
     * );
     * 
     * @example
     * // With localized message using named arguments
     * throw new ServiceException(
     *     translate: Message::create(
     *         key: 'service.validation_failed',
     *         params: ['field' => 'email']
     *     ),
     *     code: Status::BAD_REQUEST
     * );
     * 
     * @example
     * // With error data using named arguments
     * throw new ServiceException(
     *     translate: 'Payment processing failed',
     *     data: [
     *         'payment_id' => 'pay_123',
     *         'amount' => 99.99,
     *         'error_code' => 'insufficient_funds',
     *         'provider' => 'Stripe'
     *     ],
     *     code: Status::BAD_REQUEST
     * );
     * 
     * @example
     * // Exception chaining using named arguments
     * try {
     *     $this->processComplexOperation($data);
     * } catch (\Exception $e) {
     *     throw new ServiceException(
     *         translate: 'Service operation failed',
     *         data: ['operation' => 'complex_processing', 'step' => 'validation'],
     *         code: Status::INTERNAL_SERVER_ERROR,
     *         previous: $e
     *     );
     * }
     */
    public function __construct(Message|string $translate = null, ?array $data = null, ?int $code = Status::OK, ?\Throwable $previous = null)
    {
        $message = $translate instanceof Message 
            ? $translate 
            : Message::create($translate ?? 'service.error');
        parent::__construct($code, $message, $data, null, $previous);
    }
}
