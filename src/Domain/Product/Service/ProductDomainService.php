<?php

declare(strict_types=1);

namespace App\Domain\Product\Service;

// Domain Layer
use App\Domain\Product\Entity\Product;
use App\Domain\Product\Repository\ProductRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\Status;

// Shared Layer
use App\Shared\Exception\ConflictException;
use App\Shared\ValueObject\Message;

/**
 * Product Domain Service
 * 
 * Pure business logic and domain rules
 */
final class ProductDomainService
{
    use DomainValidator;
    
    public function __construct(
        private ProductRepositoryInterface $repository
    ) {}
    
    
}
