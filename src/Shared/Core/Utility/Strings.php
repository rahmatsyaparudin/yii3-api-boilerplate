<?php

declare(strict_types=1);

namespace App\Shared\Core\Utility;

/**
 * String Utility Functions.
 *
 * Utility functions for string manipulation that can be used globally
 * throughout the application: case conversion, slugs, truncation,
 * masking of sensitive values, and random string generation.
 */
final class Strings
{
    public const ALPHANUMERIC = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const ALPHA = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    public const NUMERIC = '0123456789';

    /**
     * Convert a string to a URL-friendly slug.
     * 'Héllo Wörld!' → 'hello-world'
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        $transliterated = \iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        if ($transliterated !== false) {
            $value = $transliterated;
        }

        // iconv may transliterate accents as 'e / `a — drop the quotes
        $value = \str_replace(["'", '"', '`'], '', $value);
        $value = \strtolower($value);
        $value = (string) \preg_replace('/[^a-z0-9]+/', $separator, $value);

        return \trim($value, $separator);
    }

    /**
     * Convert a string to camelCase. 'user_name' → 'userName'
     */
    public static function camel(string $value): string
    {
        return \lcfirst(self::pascal($value));
    }

    /**
     * Convert a string to PascalCase. 'user_name' → 'UserName'
     */
    public static function pascal(string $value): string
    {
        $value = \str_replace(['-', '_'], ' ', $value);

        return \str_replace(' ', '', \ucwords($value));
    }

    /**
     * Convert a string to snake_case. 'userName' / 'user-name' → 'user_name'
     */
    public static function snake(string $value, string $separator = '_'): string
    {
        $value = (string) \preg_replace('/([a-z\d])([A-Z])/', '$1' . $separator . '$2', $value);
        $value = (string) \preg_replace('/[\s\-]+/', $separator, $value);

        return \strtolower($value);
    }

    /**
     * Convert a string to kebab-case. 'userName' → 'user-name'
     */
    public static function kebab(string $value): string
    {
        return self::snake($value, '-');
    }

    /**
     * Truncate a string to a maximum length, appending $suffix when cut.
     */
    public static function truncate(string $value, int $length, string $suffix = '...'): string
    {
        if (\mb_strlen($value) <= $length) {
            return $value;
        }

        return \mb_substr($value, 0, \max(0, $length - \mb_strlen($suffix))) . $suffix;
    }

    /**
     * Mask a string, keeping the first $showStart and last $showEnd
     * characters visible. '08123456789' → '08*******89'
     */
    public static function mask(string $value, int $showStart = 2, int $showEnd = 2, string $char = '*'): string
    {
        $length = \mb_strlen($value);

        if ($length <= $showStart + $showEnd) {
            return \str_repeat($char, $length);
        }

        $start = $showStart > 0 ? \mb_substr($value, 0, $showStart) : '';
        $end = $showEnd > 0 ? \mb_substr($value, -$showEnd) : '';

        return $start . \str_repeat($char, $length - $showStart - $showEnd) . $end;
    }

    /**
     * Mask the local part of an email address.
     * 'john.doe@example.com' → 'jo*******@example.com'
     */
    public static function maskEmail(string $email): string
    {
        $atPos = \strrpos($email, '@');

        if ($atPos === false) {
            return self::mask($email);
        }

        return self::mask(\substr($email, 0, $atPos), 2, 0) . \substr($email, $atPos);
    }

    /**
     * Mask a phone number, keeping only the last digits visible.
     * '08123456789' → '*******6789'
     */
    public static function maskPhone(string $phone, int $showEnd = 4): string
    {
        return self::mask($phone, 0, $showEnd);
    }

    /**
     * Generate a cryptographically random string from the given alphabet.
     */
    public static function random(int $length, string $alphabet = self::ALPHANUMERIC): string
    {
        $max = \strlen($alphabet) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $alphabet[\random_int(0, $max)];
        }

        return $result;
    }

    /**
     * Get the keywords that appear in the text (case-insensitive).
     * Returns the matched keywords in the order given; empty keywords
     * are skipped.
     *
     * @return string[]
     */
    public static function matchingKeywords(string $text, array $keywords): array
    {
        $found = [];

        foreach ($keywords as $word) {
            $word = (string) $word;
            if ($word !== '' && \stripos($text, $word) !== false) {
                $found[] = $word;
            }
        }

        return $found;
    }

    /**
     * Same as matchingKeywords() but returns the matches joined into a string.
     */
    public static function matchingKeywordsString(string $text, array $keywords, string $separator = ', '): string
    {
        return \implode($separator, self::matchingKeywords($text, $keywords));
    }
}
