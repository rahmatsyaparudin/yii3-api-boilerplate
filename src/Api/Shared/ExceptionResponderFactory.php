<?php

declare(strict_types=1);

namespace App\Api\Shared;

// Domain Layer
use App\Shared\Exception\HttpException;
use App\Shared\Exception\NoChangesException;
use App\Shared\Exception\ValidationException;

// PSR Interfaces
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

// Vendor Layer
use Yiisoft\ErrorHandler\Exception\UserException;
use Yiisoft\ErrorHandler\Middleware\ExceptionResponder;
use Yiisoft\Injector\Injector;
use Yiisoft\Input\Http\InputValidationException;
use Yiisoft\Translator\TranslatorInterface;

// Shared Layer
use App\Shared\ValueObject\Message;

final readonly class ExceptionResponderFactory
{
    public function __construct(
        private ResponseFactoryInterface $psrResponseFactory,
        private ResponseFactory $apiResponseFactory,
        private TranslatorInterface $translator,
        private Injector $injector,
    ) {
    }

    public function create(): ExceptionResponder
    {
        return new ExceptionResponder(
            [
                InputValidationException::class => $this->inputValidationException(...),
                NoChangesException::class => $this->noChangesException(...),
                \Throwable::class               => $this->throwable(...),
            ],
            $this->psrResponseFactory,
            $this->injector,
        );
    }

    private function inputValidationException(InputValidationException $exception): ResponseInterface
    {
        return $this->apiResponseFactory->failValidation($exception->getResult());
    }

    private function noChangesException(NoChangesException $exception): ResponseInterface
    {
        return $this->apiResponseFactory->success(
            data: $exception->getData(),
            translate: $exception->getTranslateMessage()
        );
    }

    private function throwable(\Throwable $exception): ResponseInterface
    {
        // Format error yang diinginkan untuk semua exception
        $statusCode = 500;
        $message = 'Internal Server Error';
        
        if ($exception instanceof HttpException) {
            $statusCode = $exception->getCode();
            
            // Gunakan Message dari exception
            $translateMessage = $exception->getTranslateMessage();
            $message = $this->translator->translate(
                $translateMessage->getKey(),
                $translateMessage->getParams(),
                $translateMessage->getDomain() ?? 'error'
            );
        } elseif (UserException::isUserException($exception)) {
            $statusCode = 400;
            $message = $exception->getMessage();
        } else {
            // Untuk exception lain seperti ParseError, RuntimeException, dll
            $message = $exception->getMessage();
        }

        // Check environment dan apakah ini business exception
        $showErrors = $this->shouldShowErrorDetails($exception);
        
        // Build error response dengan format yang diinginkan
        $errorData = [
            'code' => $statusCode,
            'success' => false,
            'message' => $message,
            'errors' => []
        ];
        
        // Jika boleh menampilkan detail error
        if ($showErrors) {
            // Untuk ValidationException, tampilkan validation errors
            if ($exception instanceof ValidationException) {
                $validationErrors = $exception->getErrors();
                if ($validationErrors !== null) {
                    $errorData['errors'] = $validationErrors;
                } else {
                    // Fallback ke format default jika tidak ada validation errors
                    $errorData['errors'] = [
                        [
                            'type' => get_class($exception),
                            'message' => $exception->getMessage(),
                            'code' => $exception->getCode(),
                            'file' => $exception->getFile(),
                            'line' => $exception->getLine(),
                            'trace' => $exception->getTraceAsString(),
                        ]
                    ];
                }
            } else {
                // Untuk exception lain, tampilkan detail lengkap
                $errorData['errors'] = [
                    [
                        'type' => get_class($exception),
                        'message' => $exception->getMessage(),
                        'code' => $exception->getCode(),
                        'file' => $exception->getFile(),
                        'line' => $exception->getLine(),
                        'trace' => $exception->getTraceAsString(),
                    ]
                ];
            }
        }

        $response = $this->psrResponseFactory->createResponse($statusCode);
        $jsonResult = json_encode($errorData, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        if ($jsonResult === false) {
            $jsonResult = '{"error": "Failed to encode error data"}';
        }
        $response->getBody()->write($jsonResult);
        
        return $response->withHeader('Content-Type', 'application/json');
    }

    private function shouldShowErrorDetails(\Throwable $exception): bool
    {
        // Get environment variables dari $_ENV (lebih reliable)
        $env = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? 'prod';
        $debug = $_ENV['APP_DEBUG'] ?? $_SERVER['APP_DEBUG'] ?? '0';
        
        // Convert to lowercase for comparison
        $env = strtolower($env);
        $debug = strtolower($debug);
        
        // Hanya tampilkan detail error jika APP_ENV=dev DAN APP_DEBUG=1
        $environmentAllowsDetails = $env === 'dev' && ($debug === '1' || $debug === 'true');
        
        // Jika environment tidak memperbolehkan detail, langsung return false
        if (!$environmentAllowsDetails) {
            return false;
        }
        
        // Jika environment memperbolehkan, cek apakah ini business exception
        // Business exception dari src/Shared/Exception tidak perlu detail
        $exceptionClass = get_class($exception);
        $isBusinessException = str_contains($exceptionClass, 'App\\Shared\\Exception');
        
        // Exception: ValidationException boleh menampilkan detail error di development
        $isValidationException = $exception instanceof ValidationException;
        
        // Tampilkan detail jika: environment allows + (bukan business exception ATAU validation exception)
        $showDetails = $environmentAllowsDetails && (!$isBusinessException || $isValidationException);
        
        return $showDetails;
    }

    private function translateErrorMessage(string $message, array $params = []): string
    {
        $translated = $this->translator->translate($message, $params, 'error');

        return $translated === '' ? $message : $translated;
    }
}
