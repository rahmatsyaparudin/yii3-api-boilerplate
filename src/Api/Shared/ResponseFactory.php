<?php

declare(strict_types=1);

namespace App\Api\Shared;

// Shared Layer
use App\Api\Shared\Presenter\AsIsPresenter;
use App\Api\Shared\Presenter\FailPresenter;
use App\Api\Shared\Presenter\PresenterInterface;
use App\Api\Shared\Presenter\SuccessPresenter;
use App\Api\Shared\Presenter\SuccessWithMetaPresenter;
use App\Api\Shared\Presenter\ValidationResultPresenter;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;

// Vendor Layer
use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;

// Shared Layer
use App\Shared\ValueObject\Message;

final readonly class ResponseFactory
{
    public function __construct(
        private DataResponseFactoryInterface $dataResponseFactory,
        private TranslatorInterface $translator,
    ) {
    }

    public function success(
        array|object|null $data = null,
        ?array $meta = null,
        string|Message|null $translate = null,
        PresenterInterface $presenter = new AsIsPresenter(),
    ): ResponseInterface {
        $message = 'Success'; 
        
        if (is_string($translate)) {
            $message = $translate;
        }
        
        if ($translate instanceof Message) {
            $message = $this->translator->translate(
                $translate->key,
                $translate->params,
                $translate->domain ?? 'success'
            );
        }

        if ($meta !== null) {
            // Use custom presenter that includes meta
            return (new SuccessWithMetaPresenter($presenter, $meta, $message))
                ->present($data, $this->dataResponseFactory->createResponse());
        }

        return (new SuccessPresenter($presenter, $message))
            ->present($data, $this->dataResponseFactory->createResponse());
    }

    public function fail(
        array|object|null $data = null,
        PresenterInterface $presenter = new AsIsPresenter(),
        string|Message|null $translate = null,
        ?int $httpCode = Status::BAD_REQUEST,
    ): ResponseInterface {
        $message = 'Error';
        
        if (is_string($translate)) {
            $message = $translate;
        }
        
        if ($translate instanceof Message) {
            $message = $this->translator->translate(
                $translate->key,
                $translate->params,
                $translate->domain ?? 'error'
            );
        }
        
        return (new FailPresenter($message, $httpCode, $presenter))
            ->present($data, $this->dataResponseFactory->createResponse());
    }

    public function notFound(string $message = 'Not found.'): ResponseInterface
    {
        return $this->fail(
            translate: Message::create(key: 'http.not_found'), 
            httpCode: Status::NOT_FOUND
        );
    }

    public function failValidation(Result $result): ResponseInterface
    {
        return $this->fail(
            data: $result,
            translate: Message::create(key: 'validation.failed'),
            httpCode: Status::UNPROCESSABLE_ENTITY,
            presenter: new ValidationResultPresenter(),
        );
    }
}
