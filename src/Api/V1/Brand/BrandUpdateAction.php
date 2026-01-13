<?php

declare(strict_types=1);

namespace App\Api\V1\Brand;

use App\Api\Shared\ResponseFactory;
use App\Domain\Brand\BrandService;
use App\Shared\Constants\StatusEnum;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Yiisoft\Http\Status;

final readonly class BrandUpdateAction
{
    public function __construct(
        private BrandService $service,
        private ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequestInterface $request): ResponseInterface
    {
        $id = (int) $request->getAttribute('id');

        if (!$id) {
            return $this->responseFactory->fail('Brand ID is required');
        }

        $body = (array) $request->getParsedBody();

        $name       = isset($body['name']) ? \trim((string) $body['name']) : '';
        $status     = isset($body['status']) ? (int) $body['status'] : StatusEnum::ACTIVE->value;
        $detailInfo = isset($body['detail_info']) && \is_array($body['detail_info']) ? $body['detail_info'] : [];

        if ($name === '') {
            return $this->responseFactory->fail('Name is required', httpCode: Status::UNPROCESSABLE_ENTITY);
        }

        $brand = $this->service->update($id, $name, $status, $detailInfo);

        return $this->responseFactory->success($brand, messageKey: 'success.updated');
    }
}
