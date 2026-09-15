<?php

declare(strict_types=1);

namespace App\Shared\Core\Utility;

/**
 * Date Utility Functions.
 *
 * Utility functions for date handling, focused on search-filter ranges
 * and database formats. Accepts any input supported by Types::dateTime()
 * (DateTimeInterface, int timestamp, parseable string).
 */
final class Dates
{
    public const DB_DATE = 'Y-m-d';
    public const DB_DATETIME = 'Y-m-d H:i:s';

    /**
     * Full-day bounds for a single date, useful for BETWEEN filters.
     * '2024-01-31' → ['2024-01-31 00:00:00', '2024-01-31 23:59:59']
     *
     * @return array{string, string}|null
     */
    public static function dayRange(mixed $date): ?array
    {
        $dt = Types::dateTime($date);

        if ($dt === null) {
            return null;
        }

        return [
            $dt->format('Y-m-d 00:00:00'),
            $dt->format('Y-m-d 23:59:59'),
        ];
    }

    /**
     * Inclusive full-day bounds between two dates.
     * ('2024-01-01', '2024-01-31') → ['2024-01-01 00:00:00', '2024-01-31 23:59:59']
     *
     * @return array{string, string}|null
     */
    public static function range(mixed $from, mixed $to): ?array
    {
        $start = Types::dateTime($from);
        $end = Types::dateTime($to);

        if ($start === null || $end === null) {
            return null;
        }

        return [
            $start->format('Y-m-d 00:00:00'),
            $end->format('Y-m-d 23:59:59'),
        ];
    }

    /**
     * Full-month bounds covering the month of the given date.
     * '2024-02-10' → ['2024-02-01 00:00:00', '2024-02-29 23:59:59']
     *
     * @return array{string, string}|null
     */
    public static function monthRange(mixed $month): ?array
    {
        $dt = Types::dateTime($month);

        if ($dt === null) {
            return null;
        }

        return [
            $dt->format('Y-m-01 00:00:00'),
            $dt->format('Y-m-t 23:59:59'),
        ];
    }

    /**
     * Convert a value to the database date format 'Y-m-d'.
     */
    public static function toDbDate(mixed $value, ?string $default = null): ?string
    {
        return Types::date($value, $default);
    }

    /**
     * Convert a value to the database datetime format 'Y-m-d H:i:s'.
     */
    public static function toDbDateTime(mixed $value, ?string $default = null): ?string
    {
        return Types::dateTimeString($value, self::DB_DATETIME, $default);
    }
}
