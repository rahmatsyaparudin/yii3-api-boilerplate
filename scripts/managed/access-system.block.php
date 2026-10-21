    // === SKELETON MANAGED PERMISSIONS — do not edit between markers ===
    // System module permissions. Synced by `composer skeleton:update`;
    // FQCN is used so the block is self-contained and needs no `use`
    // statements in this file.
    'system.service-token.list'   => static fn (\App\Infrastructure\Core\Security\Actor $actor): bool => true,
    'system.service-token.view'   => static fn (\App\Infrastructure\Core\Security\Actor $actor): bool => true,
    'system.service-token.create' => static fn (\App\Infrastructure\Core\Security\Actor $actor): bool => true,
    'system.service-token.update' => static fn (\App\Infrastructure\Core\Security\Actor $actor): bool => true,
    'system.service-token.delete' => static fn (\App\Infrastructure\Core\Security\Actor $actor): bool => true,
    'system.cache.purge'          => static fn (\App\Infrastructure\Core\Security\Actor $actor): bool => true,
    // === END SKELETON MANAGED PERMISSIONS ===
