<?php
declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Common\Audit\Actor;
use App\Shared\Exception\UnauthorizedException;

final class ActorProvider
{
    public function fromToken(object $claims): Actor
    {
        // Handle custom SSO structure with user object
        if (isset($claims->user) && is_object($claims->user)) {
            if (!isset($claims->user->username)) {
                throw new UnauthorizedException('JWT missing user.username claim');
            }
            return new Actor(username: $claims->user->username);
        }
        
        // Fallback to standard preferred_username claim
        if (isset($claims->preferred_username)) {
            return new Actor(username: $claims->preferred_username);
        }
        
        throw new UnauthorizedException('JWT missing username claim');
    }
}
