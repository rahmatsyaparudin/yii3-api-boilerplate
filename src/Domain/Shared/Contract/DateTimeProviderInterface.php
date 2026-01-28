<?php

declare(strict_types=1);

namespace App\Domain\Shared\Contract;

// Vendor Layer
use DateTimeImmutable;

interface DateTimeProviderInterface
{
    /** Format standar Object Menghasilkan format DateTimeImmutable */
    public function object(): DateTimeImmutable;

    /** Format standar Database Menghasilkan format Y-m-d H:i:s */
    public function database(): string;

    /** Format standar ISO 8601 (Zulu) Menghasilkan format ISO 8601 */
    public function iso8601(): string;
}