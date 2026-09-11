<?php

declare(strict_types=1);

namespace App\Api;

// PSR Interfaces
use App\Api\Shared\ResponseFactory;
// Shared Layer
use App\Shared\ApplicationParams;
use Psr\Http\Message\ResponseInterface;

final class IndexAction
{
    public function __invoke(
        ResponseFactory $responseFactory,
        ApplicationParams $applicationParams,
    ): ResponseInterface {
        $data = [
            'name'    => $applicationParams->name,
            'version' => $applicationParams->version,
            'language' => $applicationParams->language,
        ];

        if ($applicationParams->environment !== null) {
            $data['environment'] = $applicationParams->environment;
        }

        return $responseFactory->success($data);
    }
}
