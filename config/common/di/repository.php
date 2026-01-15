<?php

declare(strict_types=1);

use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Brand\Application\BrandValidator;
use App\Domain\Brand\Application\BrandInputValidator;
use App\Infrastructure\Persistence\Brand\DbBrandRepository;
use App\Shared\Validation\UniqueFieldValidator;
use Yiisoft\Translator\TranslatorInterface;

return [
    // Interface → Implementasi
    BrandRepositoryInterface::class => DbBrandRepository::class,
    
    // Global Validators
    UniqueFieldValidator::class => static function (TranslatorInterface $translator) {
        return new UniqueFieldValidator($translator);
    },
    
    // Application Services
    BrandValidator::class => static function (BrandRepositoryInterface $repository, UniqueFieldValidator $uniqueFieldValidator, TranslatorInterface $translator) {
        return new BrandValidator($repository, $uniqueFieldValidator, $translator);
    },
    
    BrandInputValidator::class => BrandInputValidator::class,
];
