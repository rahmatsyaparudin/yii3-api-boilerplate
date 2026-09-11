<?php

declare(strict_types=1);

namespace App\Infrastructure\Core\Security;

// Infrastructure Layer

/**
 * Interface ini menandakan bahwa sebuah class membutuhkan akses ke CurrentUser.
 * Framework akan otomatis memanggil setCurrentUser() jika class mengimplementasikan ini.
 */
interface CurrentUserAwareInterface
{
    public function setCurrentUser(CurrentUser $currentUser): void;
}
