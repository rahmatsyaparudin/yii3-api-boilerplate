<?php

declare(strict_types=1);

namespace App\Api\Shared\Presenter;

use Yiisoft\DataResponse\DataResponse;
use Yiisoft\Http\Status;

/**
 * @implements PresenterInterface<mixed>
 */
final readonly class SuccessPresenter implements PresenterInterface
{
    public function __construct(
        private PresenterInterface $presenter = new AsIsPresenter(),
    ) {}

    public function present(mixed $value, DataResponse $response): DataResponse
    {
        $response = $this->presenter->present($value, $response);
        return $response
            ->withData([
                'code' => $response->getStatusCode(),
                'success' => true,
                'message' => $message,
                'data' => $response->getData(),
            ])
            ->withStatus(Status::OK);
    }
}
