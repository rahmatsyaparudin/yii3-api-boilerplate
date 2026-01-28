<?php

declare(strict_types=1);

namespace App\Shared\Query;

// Vendor Layer
use Yiisoft\Db\Query\Query;

/**
 * QueryConditionApplier
 *
 * Stateless utility to apply conditional filters to Yiisoft DB Query.
 * Designed for Infrastructure / Application layer usage.
 *
 * - No database connection dependency
 * - No domain knowledge
 * - Safe for reuse across modules & APIs
 */
final class QueryConditionApplier
{
    /**
     * Menyaring query berdasarkan kecocokan nilai yang persis (exact match)
     * sesuai dengan kolom yang diizinkan (whitelist).
     */
    public static function filterByExactMatch(Query $query, array $filters, array $allowedColumns): Query
    {
        // 1. Ambil hanya kunci yang diizinkan
        $whitelisted = array_intersect_key($filters, array_flip($allowedColumns));
        
        // 2. Buang nilai kosong (null atau string kosong)
        $activeFilters = array_filter($whitelisted, fn($v) => $v !== null && $v !== '');

        if (!empty($activeFilters)) {
            self::andWhere($query, $activeFilters);
        }

        return $query;
    }

    /**
     * Apply AND equality filters
     *
     * Example:
     *  ['status' => 1, 'type' => null]
     */
    public static function andWhere(Query $query, array $conditions): Query
    {
        foreach ($conditions as $column => $value) {
            if (self::isFilled($value)) {
                $query->andWhere([$column => $value]);
            }
        }

        return $query;
    }

    /**
     * Apply OR equality filters
     *
     * Result:
     *  AND (col1 = x OR col2 = y)
     */
    public static function orWhere(Query $query, array $conditions): Query
    {
        $or = [];

        foreach ($conditions as $column => $value) {
            if (self::isFilled($value)) {
                $or[] = [$column => $value];
            }
        }

        if ($or !== []) {
            $query->andWhere(['or', ...$or]);
        }

        return $query;
    }

    /**
     * Apply AND LIKE / ILIKE filters
     *
     * Operator must be provided by caller (like / ilike)
     */
    public static function andLike(
        Query $query,
        string $operator,
        array $conditions
    ): Query {
        foreach ($conditions as $column => $value) {
            if (self::isFilled($value)) {
                $query->andWhere([$operator, $column, $value]);
            }
        }

        return $query;
    }

    /**
     * Apply OR LIKE / ILIKE filters
     *
     * Result:
     *  AND (col1 LIKE x OR col2 LIKE y)
     */
    public static function orLike(
        Query $query,
        string $operator,
        array $conditions
    ): Query {
        $or = [];

        foreach ($conditions as $column => $value) {
            if (self::isFilled($value)) {
                $or[] = [$operator, $column, $value];
            }
        }

        if ($or !== []) {
            $query->andWhere(['or', ...$or]);
        }

        return $query;
    }

    /**
     * Apply AND IN filters
     *
     * Example:
     *  ['id' => [1,2,3]]
     */
    public static function andIn(Query $query, array $conditions): Query
    {
        foreach ($conditions as $column => $values) {
            if (is_array($values) && $values !== []) {
                $query->andWhere([$column => $values]);
            }
        }

        return $query;
    }

    /**
     * Apply OR IN filters
     *
     * Result:
     *  AND (col IN (...) OR col2 IN (...))
     */
    public static function orIn(Query $query, array $conditions): Query
    {
        $or = [];

        foreach ($conditions as $column => $values) {
            if (is_array($values) && $values !== []) {
                $or[] = [$column => $values];
            }
        }

        if ($or !== []) {
            $query->andWhere(['or', ...$or]);
        }

        return $query;
    }

    /**
     * Apply numeric or date range filters
     *
     * Example:
     *  [
     *    'price' => ['min' => 100, 'max' => 500],
     *    'created_at' => ['min' => '2024-01-01']
     *  ]
     */
    public static function andRange(Query $query, array $ranges): Query
    {
        foreach ($ranges as $column => $range) {
            if (!is_array($range)) {
                continue;
            }

            if (array_key_exists('min', $range) && self::isFilled($range['min'])) {
                $query->andWhere(['>=', $column, $range['min']]);
            }

            if (array_key_exists('max', $range) && self::isFilled($range['max'])) {
                $query->andWhere(['<=', $column, $range['max']]);
            }
        }

        return $query;
    }

    /**
     * Check if value should be applied to query
     */
    private static function isFilled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
