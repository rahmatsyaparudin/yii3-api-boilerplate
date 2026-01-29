<?php

declare(strict_types=1);

namespace App\Shared\Query;

// Vendor Layer
use Yiisoft\Db\Query\Query;

/**
 * Query Condition Applier
 * 
 * This utility class provides a set of static methods for applying various
 * conditional filters to Yiisoft DB Query objects in a type-safe and consistent
 * manner. It supports exact matching, LIKE/ILIKE operations, IN clauses,
 * range filtering, and complex condition combinations with AND/OR logic.
 * 
 * @package App\Shared\Query
 * 
 * @example
 * // Basic exact match filtering
 * $query = new Query();
 * $filters = ['status' => 'active', 'type' => 'user'];
 * $allowedColumns = ['status', 'type', 'name'];
 * QueryConditionApplier::filterByExactMatch($query, $filters, $allowedColumns);
 * 
 * @example
 * // Complex filtering with multiple conditions
 * $query = new Query();
 * QueryConditionApplier::andWhere($query, ['status' => 'active', 'type' => 'premium']);
 * QueryConditionApplier::orLike($query, 'ilike', ['name' => 'john', 'email' => 'example']);
 * QueryConditionApplier::andRange($query, [
 *     'price' => ['min' => 100, 'max' => 500],
 *     'created_at' => ['min' => '2024-01-01', 'max' => '2024-12-31']
 * ]);
 * 
 * @example
 * // In repository using SearchCriteria
 * public function findByCriteria(SearchCriteria $criteria): array
 * {
 *     $query = $this->createQuery();
 *     
 *     // Apply filters from SearchCriteria
 *     QueryConditionApplier::filterByExactMatch($query, $criteria->filter, $this->allowedColumns);
 *     
 *     // Apply sorting
 *     $orderClause = $criteria->getOrderClause();
 *     foreach ($orderClause as $column => $direction) {
     *         $query->addOrderBy([$column => $direction]);
     *     }
 *     
 *     // Apply pagination
     $query->limit($criteria->getPageSize());
     $query->offset($criteria->getOffset());
 *     
 *     return $query->all();
 * }
 * 
 * @example
 * // In service layer for complex queries
 * public function searchProducts(SearchCriteria $criteria): PaginatedResult
 * {
 *     $query = $this->createQuery();
 *     
 *     // Apply filters
 *     QueryConditionApplier::filterByExactMatch($query, $criteria->filter, ['id', 'name', 'status', 'category']);
 *     
 *     // Apply text search
 *     if (!empty($criteria->search)) {
     *         QueryConditionApplier::orLike($query, 'ilike', [
     *             'name' => $criteria->search,
     *             'description' => $criteria->search,
     *             'tags' => $criteria->search
     *         ]);
     *     }
     *     
     // Apply date range
 *     if (isset($criteria->dateFrom)) {
     *         QueryConditionApplier::andRange($query, [
     *             'created_at' => ['min' => $criteria->dateFrom, 'max' => $criteria->dateTo]
     *         ]);
     }
     *     
 *     return $this->paginate($query, $criteria);
 * }
 * 
 * @example
 * // Building dynamic queries
 * public function buildQuery(array $conditions, array $allowedColumns): Query
 * {
 *     $query = new Query();
 *     
 *     // Apply exact match filters
 *     QueryConditionApplier::filterByExactMatch($query, $conditions, $allowedColumns);
 *     
     // Apply OR conditions for flexible search
 *     $orConditions = [];
 *     if (isset($conditions['search'])) {
     *         $orConditions['name'] = $conditions['search'];
     *         $orConditions['description'] = $conditions['search'];
     }
     QueryConditionApplier::orWhere($query, $orConditions);
     *     
 *     // Apply range filters
 *     $rangeConditions = array_filter($conditions, fn($v) => is_array($v) && isset($v['min']) || isset($v['max']));
 *     QueryConditionApplier::andRange($query, $rangeConditions);
     *     
     return $query;
 * }
 */
final class QueryConditionApplier
{
    /**
     * Filter query by exact match with column whitelist
     * 
     * Applies exact match filters to the query while only allowing
     * columns that are explicitly whitelisted for security.
     * 
     * @param Query $query The query to modify
     * @param array $filters Key-value pairs of column => value
     * @param array $allowedColumns Whitelisted column names
     * @return Query The modified query
     * 
     * @example
     * // Basic exact match filtering
     * $query = new Query();
     $filters = ['status' => 'active', 'type' => 'user'];
     $allowedColumns = ['status', 'type', 'name'];
     $query = QueryConditionApplier::filterByExactMatch($query, $filters, $allowedColumns);
     * 
     * @example
     * // In repository with security
     * $query = $this->createQuery();
     $filters = $request->all();
     $allowedColumns = ['id', 'name', 'status', 'category'];
     $query = QueryConditionApplier::filterByExactMatch($query, $filters, $allowedColumns);
     * 
     * @example
     // With SearchCriteria integration
     $query = $this->createQuery();
     $query = QueryConditionApplier::filterByExactMatch(
     *     query: $query,
     *     filters: $criteria->filter,
     *     allowedColumns: $this->allowedColumns
     * );
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
     * Apply AND equality filters to the query
     * 
     * Adds multiple AND conditions to the query for exact matching.
     * Empty or null values are automatically skipped.
     * 
     * @param Query $query The query to modify
     * @param array $conditions Key-value pairs of column => value
     * @return Query The modified query
     * 
     * @example
     * // Basic AND filtering
     * $query = new Query();
     $conditions = ['status' => 1, 'type' => 'premium', 'is_active' => true];
     $query = QueryConditionApplier::andWhere($query, $conditions);
     * 
     * @example
     // In service layer
     * $query = $this->createQuery();
     $query = QueryConditionApplier::andWhere($query, [
     *     'status' => RecordStatus::ACTIVE->value,
     *     'type' => 'premium',
     *     'created_at' => $date
     * ]);
     * 
     * @example
     // With SearchCriteria
     * $query = $this->createQuery();
     $query = QueryConditionApplier::andWhere(
     *     query: $query,
     *     conditions: $criteria->filter
     * );
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
     * Apply OR equality filters to the query
     * 
     * Adds multiple OR conditions wrapped in a single AND clause.
     * The result format is: AND (col1 = x OR col2 = y OR col3 = z).
     * Empty or null values are automatically skipped.
     * 
     * @param Query $query The query to modify
     * @param array $conditions Key-value pairs of column => value
     * @return Query The modified query
     * 
     * @example
     * // Basic OR filtering
     * $query = new Query();
     $conditions = ['status' => 'active', 'archived' => false];
     $query = QueryConditionApplier::orWhere($query, $conditions);
     * // Generates: AND (status = 'active' OR archived = false)
     * 
     * @example
     // Flexible search with multiple OR conditions
     * $query = new Query();
     $query = QueryConditionApplier::orWhere($query, [
     *     'name' => 'John',
     *     'email' => 'john@example.com',
     *     'username' => 'john_doe'
     * ]);
     * 
     * @example
     // In search functionality
     $query = $this->createQuery();
     $searchTerms = explode(' ', $searchQuery);
     $orConditions = [];
     foreach ($searchTerms as $term) {
     *     $orConditions['name'] = "%$term%";
     *     $orConditions['description'] = "%$term%";
     * }
     * QueryConditionApplier::orWhere($query, $orConditions);
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
     * Apply AND LIKE/ILIKE filters to the query
     * 
     * Adds text search conditions using the specified operator.
     * Commonly used for case-insensitive (ilike) or case-sensitive (like) text search.
     * 
     * @param Query $query The query to modify
     * @param string $operator The LIKE operator to use ('like' or 'ilike')
     * @param array $conditions Key-value pairs of column => search pattern
     * @return Query The modified query
     * 
     * @example
     * // Case-insensitive text search
     * $query = new Query();
     $conditions = ['name' => '%john%', 'email' => '%@example%'];
     $query = QueryConditionApplier::andLike($query, 'ilike', $conditions);
     * 
     * @example
     * // Case-sensitive text search
     * $query = new Query();
     $conditions = ['title' => 'Exact Match', 'description' => 'Contains Text'];
     * $query = QueryConditionApplier::andLike($query, 'like', $conditions);
     * 
     * @example
     * In search service
     * $query = $this->createQuery();
     $query = QueryConditionApplier::andLike(
     *     query: $query,
     *     operator: 'ilike',
     *     conditions: [
     *         'name' => "%{$searchTerm}%",
     *         'description' => "%{$searchTerm}%"
     *     ]
     * );
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
     * Apply OR LIKE/ILIKE filters to the query
     * 
     * Adds text search conditions using OR logic within an AND clause.
     * The result format is: AND (col1 LIKE x OR col2 LIKE y OR col3 LIKE z).
     * 
     * @param Query $query The query to modify
     * @param string $operator The LIKE operator to use ('like' or 'ilike')
     * @param array $conditions Key-value pairs of column => search pattern
     * @return Query The modified query
     * 
     * @example
     * // Multi-field text search
     * $query = new Query();
     $conditions = [
     *     'name' => '%john%',
     *     'email' => '%@example%',
     *     'username' => '%john%'
     * ];
     $query = QueryConditionApplier::orLike($query, 'ilike', $conditions);
     * // Generates: AND (name LIKE '%john%' OR email LIKE '%@example%' OR username LIKE '%john%')
     * 
     * @example
     * In search API endpoint
     * $query = $this->createQuery();
     $searchTerms = ['product', 'description', 'tags'];
     $orConditions = [];
     foreach ($searchTerms as $term) {
     *     $orConditions[$term] = "%$term%";
     * }
     $query = QueryConditionApplier::orLike($query, 'ilike', $orConditions);
     * 
     * @example
     // Advanced search with multiple operators
     * $query = $this->createQuery();
     $query = QueryConditionApplier::orLike($query, 'ilike', [
     *     'title' => '%search%',
     *     'content' => '%search%'
     * ]);
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
     * Apply AND IN filters to the query
     * 
     * Adds IN conditions for multiple value matching.
     * Empty arrays are automatically skipped for performance.
     * 
     * @param Query $query The query to modify
     * @param array $conditions Key-value pairs of column => array of values
     * @return Query The modified query
     * 
     * @example
     * // Basic IN filtering
     * $query = new Query();
     $conditions = ['id' => [1, 2, 3], 'status' => ['active', 'pending']];
     $query = QueryConditionApplier::andIn($query, $conditions);
     * // Generates: WHERE id IN (1, 2, 3) AND status IN ('active', 'pending')
     * 
     * @example
     // Status filtering with enums
     * $query = new Query();
     $conditions = [
     *     'status' => [
     *         RecordStatus::ACTIVE->value,
     *         RecordStatus::PENDING->value
     *     ]
     * ];
     $query = QueryConditionApplier::andIn($query, $conditions);
     * 
     * @example
     // In repository for filtering
     * $query = $this->createQuery();
     $filters = $request->get('ids');
     if (!empty($filters)) {
     *     $query = QueryConditionApplier::andIn($query, ['id' => $filters]);
     * }
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
     * Apply OR IN filters to the query
     * 
     * Adds OR conditions with IN logic within an AND clause.
     * The result format is: AND (col IN (1,2,3) OR col2 IN (4,5,6)).
     * Empty arrays are automatically skipped for performance.
     * 
     * @param Query $query The query to modify
     * @param array $conditions Key-value pairs of column => array of values
     * @return Query The modified query
     * 
     * @example
     // Multiple IN conditions
     * $query = new Query();
     $conditions = [
     *     'category_id' => [1, 2, 3],
     *     'status_id' => [4, 5]
     * ];
     $query = QueryConditionApplier::orIn($query, $conditions);
     * // Generates: AND (category_id IN (1, 2, 3) OR status_id IN (4, 5))
     * 
     * @example
     // Flexible category filtering
     $query = $this->createQuery();
     $selectedCategories = $request->get('categories', []);
     $allCategories = $this->categoryService->getAllIds();
     $conditions = ['category_id' => $selectedCategories];
     $query = QueryConditionApplier::orIn($query, $conditions);
     * 
     * @example
     // Status filtering with multiple status sets
     * $query = $this->createQuery();
     $activeStatuses = [RecordStatus::ACTIVE->value, RecordStatus::PENDING->value];
     $inactiveStatuses = [RecordStatus::INACTIVE->value];
     $query = QueryConditionApplier::orIn($query, [
     *     'status' => $activeStatuses,
     *     'archived' => $inactiveStatuses
     * ]);
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
     * Apply numeric or date range filters to the query
     * 
     * Supports both numeric ranges (min/max) and date ranges.
     * Uses >= and <= operators for range boundaries.
     * 
     * @param Query $query The query to modify
     * @param array $ranges Key-value pairs with 'min' and/or 'max' values
     * @return Query The modified query
     * 
     * @example
     // Numeric range filtering
     * $query = new Query();
     $ranges = [
     *     'price' => ['min' => 100, 'max' => 500],
     *     'quantity' => ['min' => 1]
     * ];
     $query = QueryConditionApplier::andRange($query, $ranges);
     * // Generates: WHERE price >= 100 AND price <= 500 AND quantity >= 1
     * 
     * @example
     // Date range filtering
     * $query = new Query();
     $ranges = [
     *     'created_at' => ['min' => '2024-01-01', 'max' => '2024-12-31'],
     *     'updated_at' => ['min' => '2024-06-01']
     * ];
     $query = QueryConditionApplier::andRange($query, $ranges);
     * // Generates: WHERE created_at >= '2024-01-01' AND created_at <= '2024-12-31' AND updated_at >= '2024-06-01'
     * 
     * @example
     // In service for date-based filtering
     $query = $this->createQuery();
     $dateRange = $this->getDateRange($request);
     $ranges = [
     *     'created_at' => ['min' => $dateRange['from'], 'max' => $dateRange['to']],
     *     'updated_at' => ['min' => $dateRange['updated_from']]
     * ];
     $query = QueryConditionApplier::andRange($query, $ranges);
     * 
     * @example
     // Price range filtering with SearchCriteria
     $query = $this->createQuery();
     $ranges = [
     *     'price' => [
     *         'min' => $criteria->getMinPrice(),
     *         'max' => $criteria->getMaxPrice()
     *     ]
     * ];
     $query = QueryConditionApplier::andRange($query, $ranges);
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
     * Check if a value should be applied to the query
     * 
     * Determines if a value is non-null and non-empty string.
     * This helper method is used to skip empty values in query building.
     * 
     * @param mixed $value The value to check
     * @return bool True if value should be applied, false otherwise
     * 
     * @example
     * // Check various value types
     * $isValid = QueryConditionApplier::isFilled('active'); // true
     * $isValid = QueryConditionApplier::isFilled(''); // false
     * $isValid = QueryConditionApplier::isFilled(0); // false
     * $isValid = QueryConditionApplier::isFilled([]); // false
     * 
     * @example
     // In filtering logic
     * foreach ($conditions as $column => $value) {
     *     if (QueryConditionApplier::isFilled($value)) {
     *         $query->andWhere([$column => $value]);
     *     }
     * }
     */
    private static function isFilled(mixed $value): bool
    {
        return $value !== null && $value !== '';
    }
}
