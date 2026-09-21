<?php

declare(strict_types=1);

namespace App\Shared\Core\Utility;

/**
 * Identifier Utility Functions.
 *
 * Generators and validators for unique identifiers: UUID v4, ULID,
 * NanoID-style random ids, and MongoDB ObjectId (pure PHP, no extension
 * required).
 */
final class Ids
{
    private const ULID_ALPHABET = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';
    private const NANOID_ALPHABET = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ_-';

    private static ?int $objectIdCounter = null;

    /**
     * Generate a random UUID v4.
     */
    public static function uuid4(): string
    {
        $bytes = \random_bytes(16);
        $bytes[6] = \chr((\ord($bytes[6]) & 0x0F) | 0x40);
        $bytes[8] = \chr((\ord($bytes[8]) & 0x3F) | 0x80);

        $hex = \bin2hex($bytes);

        return \substr($hex, 0, 8) . '-'
            . \substr($hex, 8, 4) . '-'
            . \substr($hex, 12, 4) . '-'
            . \substr($hex, 16, 4) . '-'
            . \substr($hex, 20);
    }

    /**
     * Generate a ULID (26 chars, Crockford base32): 10 timestamp chars
     * followed by 16 random chars. Lexicographically sortable by time.
     */
    public static function ulid(?int $timestampMs = null): string
    {
        $time = $timestampMs ?? (int) (\microtime(true) * 1000);

        // 48-bit timestamp → 10 base32 chars (50-bit capacity, top 2 bits zero)
        $timeBits = \substr(\str_pad(\decbin($time), 50, '0', STR_PAD_LEFT), -50);
        $ulid = '';

        for ($i = 0; $i < 10; $i++) {
            $ulid .= self::ULID_ALPHABET[\bindec(\substr($timeBits, $i * 5, 5))];
        }

        // 80-bit randomness → 16 base32 chars
        $randomBits = '';

        foreach (\str_split(\random_bytes(10)) as $byte) {
            $randomBits .= \str_pad(\decbin(\ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        for ($i = 0; $i < 16; $i++) {
            $ulid .= self::ULID_ALPHABET[\bindec(\substr($randomBits, $i * 5, 5))];
        }

        return $ulid;
    }

    /**
     * Generate a NanoID-style random id (default 21 chars, URL-safe).
     */
    public static function nanoid(int $length = 21, string $alphabet = self::NANOID_ALPHABET): string
    {
        $max = \strlen($alphabet) - 1;
        $id = '';

        for ($i = 0; $i < $length; $i++) {
            $id .= $alphabet[\random_int(0, $max)];
        }

        return $id;
    }

    /**
     * Generate a MongoDB ObjectId hex string (24 chars) in pure PHP:
     * 4-byte timestamp + 5-byte random + 3-byte counter.
     */
    public static function objectId(): string
    {
        if (self::$objectIdCounter === null) {
            self::$objectIdCounter = \random_int(0, 0xFFFFFF);
        }

        self::$objectIdCounter = (self::$objectIdCounter + 1) & 0xFFFFFF;

        return \bin2hex(
            \pack('N', \time())
            . \random_bytes(5)
            . \substr(\pack('N', self::$objectIdCounter), 1)
        );
    }

    /**
     * Check whether a value is a valid MongoDB ObjectId hex string.
     */
    public static function isObjectId(mixed $value): bool
    {
        return \is_string($value) && \preg_match('/^[0-9a-fA-F]{24}$/', $value) === 1;
    }

    /**
     * Check whether a value is a valid UUID string.
     */
    public static function isUuid(mixed $value): bool
    {
        return Types::uuid($value) !== null;
    }
}
