<?php

declare(strict_types=1);

namespace App\Api\Shared\Presenter;

use Yiisoft\DataResponse\DataResponse;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * @implements PresenterInterface<mixed>
 */
final readonly class SuccessPresenter implements PresenterInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private PresenterInterface $presenter = new AsIsPresenter(),
        private ?string $message = null,
    ) {
    }

    public function present(mixed $value, DataResponse $response): DataResponse
    {
        $response = $this->presenter->present($value, $response);

        return $response
            ->withData([
                'code'    => $response->getStatusCode(),
                'success' => true,
                'message' => $this->message,
                'data'    => $response->getData(),
            ])
            ->withStatus(Status::OK);
    }
}
