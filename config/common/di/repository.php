<?php

declare(strict_types=1);

use App\Domain\Brand\BrandRepositoryInterface;
use App\Infrastructure\Persistence\Brand\DbBrandRepository;

return [
    // Interface → Implementasi
    BrandRepositoryInterface::class => DbBrandRepository::class,
];
