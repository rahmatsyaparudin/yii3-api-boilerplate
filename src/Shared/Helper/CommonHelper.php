<?php

declare(strict_types=1);

use App\Shared\Exception\BadRequestException;

/**
 * Common Helper Functions
 * 
 * Menyediakan utility functions yang sering digunakan:
 * - Array manipulation
 * - String manipulation
 * - Validation
 * - Formatting
 */
class CommonHelper
{
    /**
     * Extract value from array with default
     */
    public static function extract(array $data, string $key, mixed $default = null): mixed
    {
        return $data[$key] ?? $default;
    }

    /**
     * Check if array has all required keys
     */
    public static function hasRequiredKeys(array $data, array $requiredKeys): bool
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data) || $data[$key] === null) {
                return false;
            }
        }
        return true;
    }

    /**
     * Filter array by allowed keys
     */
    public static function filterKeys(array $data, array $allowedKeys): array
    {
        return array_intersect_key($data, array_flip($allowedKeys));
    }

    /**
     * Convert array to string with separator
     */
    public static function arrayToString(array $array, string $separator = ', '): string
    {
        return implode($separator, $array);
    }

    /**
     * Convert string to array with separator
     */
    public static function stringToArray(string $string, string $separator = ','): array
    {
        return empty($string) ? [] : explode($separator, $string);
    }

    /**
     * Generate random string
     */
    public static function randomString(int $length = 10): string
    {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        
        return $randomString;
    }

    /**
     * Generate UUID
     */
    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x0fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    /**
     * Format currency amount
     */
    public static function formatCurrency(float $amount, string $currency = 'IDR'): string
    {
        return $currency . ' ' . number_format($amount, 2, ',', '.');
    }

    /**
     * Format phone number
     */
    public static function formatPhoneNumber(string $phone): string
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Format Indonesian phone numbers
        if (strlen($phone) === 10) {
            return preg_replace('/^(\d{3})(\d{4})(\d{3})$/', '$1-$2-$3', $phone);
        } elseif (strlen($phone) === 11 && $phone[0] === '0') {
            return preg_replace('/^0(\d{3})(\d{4})(\d{3})$/', '$1-$2-$3', $phone);
        }
        
        return $phone;
    }

    /**
     * Validate email format
     */
    public static function isValidEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Validate phone number format
     */
    public static function isValidPhoneNumber(string $phone): bool
    {
        // Remove all non-numeric characters
        $phone = preg_replace('/[^0-9]/', '', $phone);
        
        // Indonesian phone numbers: 10-12 digits
        return strlen($phone) >= 10 && strlen($phone) <= 12;
    }

    /**
     * Sanitize string for safe output
     */
    public static function sanitize(string $string): string
    {
        return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Generate slug from string
     */
    public static function slugify(string $string): string
    {
        // Convert to lowercase and replace spaces with hyphens
        $slug = strtolower($string);
        $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
        
        // Remove multiple hyphens and trim
        $slug = preg_replace('/-+/', '-', $slug);
        $slug = trim($slug, '-');
        
        return $slug;
    }

    /**
     * Truncate string with ellipsis
     */
    public static function truncate(string $string, int $length = 100, string $suffix = '...'): string
    {
        if (strlen($string) <= $length) {
            return $string;
        }
        
        return substr($string, 0, $length - strlen($suffix)) . $suffix;
    }

    /**
     * Convert bytes to human readable format
     */
    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if string is JSON
     */
    public static function isJson(string $string): bool
    {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Convert array to JSON
     */
    public static function toJson(array $data, bool $pretty = false): string
    {
        $flags = $pretty ? JSON_PRETTY_PRINT : 0;
        return json_encode($data, $flags);
    }

    /**
     * Convert JSON to array
     */
    public static function fromJson(string $json): array
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException('Invalid JSON: ' . json_last_error_msg());
        }
        
        return $data;
    }

    /**
     * Get current timestamp
     */
    public static function now(): string
    {
        return date('Y-m-d H:i:s');
    }

    /**
     * Get current date
     */
    public static function today(): string
    {
        return date('Y-m-d');
    }

    /**
     * Format date
     */
    public static function formatDate(string $date, string $format = 'Y-m-d H:i:s'): string
    {
        $timestamp = strtotime($date);
        return date($format, $timestamp);
    }

    /**
     * Calculate age from birthdate
     */
    public static function calculateAge(string $birthdate): int
    {
        $birthDate = new \DateTime($birthdate);
        $today = new \DateTime();
        
        return $today->diff($birthDate)->y;
    }

    /**
     * Generate random numeric code
     */
    public static function generateCode(int $length = 6): string
    {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= rand(0, 9);
        }
        return $code;
    }
}
