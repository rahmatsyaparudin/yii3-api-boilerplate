<?php

declare(strict_types=1);

namespace App\Shared\Validation;

use App\Shared\Exception\BadRequestException;
use App\Shared\Request\RawParams;
use App\Shared\ValueObject\Message;

/**
 * Request Validator
 * 
 * Menyediakan validasi untuk request parameters menggunakan Yii validator
 */
class RequestValidator
{
    public function __construct() {}

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
                    translate: new Message(
                        key: 'request.unknown_parameters',
                        domain: 'validation',
                        params: [
                            'unknown_keys' => implode(', ', $unknown),
                            'allowed_keys' => implode(', ', $allowedKeys),
                        ]
                    )
                );
            }

            $allowedData = \array_intersect_key($filterArray, \array_flip($allowedKeys));
            return new RawParams($allowedData);
        }

        // Handle array input (backward compatibility)
        $unknown = self::unknownKeys($filters, $allowedKeys);
        
        if ($unknown !== []) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'request.unknown_parameters',
                    domain: 'validation',
                    params: [
                        'unknown_keys' => implode(', ', $unknown),
                        'allowed_keys' => implode(', ', $allowedKeys),
                    ]
                )
            );
        }
        
        return \array_intersect_key($filters, \array_flip($allowedKeys));
    }

    /**
     * Get unknown keys from filters
     */
    private static function unknownKeys(array $filters, array $allowedKeys): array
    {
        $unknown = \array_diff(\array_keys($filters), $allowedKeys);
        \sort($unknown);

        return $unknown;
    }
}
