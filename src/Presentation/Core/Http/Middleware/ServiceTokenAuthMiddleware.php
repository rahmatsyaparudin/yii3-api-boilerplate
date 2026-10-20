<?php

declare(strict_types=1);

namespace App\Presentation\Core\Http\Middleware;

use App\Application\System\Token\ServiceTokenApplicationService;
use App\Infrastructure\Core\Security\CurrentUser;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

/**
 * Service token authenticator — sits BEFORE JwtMiddleware in the stack.
 *
 * `Authorization: Bearer <opaque>` that is not a JWT (JWTs always carry
 * two dots) is tried against `service_tokens`: a hit resolves to the
 * issuing user's Actor — SSO-style, so downstream code (permissions,
 * audit, DetailInfo changelog) treats the call as that user — and the
 * request continues with an `actor` attribute that tells JwtMiddleware
 * to skip. Anything else (no header, JWT-shaped token, unknown/expired
 * service token) falls through to JwtMiddleware unchanged.
 *
 * Disabled via `app.service_token.enabled=false` (the default): the
 * middleware short-circuits straight to the next entry, so the feature
 * is a pure opt-in with zero DB overhead when off.
 */
final class ServiceTokenAuthMiddleware implements MiddlewareInterface
{
    public function __construct(
        private ServiceTokenApplicationService $serviceTokens,
        private CurrentUser $currentUser,
        private bool $enabled = false,
    ) {
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->enabled) {
            return $handler->handle($request);
        }

        $authHeader = $request->getHeaderLine('Authorization');

        if (\str_starts_with($authHeader, 'Bearer ')) {
            $token = \trim(\substr($authHeader, 7));

            // Opaque token (JWTs always contain two dots) → service token lookup.
            if ($token !== '' && \substr_count($token, '.') !== 2) {
                $actor = $this->serviceTokens->authenticate(
                    $token,
                    self::clientHost($request),
                );

                if ($actor !== null) {
                    $this->currentUser->setActor($actor);

                    return $handler->handle($request->withAttribute('actor', $actor));
                }
            }
        }

        return $handler->handle($request);
    }

    /**
     * Caller host for whitelist_host matching: first X-Forwarded-For
     * hop when behind a proxy, else REMOTE_ADDR. Null when unknown
     * (a non-empty whitelist then fails closed).
     */
    private static function clientHost(ServerRequestInterface $request): ?string
    {
        $xff = $request->getHeaderLine('X-Forwarded-For');
        if ($xff !== '') {
            $first = trim(explode(',', $xff)[0]);
            if ($first !== '') {
                return $first;
            }
        }

        $remote = $request->getServerParams()['REMOTE_ADDR'] ?? null;

        return \is_string($remote) && $remote !== '' ? $remote : null;
    }
}
