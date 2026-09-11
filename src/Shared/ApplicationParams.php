<?php

declare(strict_types=1);

namespace App\Shared;

final readonly class ApplicationParams
{
    public function __construct(
        public string $name = 'My Project',
        public string $version = '1.0',
        public string $language = 'en',
        public ?string $environment = null,
    ) {
    }
}
