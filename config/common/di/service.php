<?php

declare(strict_types=1);

use App\Domain\Brand\BrandService;
use App\Infrastructure\Time\SystemTimestampProvider;
use App\Shared\Contract\TimestampProviderInterface;
use App\Shared\Helper\DetailInfoHelper;

return [
    // Interface → Implementasi
    BrandService::class               => BrandService::class,
    DetailInfoHelper::class           => DetailInfoHelper::class,
    TimestampProviderInterface::class => SystemTimestampProvider::class,
];
