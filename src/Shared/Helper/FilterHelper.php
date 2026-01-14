<?php

declare(strict_types=1);

namespace App\Shared\Helper;

use App\Shared\Exception\BadRequestException;

final class FilterHelper
{
    /**
     * @param array<string,mixed> $filters
     * @param string[]            $allowedKeys
     *
     * @return array<string,mixed>
     */
    public static function onlyAllowed(array $filters, array $allowedKeys): array
    {
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
