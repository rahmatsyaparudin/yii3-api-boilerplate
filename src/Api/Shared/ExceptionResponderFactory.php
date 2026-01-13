<?php

declare(strict_types=1);

namespace App\Api\Shared;

use App\Shared\Exception\HttpException;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Yiisoft\ErrorHandler\Exception\UserException;
use Yiisoft\ErrorHandler\Middleware\ExceptionResponder;
use Yiisoft\Injector\Injector;
use Yiisoft\Input\Http\InputValidationException;
use Yiisoft\Translator\TranslatorInterface;

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

    private function throwable(\Throwable $exception): ResponseInterface
    {
        if ($exception instanceof HttpException) {
            $code = $exception->getCode();
            
            // Gunakan translate parameter dari exception
            $translate = $exception->getTranslate();
            $message = $this->translator->translate(
                $translate['key'] ?? $exception->getDefaultMessageKey(),
                $translate['params'] ?? $exception->getTranslateParams(),
                'error'
            );

            return $this->apiResponseFactory->fail(
                $message,
                data: $exception->getErrors() ?? [],
                code: \is_int($code) ? $code : null,
                httpCode: $exception->getHttpStatusCode(),
            );
        }

        if (UserException::isUserException($exception)) {
            $code = $exception->getCode();

            return $this->apiResponseFactory->fail(
                $this->translateErrorMessage($exception->getMessage()),
                code: \is_int($code) ? $code : null,
            );
        }

        throw $exception;
    }

    private function translateErrorMessage(string $message, array $params = []): string
    {
        $translated = $this->translator->translate($message, $params, 'error');

        return $translated === '' ? $message : $translated;
    }
}
