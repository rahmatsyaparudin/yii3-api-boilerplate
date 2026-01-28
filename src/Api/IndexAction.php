<?php

declare(strict_types=1);

namespace App\Api;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;

// Shared Layer
use App\Api\Shared\ResponseFactory;
use App\Shared\ApplicationParams;

final class IndexAction
{
    public function __invoke(
        ResponseFactory $responseFactory,
        ApplicationParams $applicationParams,
    ): ResponseInterface {
        return $responseFactory->success([
            'name'    => $applicationParams->name,
            'version' => $applicationParams->version,
        ]);
    }
}
