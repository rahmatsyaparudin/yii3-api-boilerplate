<?php

declare(strict_types=1);

namespace App\Domain\Example\Service;

// Domain Layer
use App\Domain\Example\Entity\Example;
use App\Domain\Example\Repository\ExampleRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\Status;

// Shared Layer
use App\Shared\Exception\ConflictException;
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
    
    
}
