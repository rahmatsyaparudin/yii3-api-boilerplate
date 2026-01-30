<?php

declare(strict_types=1);

namespace App\Domain\--help\Service;

// Domain Layer
use App\Domain\--help\Entity\--help;
use App\Domain\--help\Repository\--helpRepositoryInterface;
use App\Domain\Shared\Concerns\Service\DomainValidator;
use App\Domain\Shared\ValueObject\Status;

// Shared Layer
use App\Shared\Exception\ConflictException;
use App\Shared\ValueObject\Message;

/**
 * --help Domain Service
 * 
 * Pure business logic and domain rules
 */
final class --helpDomainService
{
    use DomainValidator;
    
    public function __construct(
        private --helpRepositoryInterface $repository
    ) {}
    
    
}
