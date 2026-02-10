<?php

declare(strict_types=1);

namespace App\Domain\Example\Service;

// Domain Layer
use App\Domain\Example\Entity\Example;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\Status;

// Shared Layer
use App\Shared\ValueObject\Message;

/**
 * Example Domain Service
 * 
 * Pure business logic and domain rules
 */
final class ExampleDomainService
{
    use DomainValidator;
    
    public function __construct(
        private ExampleRepositoryInterface $repository
    ) {}

    /**
     * Place Example-specific business logic here, in addition to those
     * provided by common traits/concerns (Identifiable, Stateful, etc.).
     * 
     * Examples:
     * - Complex validation rules
     * - Business calculations
     * - Domain-specific operations
     * - Cross-entity business rules
     */ 
}
