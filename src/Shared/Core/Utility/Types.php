<?php

declare(strict_types=1);

namespace App\Shared\Core\Utility;

use BackedEnum;
use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;
use Stringable;
use Traversable;
use TypeError;

/**
 * Type Utility Functions.
 *
 * Utility functions for safely coercing mixed values (e.g. request
 * parameters, database rows) into typed values. Every caster accepts a
 * $default that is returned when the value is null, blank, or cannot be
 * converted without data loss.
 */
final class Types
{
    /**
     * Coerce a value to int.
     *
     * Accepts integers and integer strings ('10', ' -3 '). Floats, numeric
     * strings with fractions ('1.9'), booleans, and non-scalars are rejected
     * rather than silently truncated.
     */
    public static function int(mixed $value, ?int $default = null): ?int
    {
        if (\is_int($value)) {
            return $value;
        }

        if (\is_bool($value) || !\is_scalar($value)) {
            return $default;
        }

        $result = \filter_var(\trim((string) $value), FILTER_VALIDATE_INT);

        return $result === false ? $default : $result;
    }

    /**
     * Coerce a value to a positive int (>= 1). Useful for ids and
     * pagination parameters.
     */
    public static function positiveInt(mixed $value, ?int $default = null): ?int
    {
        $int = self::int($value);

        return $int !== null && $int > 0 ? $int : $default;
    }

    /**
     * Coerce a value to float.
     *
     * Accepts floats, integers, and numeric strings ('1.9', '1e3').
     */
    public static function float(mixed $value, ?float $default = null): ?float
    {
        if (\is_float($value)) {
            return $value;
        }

        if (\is_bool($value) || !\is_scalar($value)) {
            return $default;
        }

        $result = \filter_var(\trim((string) $value), FILTER_VALIDATE_FLOAT);

        return $result === false ? $default : $result;
    }

    /**
     * Coerce a value to int or float, whichever fits without data loss.
     * Integer strings return int, everything else numeric returns float.
     */
    public static function numeric(mixed $value, int|float|null $default = null): int|float|null
    {
        if (\is_int($value) || \is_float($value)) {
            return $value;
        }

        return self::int($value) ?? self::float($value, $default);
    }

    /**
     * Coerce a value to a fixed-scale decimal string.
     *
     * Returns a string (not float) so the result stays safe for money and
     * precise comparisons, e.g. Types::decimal('12.5') === '12.50'.
     * Note: input is converted through float, so values beyond float
     * precision lose exactness.
     */
    public static function decimal(mixed $value, int $scale = 2, ?string $default = null): ?string
    {
        if (\is_bool($value) || !\is_scalar($value)) {
            return $default;
        }

        $value = \trim((string) $value);

        if ($value === '' || !\is_numeric($value)) {
            return $default;
        }

        return \sprintf("%.{$scale}F", (float) $value);
    }

    /**
     * Coerce a value to string.
     *
     * Accepts strings, scalars, and Stringable objects. Null, arrays, and
     * other non-scalars fall back to $default.
     */
    public static function string(mixed $value, ?string $default = null): ?string
    {
        if (\is_string($value)) {
            return $value;
        }

        if (\is_scalar($value) || $value instanceof Stringable) {
            return (string) $value;
        }

        return $default;
    }

    /**
     * Coerce a value to a trimmed, non-empty string.
     * Blank or whitespace-only values fall back to $default.
     */
    public static function nonEmptyString(mixed $value, ?string $default = null): ?string
    {
        $string = self::string($value);

        if ($string === null) {
            return $default;
        }

        $string = \trim($string);

        return $string === '' ? $default : $string;
    }

    /**
     * Coerce a value to a normalized (lowercase) UUID string.
     */
    public static function uuid(mixed $value, ?string $default = null): ?string
    {
        $string = self::nonEmptyString($value);

        if ($string === null) {
            return $default;
        }

        $pattern = '/^[0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12}$/';

        return \preg_match($pattern, $string) === 1 ? \strtolower($string) : $default;
    }

    /**
     * Coerce a value to bool.
     *
     * Accepts booleans plus '1', '0', 'true', 'false', 'on', 'off', 'yes',
     * 'no' (case-insensitive). Blank strings and unrecognized values fall
     * back to $default.
     */
    public static function bool(mixed $value, ?bool $default = null): ?bool
    {
        if (\is_bool($value)) {
            return $value;
        }

        if (!\is_scalar($value)) {
            return $default;
        }

        $value = \trim((string) $value);

        if ($value === '') {
            return $default;
        }

        return \filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Coerce a value to array.
     *
     * Accepts arrays and Traversable objects (converted via
     * iterator_to_array). Everything else falls back to $default.
     *
     * @param array $default
     *
     * @return array
     */
    public static function array(mixed $value, array $default = []): array
    {
        if (\is_array($value)) {
            return $value;
        }

        if ($value instanceof Traversable) {
            return \iterator_to_array($value);
        }

        return $default;
    }

    /**
     * Coerce a value to a list of ints.
     *
     * Accepts arrays/Traversable or a comma-separated string ('1,2,3').
     * Non-convertible items are skipped.
     *
     * @return int[]
     */
    public static function intList(mixed $value, array $default = []): array
    {
        $items = self::listItems($value);

        if ($items === null) {
            return $default;
        }

        $result = [];

        foreach ($items as $item) {
            $int = self::int($item);
            if ($int !== null) {
                $result[] = $int;
            }
        }

        return $result;
    }

    /**
     * Coerce a value to a list of trimmed, non-empty strings.
     *
     * Accepts arrays/Traversable or a comma-separated string ('a, b ,c').
     * Non-stringable and empty items are skipped.
     *
     * @return string[]
     */
    public static function stringList(mixed $value, array $default = []): array
    {
        $items = self::listItems($value);

        if ($items === null) {
            return $default;
        }

        $result = [];

        foreach ($items as $item) {
            $string = self::nonEmptyString($item);
            if ($string !== null) {
                $result[] = $string;
            }
        }

        return $result;
    }

    /**
     * Cast specific fields in a record or a list of records.
     *
     * $fields maps field name => caster. A caster is either:
     * - a method name on this class: 'int', 'decimal', 'bool', ...
     * - [method, ...extraArgs]: ['decimal', 4], ['enum', RecordStatus::class]
     * - any callable: fn ($v) => ...
     *
     * An associative array is treated as one record, a list as multiple.
     *
     * @param array<string, string|array|callable> $fields
     */
    public static function castFields(array $data, array $fields): array
    {
        $casters = [];

        foreach ($fields as $key => $spec) {
            if (!\is_string($spec) && !\is_array($spec) && \is_callable($spec)) {
                $casters[$key] = $spec;
                continue;
            }

            [$method, $args] = \is_array($spec)
                ? [$spec[0] ?? null, \array_slice($spec, 1)]
                : [$spec, []];

            if (!\is_string($method) || !\is_callable([self::class, $method])) {
                throw new InvalidArgumentException("Invalid caster for field '$key' in Types::castFields()");
            }

            $casters[$key] = static fn (mixed $v) => self::{$method}($v, ...$args);
        }

        $apply = static function (array $item) use ($casters): array {
            foreach ($casters as $key => $cast) {
                if (\array_key_exists($key, $item)) {
                    $item[$key] = $cast($item[$key]);
                }
            }

            return $item;
        };

        return (!empty($data) && \array_is_list($data))
            ? \array_map($apply, $data)
            : $apply($data);
    }

    /**
     * Coerce a value to DateTimeImmutable.
     *
     * Accepts DateTimeInterface instances, int timestamps, and any string
     * accepted by the DateTimeImmutable constructor ('2024-01-31',
     * '2024-01-31 10:00:00', 'next Monday', ...).
     */
    public static function dateTime(mixed $value, ?DateTimeImmutable $default = null): ?DateTimeImmutable
    {
        if ($value instanceof DateTimeImmutable) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($value);
        }

        if (\is_int($value)) {
            return (new DateTimeImmutable())->setTimestamp($value);
        }

        $string = self::nonEmptyString($value);

        if ($string === null) {
            return $default;
        }

        try {
            return new DateTimeImmutable($string);
        } catch (\Exception) {
            return $default;
        }
    }

    /**
     * Coerce a value to a 'Y-m-d' date string.
     */
    public static function date(mixed $value, ?string $default = null): ?string
    {
        return self::dateTime($value)?->format('Y-m-d') ?? $default;
    }

    /**
     * Coerce a value to a formatted datetime string.
     * Defaults to the database format 'Y-m-d H:i:s'.
     */
    public static function dateTimeString(
        mixed $value,
        string $format = 'Y-m-d H:i:s',
        ?string $default = null
    ): ?string {
        return self::dateTime($value)?->format($format) ?? $default;
    }

    /**
     * Coerce a value to a backed enum case via tryFrom.
     *
     * Numeric strings are also tried as int so '1' resolves int-backed
     * enums such as RecordStatus.
     *
     * @template T of BackedEnum
     *
     * @param class-string<T> $enumClass
     * @param T|null          $default
     *
     * @return T|null
     */
    public static function enum(mixed $value, string $enumClass, ?BackedEnum $default = null): ?BackedEnum
    {
        if ($value instanceof $enumClass) {
            return $value;
        }

        if (!\enum_exists($enumClass) || !\is_subclass_of($enumClass, BackedEnum::class)) {
            throw new InvalidArgumentException("$enumClass must be a backed enum");
        }

        if (!\is_int($value) && !\is_string($value)) {
            return $default;
        }

        $candidates = [$value];

        if (\is_string($value) && \preg_match('/^-?\d+$/', $value) === 1) {
            $candidates[] = (int) $value;
        }

        foreach ($candidates as $candidate) {
            try {
                $enum = $enumClass::tryFrom($candidate);
            } catch (TypeError) {
                continue;
            }

            if ($enum !== null) {
                return $enum;
            }
        }

        return $default;
    }

    /**
     * Normalize a value into a list of raw items for the *List casters:
     * CSV strings are split, Traversable is converted to array.
     */
    private static function listItems(mixed $value): ?array
    {
        if (\is_string($value)) {
            return \explode(',', $value);
        }

        if (\is_array($value)) {
            return $value;
        }

        if ($value instanceof Traversable) {
            return \iterator_to_array($value);
        }

        return null;
    }
}
