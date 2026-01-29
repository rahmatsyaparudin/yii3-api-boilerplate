<?php

declare(strict_types=1);

namespace App\Shared\Exception;

// Vendor Layer
use Yiisoft\Http\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Base HTTP Exception
 * 
 * Abstract base class for all HTTP-related exceptions in the application.
 * Provides common functionality for HTTP status codes, localized messages,
 * error details, and additional data payload. All custom HTTP exceptions
 * should extend this class for consistency.
 * 
 * @package App\Shared\Exception
 * 
 * @example
 * // Creating a custom HTTP exception
 * final class PaymentRequiredException extends HttpException
 * {
 *     public function __construct(Message|string $message = null, ?array $data = null, ?\Throwable $previous = null)
 *     {
 *         $messageObj = $message instanceof Message 
 *             ? $message 
 *             : new Message($message ?? 'http.payment_required');
 *             
 *         parent::__construct(Status::PAYMENT_REQUIRED, $messageObj, null, $data, $previous);
 *     }
 * }
 * 
 * @example
 * // Using HTTP exception in controller
 * throw new PaymentRequiredException(
 *     new Message('payment.required', ['amount' => $requiredAmount]),
 *     ['required_amount' => $requiredAmount, 'currency' => 'USD']
 * );
 * 
 * @example
 * // Catching and handling HTTP exceptions
 * try {
 *     $this->processRequest($request);
 * } catch (HttpException $e) {
 *     return $this->responseFactory
 *         ->createResponse($e->getHttpStatusCode())
 *         ->withHeader('Content-Type', 'application/json')
 *         ->withBody(json_encode([
 *             'error' => $e->getTranslateMessage()->getKey(),
 *             'message' => $e->getTranslateMessage()->getParams(),
 *             'errors' => $e->getErrors(),
 *             'data' => $e->getData()
 *         ]));
 * }
 */
abstract class HttpException extends \RuntimeException
{
    /**
     * HTTP Exception constructor
     * 
     * Initializes the exception with HTTP status code, localized message,
     * optional validation errors, and additional data payload.
     * 
     * @param int $httpStatusCode HTTP status code (e.g., 400, 404, 500)
     * @param Message $translateMessage Localized message object
     * @param array|null $errors Validation errors or error details (optional)
     * @param array|null $data Additional data payload (optional)
     * @param \Throwable|null $previous Previous exception for chaining (optional)
     * 
     * @example
     * // Basic HTTP exception
     * parent::__construct(
     *     Status::NOT_FOUND,
     *     new Message('resource.not_found', ['resource' => 'User']),
     *     null,
     *     ['user_id' => $userId]
     * );
     * 
     * @example
     * // With validation errors
     * parent::__construct(
     *     Status::UNPROCESSABLE_ENTITY,
     *     new Message('validation.failed'),
     *     [
     *         'email' => ['Invalid format'],
     *         'password' => ['Too short']
     *     ],
     *     null
     * );
     */
    public function __construct(
        private readonly int $httpStatusCode,
        private readonly Message $translateMessage,
        private readonly ?array $errors = null,
        private readonly ?array $data = null,
        ?\Throwable $previous = null
    ) {
        parent::__construct('', $httpStatusCode, $previous);
    }

    /**
     * Get HTTP status code
     * 
     * Returns the HTTP status code associated with this exception.
     * This is used to set the response status when handling the exception.
     * 
     * @return int HTTP status code (e.g., 400, 404, 500)
     * 
     * @example
     * $statusCode = $exception->getHttpStatusCode();
     * $response = $response->withStatus($statusCode);
     * 
     * @example
     * // In error handler
     * $status = $e instanceof HttpException ? $e->getHttpStatusCode() : 500;
     */
    public function getHttpStatusCode(): int
    {
        return $this->httpStatusCode;
    }
    
    /**
     * Get translate message object
     * 
     * Returns the Message object containing the translation key and parameters.
     * This can be used for localization and internationalization.
     * 
     * @return Message Message object with translation key and parameters
     * 
     * @example
     * $message = $exception->getTranslateMessage();
     * $translatedText = $translator->translate(
     *     $message->getKey(),
     *     $message->getParams()
     * );
     * 
     * @example
     * // In API response
     * return [
     *     'error_key' => $e->getTranslateMessage()->getKey(),
     *     'error_params' => $e->getTranslateMessage()->getParams()
     * ];
     */
    public function getTranslateMessage(): Message
    {
        return $this->translateMessage;
    }
    
    /**
     * Get default message key
     * 
     * Returns the translation key for the exception message.
     * This is a shortcut for accessing the message key directly.
     * 
     * @return string Translation key (e.g., 'http.not_found')
     * 
     * @example
     * $messageKey = $exception->getDefaultMessageKey();
     * // Returns: 'resource.not_found'
     * 
     * @example
     * // In logging
     * $this->logger->error('HTTP Exception', [
     *     'message_key' => $e->getDefaultMessageKey(),
     *     'status_code' => $e->getHttpStatusCode()
     * ]);
     */
    public function getDefaultMessageKey(): string
    {
        return $this->translateMessage->getKey();
    }
    
    /**
     * Get translation parameters
     * 
     * Returns the parameters array used for message translation.
     * These parameters are used to substitute placeholders in the message.
     * 
     * @return array Translation parameters (e.g., ['resource' => 'User'])
     * 
     * @example
     * $params = $exception->getTranslateParams();
     * // Returns: ['resource' => 'User', 'id' => 123]
     * 
     * @example
     * // In error response
     * return [
     *     'message' => $translator->translate(
     *         $e->getDefaultMessageKey(),
     *         $e->getTranslateParams()
     *     )
     * ];
     */
    public function getTranslateParams(): array
    {
        return $this->translateMessage->getParams();
    }
    
    /**
     * Get validation errors
     * 
     * Returns an array of validation errors or error details.
     * This is typically used for form validation failures.
     * 
     * @return array|null Validation errors array or null if no errors
     * 
     * @example
     * $errors = $exception->getErrors();
     * // Returns:
     * // [
     * //     'email' => ['Invalid format'],
     * //     'password' => ['Too short']
     * // ]
     * 
     * @example
     * // In API response
     * return [
     *     'success' => false,
     *     'errors' => $e->getErrors()
     * ];
     */
    public function getErrors(): ?array
    {
        return $this->errors;
    }
    
    /**
     * Get additional data
     * 
     * Returns additional data payload associated with the exception.
     * This can be used to provide context or additional information.
     * 
     * @return array|null Additional data array or null if no data
     * 
     * @example
     * $data = $exception->getData();
     * // Returns:
     * // [
     * //     'user_id' => 123,
     * //     'attempted_action' => 'delete'
     * // ]
     * 
     * @example
     * // In error logging
     * $this->logger->error('Exception occurred', [
     *     'message' => $e->getMessage(),
     *     'data' => $e->getData(),
     *     'errors' => $e->getErrors()
     * ]);
     */
    public function getData(): ?array
    {
        return $this->data;
    }
}
