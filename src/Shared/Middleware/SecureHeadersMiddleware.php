<?php

declare(strict_types=1);

namespace App\Shared\Middleware;

// PSR Interfaces
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class SecureHeadersMiddleware implements MiddlewareInterface
{
    private array $headers;

    public function __construct(array $config = [])
    {
        $this->headers = \array_merge([
            'X-Content-Type-Options'    => 'nosniff',
            'X-Frame-Options'           => 'SAMEORIGIN',
            'X-XSS-Protection'          => '1; mode=block',
            'Referrer-Policy'           => 'strict-origin-when-cross-origin',
            'Content-Security-Policy'   => $this->buildCsp($config['csp'] ?? []),
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains',
            'Permissions-Policy'        => $this->buildPermissionsPolicy($config['permissions'] ?? []),
        ], $config['custom'] ?? []);
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $response = $handler->handle($request);

        foreach ($this->headers as $name => $value) {
            if ($value !== null && \is_string($value)) {
                $response = $response->withHeader($name, $value);
            }
        }

        return $response;
    }

    private function buildCsp(array $config): string
    {
        $directives = \array_merge([
            'default-src' => "'self'",
            'script-src'  => "'self' 'unsafe-inline' 'unsafe-eval'",
            'style-src'   => "'self' 'unsafe-inline'",
            'img-src'     => "'self' data: https:",
            'font-src'    => "'self'",
            'connect-src' => "'self'",
        ], $config);

        foreach ($directives as $directive => $sources) {
            if (\is_array($sources)) {
                $sources = \implode(' ', $sources);
            }
            $directives[$directive] = $directive . ' ' . (string) $sources;
        }

        return \implode('; ', $directives);
    }

    private function buildPermissionsPolicy(array $config): string
    {
        $policies = \array_merge([
            'geolocation'   => '()',
            'microphone'    => '()',
            'camera'        => '()',
            'payment'       => '()',
            'usb'           => '()',
            'magnetometer'  => '()',
            'gyroscope'     => '()',
            'accelerometer' => '()',
        ], $config);

        $policy = [];
        foreach ($policies as $feature => $allowlist) {
            $policy[] = $feature . '=' . (string) $allowlist;
        }

        return \implode(', ', $policy);
    }
}
