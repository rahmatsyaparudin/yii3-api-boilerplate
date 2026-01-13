<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Shared\Exception\UnauthorizedException;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

final class JwtService
{
    public function __construct(
        private string $secret,
        private string $algo = 'HS256',
        private ?string $issuer = null,
        private ?string $audience = null
    ) {
    }

    public function decode(string $token): object
    {
        $decoded = JWT::decode($token, new Key($this->secret, $this->algo));

        // Validate issuer if configured
        if ($this->issuer !== null && isset($decoded->iss) && $decoded->iss !== $this->issuer) {
            throw new UnauthorizedException(
                translate: [
                    'key' => 'auth.invalid_issuer',
                    'params' => []
                ]
            );
        }

        // Validate audience if configured
        if ($this->audience !== null && isset($decoded->aud) && $decoded->aud !== $this->audience) {
            throw new UnauthorizedException(
                translate: [
                    'key' => 'auth.invalid_audience',
                    'params' => []
                ]
            );
        }

        return $decoded;
    }
}
