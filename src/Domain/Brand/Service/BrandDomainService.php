<?php

declare(strict_types=1);

namespace App\Domain\Brand\Service;

use App\Domain\Brand\Entity\Brand;
use App\Domain\Brand\Repository\BrandRepositoryInterface;
use App\Domain\Shared\ValueObject\Status;
use App\Shared\ValueObject\Message;
use App\Shared\Exception\ConflictException;
use App\Domain\Shared\Concerns\Service\DomainValidator;

/**
 * Brand Domain Service
 * 
 * Pure business logic and domain rules
 */
final class BrandDomainService
{
    use DomainValidator;
    
    public function __construct(
        private BrandRepositoryInterface $repository
    ) {}
    
    
}
