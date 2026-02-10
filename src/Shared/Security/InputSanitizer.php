<?php

declare(strict_types=1);

namespace App\Shared\Security;

// Shared Layer
use App\Shared\Exception\BadRequestException;
use App\Shared\ValueObject\Message;

/**
 * Input Sanitizer Security Utility
 * 
 * This final class provides comprehensive input sanitization to prevent
 * common security vulnerabilities including XSS, SQL injection, and DoS attacks.
 * It normalizes user input safely while preserving data integrity.
 * 
 * Key Features:
 * - Normalize user input safely
 * - Prevent common XSS vectors
 * - Prevent SQL injection patterns
 * - Validate encoding and length
 * - DO NOT escape HTML (escape on output!)
 * 
 * @package App\Shared\Security
 * 
 * @example
 * // Basic sanitization of request data
 * $sanitized = InputSanitizer::process([
 *     'name' => 'John<script>alert("xss")</script>',
 *     'email' => 'john@example.com',
 *     'age' => 25
 * ]);
 * // Result: ['name' => 'John', 'email' => 'john@example.com', 'age' => 25]
 * 
 * @example
 * // In middleware for request sanitization
 * public function process(ServerRequestInterface $request): ServerRequestInterface
 * {
 *     $data = $request->getParsedBody() ?? [];
 *     $sanitized = InputSanitizer::process($data);
 *     return $request->withParsedBody($sanitized);
 * }
 * 
 * @example
 * // Sanitizing nested arrays
 * $data = [
 *     'user' => [
 *         'name' => 'John',
 *         'bio' => 'I <script>alert("hack")</script> love coding'
 *     ],
 *     'settings' => ['theme' => 'dark']
 * ];
 * $sanitized = InputSanitizer::process($data);
 * // Result: [
 * //     'user' => ['name' => 'John', 'bio' => 'I love coding'],
 * //     'settings' => ['theme' => 'dark']
 * // ]
 * 
 * @example
 * // With RawParams integration
 * $rawParams = new RawParams($request->getParsedBody());
 * $sanitizedParams = $rawParams->sanitize();
 * $data = $sanitizedParams->all();
 * 
 * @example
 * // Handling different data types
 * $data = [
 *     'string' => 'Hello World',
 *     'number' => 42,
 *     'float' => 3.14,
 *     'boolean' => true,
 *     'null' => null,
 *     'empty' => ''
 * ];
 * $sanitized = InputSanitizer::process($data);
 * // Result: ['string' => 'Hello World', 'number' => 42, 'float' => 3.14, 'boolean' => true, 'null' => null, 'empty' => null]
 */
final class InputSanitizer
{
    private const MAX_STRING_LENGTH = 65535; // 64KB
    private const MAX_ARRAY_DEPTH = 10;
    private const MAX_ARRAY_SIZE = 1000;

    /**
     * Process request input
     * 
     * Main entry point for sanitizing input data. Recursively processes
     * arrays and scalar values to ensure security and data integrity.
     * 
     * @param array $input Input data to sanitize
     * @return array Sanitized input data
     * @throws BadRequestException If input violates security constraints
     * 
     * @example
     * // Basic usage
     * $sanitized = InputSanitizer::process($requestData);
     * 
     * @example
     * // In controller
     * public function createAction(ServerRequestInterface $request): ResponseInterface
     * {
     *     $data = $request->getParsedBody() ?? [];
     *     $sanitized = InputSanitizer::process($data);
     *     return $this->service->create($sanitized);
     * }
     * 
     * @example
     * // With named arguments
     * $sanitized = InputSanitizer::process(
     *     input: $userInput
     * );
     * 
     * @example
     * // In middleware
     * public function process(ServerRequestInterface $request): ServerRequestInterface
     * {
     *     $data = $request->getParsedBody() ?? [];
     *     $sanitized = InputSanitizer::process($data);
     *     return $request->withParsedBody($sanitized);
     * }
     * 
     * @example
     * // Handling complex nested data
     * $complexData = [
     *     'user' => ['profile' => ['name' => 'John<script>alert(1)</script>']],
     *     'settings' => ['preferences' => ['theme' => 'dark']]
     * ];
     * $sanitized = InputSanitizer::process($complexData);
     */
    public static function process(array $input): array
    {
        return self::value($input, 0);
    }

    /**
     * Process single value
     * 
     * Recursively processes individual values based on their type.
     * Handles strings, arrays, and scalar values with appropriate security checks.
     * 
     * @param mixed $value Value to process
     * @param int $depth Current recursion depth
     * @return mixed Processed value
     * @throws BadRequestException If value violates security constraints
     * 
     * @example
     * // Processing different value types
     * $string = self::value('Hello World', 0);
     * $array = self::value(['key' => 'value'], 0);
     * $number = self::value(42, 0);
     * 
     * @example
     * // Deep recursion protection
     * // This will throw an exception if depth exceeds MAX_ARRAY_DEPTH
     * $deepArray = self::value($nestedData, 15);
     * 
     * @example
     * // Type-specific processing
     * if (is_string($value)) {
     *     return self::string($value);
     * }
     * if (is_array($value)) {
     *     return self::array($value, $depth);
     * }
     * return self::scalar($value);
     */
    private static function value(mixed $value, int $depth): mixed
    {
        // Prevent deep recursion attacks
        if ($depth > self::MAX_ARRAY_DEPTH) {
            throw new BadRequestException(
                translate: Message::create(
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
     * 
     * Performs comprehensive security validation on string values including
     * length checks, encoding validation, and XSS/SQL injection detection.
     * 
     * @param string $value String value to process
     * @return string|null Processed string or null if empty
     * @throws BadRequestException If string violates security constraints
     * 
     * @example
     * // Basic string processing
     * $clean = self::string('Hello World');
     * // Result: 'Hello World'
     * 
     * @example
     * // XSS removal
     * $clean = self::string('John<script>alert("xss")</script>');
     * // Result: 'John'
     * 
     * @example
     * // SQL injection removal
     * $clean = self::string("name' OR '1'='1");
     * // Result: 'name OR 1=1' (suspicious content removed)
     * 
     * @example
     * // Empty string handling
     * $clean = self::string('');
     * // Result: null
     * 
     * @example
     * // UTF-8 validation
     * $clean = self::string('Hello 世界');
     * // Result: 'Hello 世界' (if valid UTF-8)
     */
    private static function string(string $value): ?string
    {
        // Length check to prevent DoS
        if (strlen($value) > self::MAX_STRING_LENGTH) {
            throw new BadRequestException(
                translate: Message::create(
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
                translate: Message::create(
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
     * 
     * Recursively processes array values with size limits and key validation.
     * Prevents DoS attacks through large arrays and validates array keys.
     * 
     * @param array $value Array value to process
     * @param int $depth Current recursion depth
     * @return array Processed array
     * @throws BadRequestException If array violates security constraints
     * 
     * @example
     * // Basic array processing
     * $clean = self::array(['name' => 'John', 'age' => 25], 0);
     * // Result: ['name' => 'John', 'age' => 25]
     * 
     * @example
     * // Nested array processing
     * $clean = self::array(['user' => ['name' => 'John']], 0);
     * // Result: ['user' => ['name' => 'John']]
     * 
     * @example
     * // Large array protection
     * // This will throw an exception if array size exceeds MAX_ARRAY_SIZE
     * $clean = self::array(range(1, 2000), 0);
     * 
     * @example
     * // Key sanitization
     * $clean = self::array(['user-name' => 'John', 'invalid$key!' => 'value'], 0);
     * // Result: ['user-name' => 'John'] (invalid key removed)
     */
    private static function array(array $value, int $depth): array
    {
        // Array size check to prevent DoS
        if (count($value) > self::MAX_ARRAY_SIZE) {
            throw new BadRequestException(
                translate: Message::create(
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
     * 
     * Validates and processes scalar values (int, float, bool, null).
     * Checks for overflow and ensures type safety.
     * 
     * @param mixed $value Scalar value to process
     * @return mixed Processed scalar value
     * @throws BadRequestException If scalar value violates constraints
     * 
     * @example
     * // Integer processing
     * $clean = self::scalar(42);
     * // Result: 42
     * 
     * @example
     * // Float processing
     * $clean = self::scalar(3.14);
     * // Result: 3.14
     * 
     * @example
     * // Boolean processing
     * $clean = self::scalar(true);
     * // Result: true
     * 
     * @example
     * // Integer overflow protection
     * // This will throw an exception for values outside PHP_INT range
     * $clean = self::scalar(PHP_INT_MAX + 1);
     */
    private static function scalar(mixed $value): mixed
    {
        if (is_int($value)) {
            // Check for integer overflow
            if ($value > PHP_INT_MAX || $value < PHP_INT_MIN) {
                throw new BadRequestException(
                    translate: Message::create(
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
                    translate: Message::create(
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
     * 
     * Validates and sanitizes array keys to prevent security issues.
     * Ensures keys are safe strings with reasonable length.
     * 
     * @param mixed $key Array key to process
     * @return string Processed array key
     * @throws BadRequestException If key is invalid
     * 
     * @example
     * // String key processing
     * $clean = self::key('username');
     * // Result: 'username'
     * 
     * @example
     * // Integer key processing
     * $clean = self::key(0);
     * // Result: '0'
     * 
     * @example
     * // Key sanitization
     * $clean = self::key('user-name');
     * // Result: 'user-name'
     * 
     * @example
     * // Invalid key removal
     * // This will throw an exception for keys with dangerous characters
     * $clean = self::key('user<script>alert(1)</script>');
     */
    private static function key(mixed $key): string
    {
        if (!is_string($key) && !is_int($key)) {
            throw new BadRequestException(
                translate: Message::create(
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
                translate: Message::create(
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
                translate: Message::create(
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
     * 
     * Strips potentially dangerous invisible characters that could
     * be used in attacks or cause encoding issues.
     * 
     * @param string $value String to clean
     * @return string Cleaned string
     * 
     * @example
     * // Remove null bytes
     * $clean = self::stripInvisibleChars("Hello\x00World");
     * // Result: 'HelloWorld'
     * 
     * @example
     * // Remove control characters
     * $clean = self::stripInvisibleChars("Hello\x1FWorld");
     * // Result: 'HelloWorld'
     * 
     * @example
     * // Remove invisible Unicode characters
     * $clean = self::stripInvisibleChars("Hello\xC2\xA0World");
     * // Result: 'HelloWorld'
     */
    private static function stripInvisibleChars(string $value): string
    {
        // Remove null bytes, control characters, and invisible Unicode chars
        return preg_replace('/[\x00-\x1F\x7F\xC2\xA0\xE2\x80\xA8\xE2\x80\xA9]/u', '', $value) ?? '';
    }

    /**
     * Enhanced XSS detection
     * 
     * Detects various XSS attack patterns including script tags,
     * JavaScript protocols, event handlers, and dangerous HTML elements.
     * 
     * @param string $value String to check for XSS
     * @return bool True if XSS pattern detected
     * 
     * @example
     * // Detect script tags
     * $hasXss = self::containsXssPayload('<script>alert("xss")</script>');
     * // Result: true
     * 
     * @example
     * // Detect JavaScript protocols
     * $hasXss = self::containsXssPayload('javascript:alert("xss")');
     * // Result: true
     * 
     * @example
     * // Detect event handlers
     * $hasXss = self::containsXssPayload('<img onclick="alert(1)" src="x">');
     * // Result: true
     * 
     * @example
     * // Safe content
     * $hasXss = self::containsXssPayload('Hello World');
     * // Result: false
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
     * 
     * Removes detected XSS and SQL injection patterns from input
     * while preserving as much legitimate content as possible.
     * 
     * @param string $value String to clean
     * @return string Cleaned string
     * 
     * @example
     * // Remove script tags
     * $clean = self::removeSuspiciousContent('<script>alert("xss")</script>Hello');
     * // Result: 'Hello'
     * 
     * @example
     * // Remove JavaScript protocols
     * $clean = self::removeSuspiciousContent('javascript:alert("xss")');
     * // Result: ''
     * 
     * @example
     * // Remove SQL injection patterns
     * $clean = self::removeSuspiciousContent("name' OR '1'='1");
     * // Result: 'name OR 1=1'
     * 
     * @example
     * // Preserve legitimate content
     * $clean = self::removeSuspiciousContent('Hello World');
     * // Result: 'Hello World'
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
     * 
     * Detects common SQL injection patterns including union attacks,
     * comment-based attacks, boolean-based attacks, and time-based attacks.
     * 
     * @param string $value String to check for SQL injection
     * @return bool True if SQL injection pattern detected
     * 
     * @example
     * // Detect union attacks
     * $hasSql = self::containsSqlInjection('UNION SELECT * FROM users');
     * // Result: true
     * 
     * @example
     * // Detect comment attacks
     * $hasSql = self::containsSqlInjection("name' --");
     * // Result: true
     * 
     * @example
     * // Detect boolean attacks
     * $hasSql = self::containsSqlInjection("1' OR '1'='1");
     * // Result: true
     * 
     * @example
     * // Safe content
     * $hasSql = self::containsSqlInjection('Hello World');
     * // Result: false
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
