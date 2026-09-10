<?php

declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Stateless helper for generic row field transformations.
 */
final class FieldMapper
{
    /**
     * Rename keys in a row.
     *
     * @param array $row Original row.
     * @param array<string, string> $map Map of old key => new key.
     */
    public static function rename(array $row, array $map): array
    {
        foreach ($map as $from => $to) {
            if (array_key_exists($from, $row)) {
                $row[$to] = $row[$from];
                unset($row[$from]);
            }
        }

        return $row;
    }

    /**
     * Convert zero or '0' values to null for the given columns.
     *
     * @param array $row Original row.
     * @param string[] $columns Column names to check.
     */
    public static function nullWhenZero(array $row, array $columns): array
    {
        foreach ($columns as $column) {
            if (array_key_exists($column, $row) && ($row[$column] === 0 || $row[$column] === '0')) {
                $row[$column] = null;
            }
        }

        return $row;
    }

    /**
     * Apply rename then null-when-zero in one call.
     *
     * @param array $row Original row.
     * @param array<string, string> $rename Map of old key => new key.
     * @param string[] $nullWhenZero Column names (after rename) to null when zero.
     */
    public static function map(array $row, array $rename, array $nullWhenZero = []): array
    {
        $row = self::rename($row, $rename);

        if ($nullWhenZero !== []) {
            $row = self::nullWhenZero($row, $nullWhenZero);
        }

        return $row;
    }
}
