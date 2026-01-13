<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

use App\Shared\Request\RequestDataParser;
use App\Shared\Request\RequestParams;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequestParamsMiddleware implements MiddlewareInterface
{
    public function __construct(
        private int $defaultPageSize = 50,
        private int $maxPageSize = 200
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // 1️⃣ Buat parser
        $parser = new RequestDataParser($request);

        // 2️⃣ Buat RequestParams
        $params = new RequestParams($parser, $this->defaultPageSize, $this->maxPageSize);

        // 3️⃣ Simpan di request attribute
        $request = $request->withAttribute('paginationConfig', [
            'defaultPageSize' => $this->defaultPageSize,
            'maxPageSize'     => $this->maxPageSize,
        ]);
        $request = $request->withAttribute('params', $params);

        return $handler->handle($request);
    }
}
