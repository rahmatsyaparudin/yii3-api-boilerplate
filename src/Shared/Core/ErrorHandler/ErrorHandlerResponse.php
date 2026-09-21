<?php

declare(strict_types=1);

namespace App\Shared\Core\ErrorHandler;

// Vendor Layer
use Psr\Http\Message\ServerRequestInterface;
// PSR Interfaces
use Throwable;
// Domain Layer
use App\Shared\Core\Exception\ValidationException;
use Yiisoft\ErrorHandler\ErrorData;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;

/**
 * Error Handler Response Renderer.
 *
 * This class handles the rendering of throwable exceptions into standardized
 * JSON error responses. It implements the ThrowableRendererInterface to provide
 * consistent error formatting across the application with proper HTTP headers
 * and structured error information.
 *
 * @example
 * // Basic usage in error handling middleware
 * try {
 *     $result = $this->service->process($request);
 * } catch (Throwable $e) {
 *     $errorResponse = new ErrorHandlerResponse();
 *     $errorData = $errorResponse->render($e, $request);
 *     return $response->withStatus(500)
 *                   ->withHeaders($errorData->getHeaders())
 *                   ->withBody($errorData->getBody());
 * }
 * @example
 * // In Yii3 error handler configuration
 * 'errorHandler' => [
 *     'renderer' => ErrorHandlerResponse::class,
 * ],
 * @example
 * // Manual error response generation
 * $renderer = new ErrorHandlerResponse();
 * $errorData = $renderer->render(new BadRequestException('Invalid input'));
 * // Returns JSON with error details and proper headers
 */
final readonly class ErrorHandlerResponse implements ThrowableRendererInterface
{
    /**
     * Render throwable into standardized error response.
     *
     * Converts any throwable exception into a structured JSON response
     * with consistent format including error code, message, and debug information.
     *
     * @param \Throwable                  $t       The throwable exception to render
     * @param ServerRequestInterface|null $request The current request (optional)
     *
     * @return ErrorData Formatted error data with JSON content and headers
     *
     * @example
     * // Basic exception rendering
     * $renderer = new ErrorHandlerResponse();
     * $errorData = $renderer->render(new RuntimeException('Something went wrong'));
     *
     * // Returns JSON:
     * // {
     * //     "code": 500,
     * //     "success": false,
     * //     "message": "Something went wrong",
     * //     "errors": []
     * // }
     * //
     * // In verbose mode (dev), a "trace" key is added:
     * // {
     * //     "trace": {
     * //         "type": "RuntimeException",
     * //         "message": "Something went wrong",
     * //         "code": 0,
     * //         "file": "/path/to/file.php",
     * //         "line": 123,
     * //         "trace": [...]
     * //     }
     * // }
     * @example
     * // With HTTP exception
     * $errorData = $renderer->render(new BadRequestException('Invalid email format'));
     * // Returns structured error with HTTP status context
     * @example
     * // In middleware
     * public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
     * {
     *     try {
     *         return $handler->handle($request);
     *     } catch (Throwable $e) {
     *         $errorData = $this->errorRenderer->render($e, $request);
     *         return $this->responseFactory
     *             ->createResponse(500)
     *             ->withHeader('Content-Type', 'application/json')
     *             ->withBody($this->streamFactory->createStream($errorData->getBody()));
     *     }
     * }
     */
    public function render(\Throwable $t, ?ServerRequestInterface $request = null): ErrorData
    {
        $response = $this->formatErrorResponse($t, false);

        return new ErrorData(
            (string) \json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Render throwable with verbose output.
     *
     * Provides verbose error output for development environments.
     * Currently delegates to the standard render method but can be
     * extended to provide additional debug information.
     *
     * @param \Throwable                  $t       The throwable exception to render
     * @param ServerRequestInterface|null $request The current request (optional)
     *
     * @return ErrorData Formatted error data with verbose information
     *
     * @example
     * // In development environment
     * $renderer = new ErrorHandlerResponse();
     * $errorData = $renderer->renderVerbose($e, $request);
     * // Returns detailed error information for debugging
     * @example
     * // Environment-specific rendering
     * $errorData = $this->app->isDebug()
     *     ? $this->errorRenderer->renderVerbose($e, $request)
     *     : $this->errorRenderer->render($e, $request);
     */
    public function renderVerbose(\Throwable $t, ?ServerRequestInterface $request = null): ErrorData
    {
        $response = $this->formatErrorResponse($t, true);

        return new ErrorData(
            (string) \json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ['Content-Type' => 'application/json']
        );
    }

    /**
     * Format throwable into structured error response array.
     *
     * Creates a standardized error response structure with error code,
     * success flag, message, and detailed error information including
     * file, line, and stack trace for debugging purposes.
     *
     * @param \Throwable $throwable The throwable exception to format
     * @param bool       $verbose   Whether to include the "trace" key (dev/debug mode)
     *
     * @return array<string, mixed> Structured error response data
     *
     * @example
     * // Basic error structure
     * $error = $this->formatErrorResponse(new BadRequestException('Invalid input'));
     * // Returns:
     * // [
     * //     'code' => 500,
     * //     'success' => false,
     * //     'message' => 'Invalid input',
     * //     'errors' => [], // only populated for field validation errors
     * //     'trace' => [...] // only in verbose/dev mode
     * // ]
     * @example
     * // With validation exception
     * $error = $this->formatErrorResponse(new ValidationException('Required field missing'));
     * // Returns structured validation error with field details
     * @example
     * // Custom exception with additional context
     * $error = $this->formatErrorResponse(new ServiceException('Database connection failed'));
     * // Returns service-specific error information
     * @example
     * // Error response structure for API documentation
     * // This format is consistently used across all API endpoints
     * // Clients can expect this structure for all error responses
     */
    private function formatErrorResponse(\Throwable $throwable, bool $verbose): array
    {
        $response = [
            'code'    => 500,
            'success' => false,
            'message' => $throwable->getMessage(),
            'errors'  => [],
        ];

        // errors khusus untuk field validation errors
        if ($throwable instanceof ValidationException) {
            $validationErrors = $throwable->getErrors();
            if ($validationErrors !== null) {
                $response['errors'] = $validationErrors;
            }
        }

        // Detail exception hanya untuk dev, masuk ke 'trace'
        if ($verbose) {
            $response['trace'] = [
                'type'    => $throwable::class,
                'message' => $throwable->getMessage(),
                'code'    => $throwable->getCode(),
                'file'    => $throwable->getFile(),
                'line'    => $throwable->getLine(),
                'trace'   => \array_map(fn ($trace) => [
                    'file'     => $trace['file'] ?? 'unknown',
                    'line'     => $trace['line'] ?? 0,
                    'function' => $trace['function'] ?? 'unknown',
                    'class'    => $trace['class'] ?? null,
                    'type'     => $trace['type'] ?? null,
                ], \array_slice($throwable->getTrace(), 0, 10)),
            ];
        }

        return $response;
    }
}
