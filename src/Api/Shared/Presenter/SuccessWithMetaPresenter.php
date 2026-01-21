<?php

declare(strict_types=1);

namespace App\Api\Shared\Presenter;

use Yiisoft\DataResponse\DataResponse;
use Yiisoft\Http\Status;

/**
 * Success presenter with meta information (e.g., pagination).
 *
 * @implements PresenterInterface<mixed>
 */
final readonly class SuccessWithMetaPresenter implements PresenterInterface
{
    public function __construct(
        private PresenterInterface $presenter = new AsIsPresenter(),
        private ?array $meta = null,
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
                'meta'    => $this->meta,
                'data'    => $response->getData(),
            ])
            ->withStatus(Status::OK);
    }
}
