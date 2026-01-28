<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

// Infrastructure Layer
use App\Infrastructure\Security\CurrentUser;

/**
 * Interface ini menandakan bahwa sebuah class membutuhkan akses ke CurrentUser.
 * Framework akan otomatis memanggil setCurrentUser() jika class mengimplementasikan ini.
 */
interface CurrentUserAwareInterface
{
    public function setCurrentUser(CurrentUser $currentUser): void;
}