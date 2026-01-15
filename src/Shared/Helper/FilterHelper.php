<?php

declare(strict_types=1);

namespace App\Shared\Helper;

use App\Shared\Exception\BadRequestException;
use App\Shared\Request\RawParams;

/**
 * Query Helper
 * 
 * Utility class for filtering and validating query parameters
 * Following DDD best practices for shared helper utilities
 */
final class FilterHelper
{
    /**
     * Filter array to only include allowed keys
     * 
     * @param array<string,mixed>|RawParams $filters
     * @param string[]            $allowedKeys
     *
     * @return array<string,mixed>|RawParams
     */
    public static function onlyAllowed(array|RawParams $filters, array $allowedKeys): array|RawParams
    {
        // Handle RawParams object
        if ($filters instanceof RawParams) {
            $filterArray = $filters->toArray();
            $unknown = self::unknownKeys($filterArray, $allowedKeys);
            
            if ($unknown !== []) {
                throw new BadRequestException(
                    translate: [
                        'key' => 'filter.invalid_keys',
                        'params' => [
                            'keys' => implode(', ', $unknown)
                        ]
                    ]
                );
            }

            $allowedData = \array_intersect_key($filterArray, \array_flip($allowedKeys));
            return new RawParams($allowedData);
        }

        // Handle array input (backward compatibility)
        $unknown = self::unknownKeys($filters, $allowedKeys);
        if ($unknown !== []) {
            throw new BadRequestException(
                translate: [
                    'key' => 'filter.invalid_keys',
                    'params' => [
                        'keys' => implode(', ', $unknown)
                    ]
                ]
            );
        }

        return \array_intersect_key($filters, \array_flip($allowedKeys));
    }

    /**
     * @param array<string,mixed> $filters
     * @param string[]            $allowedKeys
     *
     * @return string[]
     */
    public static function unknownKeys(array $filters, array $allowedKeys): array
    {
        $unknown = \array_diff(\array_keys($filters), $allowedKeys);
        \sort($unknown);

        return $unknown;
    }
}
