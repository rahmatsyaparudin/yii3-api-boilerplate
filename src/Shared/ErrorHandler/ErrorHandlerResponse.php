<?php

declare(strict_types=1);

namespace App\Shared\ErrorHandler;

use Throwable;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\ErrorHandler\ErrorData;
use Yiisoft\ErrorHandler\ThrowableRendererInterface;

final readonly class ErrorHandlerResponse implements ThrowableRendererInterface
{
    public function render(Throwable $t, ?ServerRequestInterface $request = null): ErrorData
    {
        error_log('ErrorHandlerResponse::render() called - ' . get_class($t));
        
        $response = $this->formatErrorResponse($t);
        
        return new ErrorData(
            (string) json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE),
            ['Content-Type' => 'application/json']
        );
    }

    public function renderVerbose(Throwable $t, ?ServerRequestInterface $request = null): ErrorData
    {
        return $this->render($t, $request);
    }

    /**
     * @return array<string, mixed>
     */
    private function formatErrorResponse(Throwable $throwable): array
    {
        return [
            'code' => 500,
            'success' => false,
            'message' => $throwable->getMessage(),
            'errors' => [
                [
                    'type' => get_class($throwable),
                    'message' => $throwable->getMessage(),
                    'code' => $throwable->getCode(),
                    'file' => $throwable->getFile(),
                    'line' => $throwable->getLine(),
                    'trace' => array_map(function ($trace) {
                        return [
                            'file' => $trace['file'] ?? 'unknown',
                            'line' => $trace['line'] ?? 0,
                            'function' => $trace['function'] ?? 'unknown',
                            'class' => $trace['class'] ?? null,
                            'type' => $trace['type'] ?? null,
                        ];
                    }, array_slice($throwable->getTrace(), 0, 10)),
                ]
            ]
        ];
    }
}
