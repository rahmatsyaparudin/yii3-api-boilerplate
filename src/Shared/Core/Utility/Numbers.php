<?php

declare(strict_types=1);

namespace App\Shared\Core\Utility;

use App\Shared\Core\Exception\BadRequestException;
use App\Shared\Core\Exception\ServiceException;
use App\Shared\Core\ValueObject\Message;
use Yiisoft\Http\Status;

/**
 * Number Utility Functions.
 *
 * Precise decimal arithmetic on top of bcmath. Values are accepted as
 * int, float, or numeric string and results are returned as decimal
 * strings, safe for money calculations.
 */
final class Numbers
{
    /**
     * Add two numbers: add('0.1', '0.2') → '0.30'
     */
    public static function add(string|int|float $a, string|int|float $b, int $scale = 2): string
    {
        return \bcadd(self::num($a), self::num($b), $scale);
    }

    /**
     * Subtract: sub('10', '2.5') → '7.50'
     */
    public static function sub(string|int|float $a, string|int|float $b, int $scale = 2): string
    {
        return \bcsub(self::num($a), self::num($b), $scale);
    }

    /**
     * Multiply: mul('2.5', '4') → '10.00'
     */
    public static function mul(string|int|float $a, string|int|float $b, int $scale = 2): string
    {
        return \bcmul(self::num($a), self::num($b), $scale);
    }

    /**
     * Divide: div('10', '4') → '2.50'
     */
    public static function div(string|int|float $a, string|int|float $b, int $scale = 2): string
    {
        return \bcdiv(self::num($a), self::num($b), $scale);
    }

    /**
     * Compare two numbers. Returns -1, 0, or 1 (a < b, a = b, a > b).
     */
    public static function cmp(string|int|float $a, string|int|float $b, int $scale = 2): int
    {
        return \bccomp(self::num($a), self::num($b), $scale);
    }

    /**
     * Round half away from zero at the given scale.
     * round('1.235') → '1.24', round('-1.235') → '-1.24'
     */
    public static function round(string|int|float $value, int $scale = 2): string
    {
        $num = self::num($value);
        $half = '0.' . \str_repeat('0', $scale) . '5';

        return \bccomp($num, '0', 20) < 0
            ? \bcsub($num, $half, $scale)
            : \bcadd($num, $half, $scale);
    }

    /**
     * Absolute value: abs('-5.00') → '5.00'
     */
    public static function abs(string|int|float $value): string
    {
        return \ltrim(self::num($value), '-');
    }

    /**
     * Smallest of the given values.
     */
    public static function min(string|int|float ...$values): string
    {
        return self::edge($values, -1);
    }

    /**
     * Largest of the given values.
     */
    public static function max(string|int|float ...$values): string
    {
        return self::edge($values, 1);
    }

    /**
     * Format a number for display with thousands and decimal separators.
     * Precision is kept through bcmath — no float conversion.
     *
     * Defaults follow the Indonesian convention:
     * format('1234567.891') → '1.234.567,89'
     */
    public static function format(
        string|int|float $value,
        int $scale = 2,
        string $thousandsSeparator = '.',
        string $decimalSeparator = ','
    ): string {
        $num = self::round($value, $scale);
        $negative = \str_starts_with($num, '-');

        if ($negative) {
            $num = \substr($num, 1);
        }

        [$int, $frac] = \array_pad(\explode('.', $num, 2), 2, '');
        $int = \strrev(\implode($thousandsSeparator, \str_split(\strrev($int), 3)));

        $result = ($negative ? '-' : '') . $int;

        if ($scale > 0) {
            $result .= $decimalSeparator . $frac;
        }

        return $result;
    }

    /**
     * Normalize an input into a numeric string bcmath can parse.
     * Floats and scientific notation are expanded to plain decimals.
     */
    private static function num(string|int|float $value): string
    {
        if (\is_float($value)) {
            $value = \sprintf('%.14F', $value);
        }

        $value = \trim((string) $value);

        if (\preg_match('/[eE]/', $value) === 1 && \is_numeric($value)) {
            $value = \sprintf('%.14F', (float) $value);
        }

        if (!\is_numeric($value)) {
            throw new BadRequestException(
                translate: Message::create(
                    key: 'request.invalid_parameter',
                    params: ['param' => $value]
                )
            );
        }

        return $value;
    }

    /**
     * Shared implementation for min/max: keeps the value that compares
     * $direction (bccomp result) against the current best.
     */
    private static function edge(array $values, int $direction): string
    {
        if ($values === []) {
            throw new ServiceException(
                translate: Message::create(
                    key: 'service.error',
                    params: ['reason' => 'at least one value is required']
                ),
                code: Status::INTERNAL_SERVER_ERROR
            );
        }

        $best = self::num(\array_shift($values));

        foreach ($values as $value) {
            $num = self::num($value);
            if (\bccomp($num, $best, 20) === $direction) {
                $best = $num;
            }
        }

        return $best;
    }
}
