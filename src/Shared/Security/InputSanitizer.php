<?php

declare(strict_types=1);

namespace App\Shared\Security;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * InputSanitizer
 *
 * - Normalize user input safely
 * - Prevent common XSS vectors
 * - Prevent SQL injection patterns
 * - Validate encoding and length
 * - DO NOT escape HTML (escape on output!)
 */
final class InputSanitizer
{
    private const MAX_STRING_LENGTH = 65535; // 64KB
    private const MAX_ARRAY_DEPTH = 10;
    private const MAX_ARRAY_SIZE = 1000;

    /**
     * Process request input
     */
    public static function process(array $input): array
    {
        return self::value($input, 0);
    }

    /**
     * Process single value
     */
    private static function value(mixed $value, int $depth): mixed
    {
        // Prevent deep recursion attacks
        if ($depth > self::MAX_ARRAY_DEPTH) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.input_structure_too_deep',
                    domain: 'validation',
                    params: [
                        'depth' => $depth,
                        'max_depth' => self::MAX_ARRAY_DEPTH,
                    ]
                )
            );
        }

        if (is_string($value)) {
            return self::string($value);
        }

        if (is_array($value)) {
            return self::array($value, $depth);
        }

        // Handle other types (int, float, bool, null)
        return self::scalar($value);
    }

    /**
     * Process string values with security checks
     */
    private static function string(string $value): ?string
    {
        // Length check to prevent DoS
        if (strlen($value) > self::MAX_STRING_LENGTH) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.input_too_long',
                    domain: 'validation',
                    params: [
                        'length' => strlen($value),
                        'max_length' => self::MAX_STRING_LENGTH,
                    ]
                )
            );
        }

        // Remove null bytes and control characters
        $value = self::stripInvisibleChars($value);
        
        // Validate UTF-8 encoding
        if (!mb_check_encoding($value, 'UTF-8')) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.invalid_encoding',
                    domain: 'validation',
                    params: [
                        'encoding' => 'UTF-8',
                    ]
                )
            );
        }

        // Trim whitespace
        $value = trim($value);
        
        // Convert empty string to null
        if ($value === '') {
            return null;
        }

        // Security checks - log warnings instead of throwing exceptions for development
        if (self::containsXssPayload($value)) {
            error_log('XSS pattern detected in input: ' . substr($value, 0, 100));
            // Remove suspicious content instead of throwing exception
            $value = self::removeSuspiciousContent($value);
        }

        if (self::containsSqlInjection($value)) {
            error_log('SQL injection pattern detected in input: ' . substr($value, 0, 100));
            // Remove suspicious content instead of throwing exception
            $value = self::removeSuspiciousContent($value);
        }

        return $value;
    }

    /**
     * Process array values
     */
    private static function array(array $value, int $depth): array
    {
        // Array size check to prevent DoS
        if (count($value) > self::MAX_ARRAY_SIZE) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.array_too_large',
                    domain: 'validation',
                    params: [
                        'max_size' => self::MAX_ARRAY_SIZE,
                    ]
                )
            );
        }

        $processed = [];
        foreach ($value as $key => $item) {
            // Validate array keys
            $key = self::key($key);
            $processed[$key] = self::value($item, $depth + 1);
        }

        return $processed;
    }

    /**
     * Process scalar values
     */
    private static function scalar(mixed $value): mixed
    {
        if (is_int($value)) {
            // Check for integer overflow
            if ($value > PHP_INT_MAX || $value < PHP_INT_MIN) {
                throw new BadRequestException(
                    translate: new Message(
                        key: 'input_sanitizer.integer_overflow',
                        domain: 'validation',
                        params: [
                            'value' => $value,
                            'max_value' => PHP_INT_MAX,
                            'min_value' => PHP_INT_MIN,
                        ]
                    )
                );
            }
            return $value;
        }

        if (is_float($value)) {
            // Check for float overflow
            if (!is_finite($value)) {
                throw new BadRequestException(
                    translate: new Message(
                        key: 'input_sanitizer.float_overflow',
                        domain: 'validation',
                        params: [
                            'max_value' => PHP_FLOAT_MAX,
                            'min_value' => PHP_FLOAT_MIN,
                        ]
                    )
                );
            }
            return $value;
        }

        if (is_bool($value) || $value === null) {
            return $value;
        }

        // Convert other types to string and process
        return self::string((string) $value);
    }

    /**
     * Process array keys
     */
    private static function key(mixed $key): string
    {
        if (!is_string($key) && !is_int($key)) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.invalid_array_key_type',
                    domain: 'validation',
                    params: [
                        'key' => $key,
                    ]
                )
            );
        }

        $key = (string) $key;
        
        // Key length check
        if (strlen($key) > 255) {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.invalid_array_key_length',
                    domain: 'validation',
                    params: [
                        'max_length' => 255,
                    ]
                )
            );
        }

        // Remove dangerous characters from keys
        $key = preg_replace('/[^\w\-\.]/', '', $key) ?? '';
        
        if ($key === '') {
            throw new BadRequestException(
                translate: new Message(
                    key: 'input_sanitizer.invalid_array_key',
                    domain: 'validation',
                    params: [
                        'key' => $key,
                    ]
                )
            );
        }

        return $key;
    }

    /**
     * Remove invisible / control characters and null bytes
     */
    private static function stripInvisibleChars(string $value): string
    {
        // Remove null bytes, control characters, and invisible Unicode chars
        return preg_replace('/[\x00-\x1F\x7F\xC2\xA0\xE2\x80\xA8\xE2\x80\xA9]/u', '', $value) ?? '';
    }

    /**
     * Enhanced XSS detection
     */
    private static function containsXssPayload(string $value): bool
    {
        // Comprehensive XSS patterns
        $xssPatterns = [
            // Script tags and JavaScript
            '/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is',
            '/javascript\s*:/i',
            '/vbscript\s*:/i',
            '/on\w+\s*=/i',
            
            // HTML tags that can execute JavaScript
            '/<\s*iframe/i',
            '/<\s*object/i',
            '/<\s*embed/i',
            '/<\s*link/i',
            '/<\s*meta/i',
            '/<\s*svg/i',
            '/<\s*img[^>]*src\s*=\s*["\']?\s*javascript:/i',
            
            // Data URLs
            '/data\s*:\s*text\/html/i',
            '/data\s*:\s*image\/svg\+xml/i',
            
            // CSS expressions
            '/expression\s*\(/i',
            '/@import/i',
            '/behavior\s*:/i',
            
            // Common attack vectors
            '/<\?xml/i',
            '/<\!DOCTYPE/i',
            '/<\!\[CDATA\[/i',
        ];

        foreach ($xssPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove suspicious content from input
     */
    private static function removeSuspiciousContent(string $value): string
    {
        // Remove script tags and JavaScript
        $value = preg_replace('/<\s*script[^>]*>.*?<\s*\/\s*script\s*>/is', '', $value);
        $value = preg_replace('/javascript\s*:/i', '', $value);
        $value = preg_replace('/vbscript\s*:/i', '', $value);
        $value = preg_replace('/on\w+\s*=/i', '', $value);
        
        // Remove dangerous HTML tags
        $value = preg_replace('/<\s*(iframe|object|embed|link|meta|svg)[^>]*>/i', '', $value);
        $value = preg_replace('/<\s*img[^>]*src\s*=\s*["\']?\s*javascript:/i', '', $value);
        
        // Remove SQL injection patterns
        $value = preg_replace('/union\s+select/i', '', $value);
        $value = preg_replace('/--/', '', $value);
        $value = preg_replace('/\/\*/', '', $value);
        $value = preg_replace('/\*\//', '', $value);
        $value = preg_replace('/#/', '', $value);
        $value = preg_replace('/\b(and|or)\b.*\b(=|like|in)\b/i', '', $value);
        $value = preg_replace('/sleep\s*\(/i', '', $value);
        $value = preg_replace('/benchmark\s*\(/i', '', $value);
        
        // Clean up any remaining suspicious patterns
        $value = preg_replace('/[<>"\']/', '', $value);
        
        return trim($value);
    }

    /**
     * SQL injection detection
     */
    private static function containsSqlInjection(string $value): bool
    {
        // Common SQL injection patterns
        $sqlPatterns = [
            // Union attacks
            '/union\s+select/i',
            '/union\s+all\s+select/i',
            
            // Comment attacks
            '/--/',
            '/\/\*/',
            '/\*\//',
            '/#/',
            
            // Boolean-based attacks
            '/\b(and|or)\b.*\b(=|like|in)\b/i',
            '/\b(and|or)\b\s+\d+\s*=\s*\d+/i',
            
            // Time-based attacks
            '/sleep\s*\(/i',
            '/benchmark\s*\(/i',
            '/waitfor\s+delay/i',
            
            // Database functions
            '/\b(concat|substring|ascii|char|length|version)\s*\(/i',
            
            // Quote escaping
            '/["\']\s*["\']/',
            '/["\']\s*(or|and)\s*["\']/',
            
            // Hex encoding
            '/0x[0-9a-f]+/i',
            
            // Common payloads
            '/\b(select|insert|update|delete|drop|create|alter|exec|execute)\b/i',
        ];

        foreach ($sqlPatterns as $pattern) {
            if (preg_match($pattern, $value)) {
                return true;
            }
        }

        return false;
    }
}
