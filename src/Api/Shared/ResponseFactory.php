<?php

declare(strict_types=1);

namespace App\Api\Shared;

use Yiisoft\DataResponse\DataResponseFactoryInterface;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;
use Yiisoft\Validator\Result;
use Psr\Http\Message\ResponseInterface;

use App\Api\Shared\Presenter\AsIsPresenter;
use App\Api\Shared\Presenter\FailPresenter;
use App\Api\Shared\Presenter\PresenterInterface;
use App\Api\Shared\Presenter\SuccessPresenter;
use App\Api\Shared\Presenter\SuccessWithMetaPresenter;
use App\Api\Shared\Presenter\ValidationResultPresenter;

final readonly class ResponseFactory
{
    public function __construct(
        private DataResponseFactoryInterface $dataResponseFactory,
        private TranslatorInterface $translator,
    ) {}

    public function success(
        array|object|null $data = null,
        PresenterInterface $presenter = new AsIsPresenter(),
        ?string $messageKey = null,
        ?array $meta = null,
    ): ResponseInterface {
        // Use custom message key or default 'success'
        $message = $this->translator->translate(
            $messageKey ?? 'success',
            [],
            'app'
        );

        if ($meta !== null) {
            // Use custom presenter that includes meta
            return (new SuccessWithMetaPresenter($this->translator, $meta, $presenter, $message))
                ->present($data, $this->dataResponseFactory->createResponse());
        }

        return (new SuccessPresenter($this->translator, $presenter, $message))
            ->present($data, $this->dataResponseFactory->createResponse());
    }

    public function fail(
        string $message,
        array|object|null $data = null,
        int|null $code = null,
        int $httpCode = Status::BAD_REQUEST,
        PresenterInterface $presenter = new AsIsPresenter(),
    ): ResponseInterface {
        return (new FailPresenter($message, $code, $httpCode, $presenter))
            ->present($data, $this->dataResponseFactory->createResponse());
    }

    public function notFound(string $message = 'Not found.'): ResponseInterface
    {
        return $this->fail($message, httpCode: Status::NOT_FOUND);
    }

    public function failValidation(Result $result): ResponseInterface
    {
        return $this->fail(
            'Validation failed.',
            $result,
            httpCode: Status::UNPROCESSABLE_ENTITY,
            presenter: new ValidationResultPresenter(),
        );
    }
}
