<?php
declare(strict_types=1);

use App\Infrastructure\Security\Actor;

return [

    'brand.list' => static fn () => true,

    'brand.view' => static fn (Actor $actor) =>
        $actor->hasRole('enterEDC', 'kasir')
        || $actor->hasRole('enterEDC', 'spv'),

    'brand.create' => static fn (Actor $actor) =>
        $actor->isAdmin('enterEDC')
        || $actor->isSuperAdmin('enterEDC'),

    'brand.update' => static fn (Actor $actor) =>
        $actor->isAdmin('enterEDC'),

    'brand.delete' => static fn (Actor $actor) =>
        $actor->isSuperAdmin('enterEDC'),
];
