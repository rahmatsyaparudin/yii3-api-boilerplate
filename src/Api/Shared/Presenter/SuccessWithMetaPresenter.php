<?php

declare(strict_types=1);

namespace App\Api\Shared\Presenter;

use Yiisoft\DataResponse\DataResponse;
use Yiisoft\Http\Status;
use Yiisoft\Translator\TranslatorInterface;

/**
 * Success presenter with meta information (e.g., pagination).
 *
 * @implements PresenterInterface<mixed>
 */
final readonly class SuccessWithMetaPresenter implements PresenterInterface
{
    public function __construct(
        private TranslatorInterface $translator,
        private array $meta,
        private PresenterInterface $presenter = new AsIsPresenter(),
        private ?string $message = null,
    ) {
    }

    public function present(mixed $value, DataResponse $response): DataResponse
    {
        $response = $this->presenter->present($value, $response);

        // Use provided message or translate default 'success'
        $message = $this->message ?? $this->translator->translate('success', [], 'app');

        return $response
            ->withData([
                'code'    => $response->getStatusCode(),
                'success' => true,
                'message' => $message,
                'meta'    => $this->meta,
                'data'    => $response->getData(),
            ])
            ->withStatus(Status::OK);
    }
}
