<?php

declare(strict_types=1);

namespace App\Shared\Request;

/**
 * Simple Data Parser Interface
 * 
 * Provides a flexible interface for parsing request data
 * without requiring full PSR implementation
 */
interface DataParserInterface
{
    /**
     * Get all parsed data as array
     *
     * @return array<array-key, mixed>
     */
    public function all(): array;

    /**
     * Get specific data value with default
     *
     * @param string $key The data key
     * @param mixed $default Default value if key doesn't exist
     * @return mixed
     */
    public function get(string $key, mixed $default = null): mixed;
}
