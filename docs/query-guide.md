# Query Building Guide

## 📋 Overview

Query building utilities provide a structured way to build and execute database queries in the Yii3 API application. These components ensure consistent query construction and prevent SQL injection.

---

## 🏗️ Query Architecture

### Directory Structure

```
src/Shared/Query/
└── QueryConditionApplier.php    # Query condition application utilities
```

### Design Principles

#### **1. **Type Safety**
- Strong typing with PHP 8+ features
- Parameter binding prevents SQL injection
- Compile-time error detection

#### **2. **Flexibility**
- Dynamic condition building
- Composable query logic
- Extensible condition types

#### **3. **Security**
- Parameterized queries
- SQL injection prevention
- Safe condition building

#### **4. **Performance**
- Efficient query building
- Minimal overhead
- Optimized condition application

---

## 📁 Query Components

### QueryConditionApplier

**Purpose**: Dynamic query condition application with type safety

```php
<?php

declare(strict_types=1);

namespace App\Shared\Query;

use Yiisoft\Db\ConnectionInterface;
use Yiisoft\Db\Query\Query;

/**
 * Query Condition Applier
 */
final class QueryConditionApplier
{
    public function __construct(
        private ConnectionInterface $db
    ) {}

    /**
     * Apply conditions to query
     */
    public function applyConditions(Query $query, array $conditions): Query
    {
        foreach ($conditions as $field => $value) {
            $query = $this->applyCondition($query, $field, $value);
        }

        return $query;
    }

    /**
     * Apply single condition
     */
    public function applyCondition(Query $query, string $field, mixed $value): Query
    {
        if (is_array($value)) {
            return $this->applyArrayCondition($query, $field, $value);
        }

        if ($value === null) {
            return $query->andWhere([$field => null]);
        }

        return $query->andWhere([$field => $value]);
    }

    /**
     * Apply array condition
     */
    private function applyArrayCondition(Query $query, string $field, array $value): Query
    {
        // Handle IN condition
        if ($this->isInCondition($value)) {
            return $query->andWhere([$field => $value['in']]);
        }

        // Handle NOT IN condition
        if ($this->isNotInCondition($value)) {
            return $query->andWhere(['NOT IN', $field, $value['not_in']]);
        }

        // Handle BETWEEN condition
        if ($this->isBetweenCondition($value)) {
            return $query->andWhere(['BETWEEN', $field, $value['between'][0], $value['between'][1]]);
        }

        // Handle NOT BETWEEN condition
        if ($this->isNotBetweenCondition($value)) {
            return $query->andWhere(['NOT BETWEEN', $field, $value['not_between'][0], $value['not_between'][1]]);
        }

        // Handle LIKE condition
        if ($this->isLikeCondition($value)) {
            return $query->andWhere(['LIKE', $field, $value['like']]);
        }

        // Handle NOT LIKE condition
        if ($this->isNotLikeCondition($value)) {
            return $query->andWhere(['NOT LIKE', $field, $value['not_like']]);
        }

        // Handle ILIKE condition (case-insensitive like)
        if ($this->isIlikeCondition($value)) {
            return $this->applyIlikeCondition($query, $field, $value['ilike']);
        }

        // Handle OR condition
        if ($this->isOrCondition($value)) {
            return $this->applyOrCondition($query, $field, $value['or']);
        }

        // Handle multiple conditions
        return $this->applyMultipleConditions($query, $field, $value);
    }

    /**
     * Apply ILIKE condition (case-insensitive)
     */
    private function applyIlikeCondition(Query $query, string $field, string $value): Query
    {
        // Use LOWER() for case-insensitive comparison
        return $query->andWhere(['LOWER(' . $field . ')' => strtolower($value)]);
    }

    /**
     * Apply OR condition
     */
    private function applyOrCondition(Query $query, string $field, array $conditions): Query
    {
        $orConditions = [];
        
        foreach ($conditions as $condition) {
            if (is_array($condition)) {
                $orConditions[] = $condition;
            } else {
                $orConditions[] = [$field => $condition];
            }
        }
        
        if (!empty($orConditions)) {
            $query->andWhere(['OR', $orConditions]);
        }
        
        return $query;
    }

    /**
     * Apply multiple conditions
     */
    private function applyMultipleConditions(Query $query, string $field, array $conditions): Query
    {
        foreach ($conditions as $operator => $value) {
            switch (strtoupper($operator)) {
                case '>':
                    $query->andWhere(['>', $field, $value]);
                    break;
                case '>=':
                    $query->andWhere(['>=', $field, $value]);
                    break;
                case '<':
                    $query->andWhere(['<', $field, $value]);
                    break;
                case '<=':
                    $query->andWhere(['<=', $field, $value]);
                    break;
                case '!=':
                    $query->andWhere(['!=', $field, $value]);
                    break;
                case '<>':
                    $query->andWhere(['<>', $field, $value]);
                    break;
                case 'IS NOT NULL':
                    $query->andWhere(['IS NOT', $field, null]);
                    break;
                case 'IS NULL':
                    $query->andWhere(['IS', $field, null]);
                    break;
            }
        }
        
        return $query;
    }

    /**
     * Check if condition is IN
     */
    private function isInCondition(array $value): bool
    {
        return array_key_exists('in', $value) && is_array($value['in']);
    }

    /**
     * Check if condition is NOT IN
     */
    private function isNotInCondition(array $value): bool
    {
        return array_key_exists('not_in', $value) && is_array($value['not_in']);
    }

    /**
     * Check if condition is BETWEEN
     */
    private function isBetweenCondition(array $value): bool
    {
        return array_key_exists('between', $value) 
            && is_array($value['between']) 
            && count($value['between']) === 2;
    }

    /**
     * Check if condition is NOT BETWEEN
     */
    private function isNotBetweenCondition(array $value): bool
    {
        return array_key_exists('not_between', $value) 
            && is_array($value['not_between']) 
            && count($value['not_between']) === 2;
    }

    /**
     * Check if condition is LIKE
     */
    private function isLikeCondition(array $value): bool
    {
        return array_key_exists('like', $value) && is_string($value['like']);
    }

    /**
     * Check if condition is NOT LIKE
     */
    private function isNotLikeCondition(array $value): bool
    {
        return array_key_exists('not_like', $value) && is_string($value['not_like']);
    }

    /**
     * Check if condition is ILIKE
     */
    private function isIlikeCondition(array $value): bool
    {
        return array_key_exists('ilike', $value) && is_string($value['ilike']);
    }

    /**
     * Check if condition is OR
     */
    private function isOrCondition(array $value): bool
    {
        return array_key_exists('or', $value) && is_array($value['or']);
    }

    /**
     * Apply sorting to query
     */
    public function applySorting(Query $query, array $sorting): Query
    {
        foreach ($sorting as $field => $direction) {
            $query->addOrderBy([$field => strtoupper($direction)]);
        }

        return $query;
    }

    /**
     * Apply pagination to query
     */
    public function applyPagination(Query $query, int $limit, int $offset): Query
    {
        return $query->limit($limit)->offset($offset);
    }

    /**
     * Apply search condition
     */
    public function applySearch(Query $query, array $searchFields, string $searchTerm): Query
    {
        if (empty($searchTerm) || empty($searchFields)) {
            return $query;
        }

        $orConditions = [];
        
        foreach ($searchFields as $field) {
            $orConditions[] = ['LIKE', $field, '%' . $searchTerm . '%'];
        }
        
        if (!empty($orConditions)) {
            $query->andWhere(['OR', $orConditions]);
        }
        
        return $query;
    }

    /**
     * Apply date range condition
     */
    public function applyDateRange(Query $query, string $field, ?string $startDate, ?string $endDate): Query
    {
        if ($startDate) {
            $query->andWhere(['>=', $field, $startDate]);
        }
        
        if ($endDate) {
            $query->andWhere(['<=', $field, $endDate]);
        }
        
        return $query;
    }

    /**
     * Apply numeric range condition
     */
    public function applyNumericRange(Query $query, string $field, ?float $minValue, ?float $maxValue): Query
    {
        if ($minValue !== null) {
            $query->andWhere(['>=', $field, $minValue]);
        }
        
        if ($maxValue !== null) {
            $query->andWhere(['<=', $field, $maxValue]);
        }
        
        return $query;
    }

    /**
     * Apply text search with multiple fields
     */
    public function applyTextSearch(Query $query, array $searchConfig, string $searchTerm): Query
    {
        if (empty($searchTerm)) {
            return $query;
        }

        $orConditions = [];
        
        foreach ($searchConfig as $config) {
            $field = $config['field'];
            $type = $config['type'] ?? 'like';
            $weight = $config['weight'] ?? 1;
            
            switch ($type) {
                case 'like':
                    $orConditions[] = ['LIKE', $field, '%' . $searchTerm . '%'];
                    break;
                case 'ilike':
                    $orConditions[] = ['LOWER(' . $field . ')' => strtolower($searchTerm)];
                    break;
                case 'exact':
                    $orConditions[] = [$field => $searchTerm];
                    break;
                case 'starts_with':
                    $orConditions[] = ['LIKE', $field, $searchTerm . '%'];
                    break;
                case 'ends_with':
                    $orConditions[] = ['LIKE', $field, '%' . $searchTerm];
                    break;
            }
        }
        
        if (!empty($orConditions)) {
            $query->andWhere(['OR', $orConditions]);
        }
        
        return $query;
    }

    /**
     * Apply filter conditions
     */
    public function applyFilters(Query $query, array $filters): Query
    {
        foreach ($filters as $filter) {
            $query = $this->applyFilter($query, $filter);
        }
        
        return $query;
    }

    /**
     * Apply single filter
     */
    private function applyFilter(Query $query, array $filter): Query
    {
        $field = $filter['field'];
        $operator = $filter['operator'];
        $value = $filter['value'];
        
        switch (strtoupper($operator)) {
            case '=':
            case 'EQ':
                $query->andWhere([$field => $value]);
                break;
            case '!=':
            case 'NE':
                $query->andWhere(['!=', $field, $value]);
                break;
            case '>':
            case 'GT':
                $query->andWhere(['>', $field, $value]);
                break;
            case '>=':
            case 'GTE':
                $query->andWhere(['>=', $field, $value]);
                break;
            case '<':
            case 'LT':
                $query->andWhere(['<', $field, $value]);
                break;
            case '<=':
            case 'LTE':
                $query->andWhere(['<=', $field, $value]);
                break;
            case 'IN':
                $query->andWhere(['IN', $field, $value]);
                break;
            case 'NOT IN':
                $query->andWhere(['NOT IN', $field, $value]);
                break;
            case 'LIKE':
                $query->andWhere(['LIKE', $field, $value]);
                break;
            case 'NOT LIKE':
                $query->andWhere(['NOT LIKE', $field, $value]);
                break;
            case 'ILIKE':
                $query->andWhere(['LOWER(' . $field . ')' => strtolower($value)]);
                break;
            case 'IS NULL':
                $query->andWhere(['IS', $field, null]);
                break;
            case 'IS NOT NULL':
                $query->andWhere(['IS NOT', $field, null]);
                break;
            case 'BETWEEN':
                if (is_array($value) && count($value) === 2) {
                    $query->andWhere(['BETWEEN', $field, $value[0], $value[1]]);
                }
                break;
            case 'NOT BETWEEN':
                if (is_array($value) && count($value) === 2) {
                    $query->andWhere(['NOT BETWEEN', $field, $value[0], $value[1]]);
                }
                break;
        }
        
        return $query;
    }

    /**
     * Apply join conditions
     */
    public function applyJoins(Query $query, array $joins): Query
    {
        foreach ($joins as $join) {
            $type = $join['type'] ?? 'INNER';
            $table = $join['table'];
            $on = $join['on'];
            $alias = $join['alias'] ?? null;
            
            if ($alias) {
                $query->join($type, $table, $alias, $on);
            } else {
                $query->join($type, $table, $on);
            }
        }
        
        return $query;
    }

    /**
     * Apply group by
     */
    public function applyGroupBy(Query $query, array $fields): Query
    {
        foreach ($fields as $field) {
            $query->addGroupBy($field);
        }
        
        return $query;
    }

    /**
     * Apply having conditions
     */
    public function applyHaving(Query $query, array $conditions): Query
    {
        foreach ($conditions as $field => $value) {
            if (is_array($value)) {
                $query->andHaving($value);
            } else {
                $query->andHaving([$field => $value]);
            }
        }
        
        return $query;
    }

    /**
     * Build query from criteria
     */
    public function buildQuery(array $criteria): Query
    {
        $query = $this->db->createQueryBuilder()
            ->select($criteria['select'] ?? ['*'])
            ->from($criteria['from']);

        // Apply joins
        if (isset($criteria['joins'])) {
            $query = $this->applyJoins($query, $criteria['joins']);
        }

        // Apply conditions
        if (isset($criteria['conditions'])) {
            $query = $this->applyConditions($query, $criteria['conditions']);
        }

        // Apply group by
        if (isset($criteria['group_by'])) {
            $query = $this->applyGroupBy($query, $criteria['group_by']);
        }

        // Apply having
        if (isset($criteria['having'])) {
            $query = $this->applyHaving($query, $criteria['having']);
        }

        // Apply sorting
        if (isset($criteria['order'])) {
            $query = $this->applySorting($query, $criteria['order']);
        }

        // Apply pagination
        if (isset($criteria['limit'])) {
            $query = $this->applyPagination(
                $query,
                $criteria['limit'],
                $criteria['offset'] ?? 0
            );
        }

        return $query;
    }

    /**
     * Count query
     */
    public function buildCountQuery(array $criteria): Query
    {
        $countCriteria = $criteria;
        $countCriteria['select'] = ['COUNT(*) as count'];
        unset($countCriteria['order']); // Order doesn't matter for count
        
        return $this->buildQuery($countCriteria);
    }

    /**
     * Validate criteria
     */
    public function validateCriteria(array $criteria): array
    {
        $errors = [];
        
        // Validate from clause
        if (!isset($criteria['from']) || !is_string($criteria['from'])) {
            $errors[] = 'From clause is required and must be a string';
        }
        
        // Validate select clause
        if (isset($criteria['select']) && !is_array($criteria['select'])) {
            $errors[] = 'Select clause must be an array';
        }
        
        // Validate conditions
        if (isset($criteria['conditions']) && !is_array($criteria['conditions'])) {
            $errors[] = 'Conditions must be an array';
        }
        
        // Validate order
        if (isset($criteria['order']) && !is_array($criteria['order'])) {
            $errors[] = 'Order clause must be an array';
        }
        
        // Validate limit
        if (isset($criteria['limit'])) {
            if (!is_int($criteria['limit']) || $criteria['limit'] < 1) {
                $errors[] = 'Limit must be a positive integer';
            }
        }
        
        // Validate offset
        if (isset($criteria['offset'])) {
            if (!is_int($criteria['offset']) || $criteria['offset'] < 0) {
                $errors[] = 'Offset must be a non-negative integer';
            }
        }
        
        return $errors;
    }
}
```

---

## 🔧 Integration Patterns

### 1. **Repository Usage**
```php
final class ExampleRepository
{
    public function __construct(
        private ConnectionInterface $db,
        private QueryConditionApplier $conditionApplier
    ) {}

    public function findAll(SearchCriteria $criteria): array
    {
        $query = $this->db->createQueryBuilder()
            ->select('*')
            ->from('example');

        // Apply filters
        foreach ($criteria->filters as $field => $value) {
            $query = $this->conditionApplier->applyCondition($query, $field, $value);
        }

        // Apply sorting
        foreach ($criteria->sort as $field => $direction) {
            $query->addOrderBy([$field => $direction]);
        }

        // Apply pagination
        $query->offset($criteria->getOffset())
              ->limit($criteria->limit);

        return $query->fetchAll();
    }

    public function findByFilters(array $filters): array
    {
        $query = $this->db->createQueryBuilder()
            ->select('*')
            ->from('example');

        $query = $this->conditionApplier->applyConditions($query, $filters);

        return $query->fetchAll();
    }

    public function countByFilters(array $filters): int
    {
        $query = $this->db->createQueryBuilder()
            ->select('COUNT(*) as count')
            ->from('example');

        $query = $this->conditionApplier->applyConditions($query, $filters);

        $result = $query->fetchOne();
        return (int) $result['count'];
    }

    public function search(string $searchTerm, array $fields): array
    {
        $query = $this->db->createQueryBuilder()
            ->select('*')
            ->from('example');

        $query = $this->conditionApplier->applySearch($query, $fields, $searchTerm);

        return $query->fetchAll();
    }

    public function findByDateRange(string $field, ?string $startDate, ?string $endDate): array
    {
        $query = $this->db->createQueryBuilder()
            ->select('*')
            ->from('example');

        $query = $this->conditionApplier->applyDateRange($query, $field, $startDate, $endDate);

        return $query->fetchAll();
    }
}
```

### 2. **Service Usage**
```php
final class ExampleApplicationService
{
    public function __construct(
        private ExampleRepositoryInterface $repository,
        private QueryConditionApplier $conditionApplier
    ) {}

    public function list(SearchCriteria $criteria): PaginatedResult
    {
        // Build complex query
        $queryCriteria = [
            'from' => 'example',
            'conditions' => $criteria->filters,
            'order' => $criteria->sort,
            'limit' => $criteria->limit,
            'offset' => $criteria->getOffset(),
        ];

        // Validate criteria
        $errors = $this->conditionApplier->validateCriteria($queryCriteria);
        if (!empty($errors)) {
            throw new BadRequestException('Invalid query criteria: ' . implode(', ', $errors));
        }

        // Execute query
        $query = $this->conditionApplier->buildQuery($queryCriteria);
        $data = $query->fetchAll();

        // Get total count
        $countQuery = $this->conditionApplier->buildCountQuery($queryCriteria);
        $total = (int) $countQuery->fetchOne()['count'];

        return PaginatedResult::fromArray(
            $data,
            $total,
            $criteria->page,
            $criteria->limit
        );
    }

    public function advancedSearch(AdvancedSearchCommand $command): array
    {
        $query = $this->db->createQueryBuilder()
            ->select([
                'e.*',
                'c.name as category_name'
            ])
            ->from('example e')
            ->innerJoin('category c', 'e.category_id = c.id');

        // Apply text search
        if (!empty($command->searchTerm)) {
            $searchConfig = [
                ['field' => 'e.name', 'type' => 'ilike', 'weight' => 2],
                ['field' => 'e.description', 'type' => 'ilike', 'weight' => 1],
                ['field' => 'c.name', 'type' => 'ilike', 'weight' => 1],
            ];
            
            $query = $this->conditionApplier->applyTextSearch($query, $searchConfig, $command->searchTerm);
        }

        // Apply filters
        $filters = [];
        
        if ($command->status) {
            $filters['e.status'] = $command->status;
        }
        
        if ($command->categoryId) {
            $filters['e.category_id'] = $command->categoryId;
        }
        
        if ($command->minPrice !== null) {
            $filters['e.price'] = ['>=', $command->minPrice];
        }
        
        if ($command->maxPrice !== null) {
            $filters['e.price'] = ['<=', $command->maxPrice];
        }
        
        if (!empty($filters)) {
            $query = $this->conditionApplier->applyFilters($query, $filters);
        }

        // Apply sorting
        $sorting = [
            'e.created_at' => 'desc'
        ];
        
        if ($command->sortBy) {
            $sorting = [$command->sortBy => $command->sortDirection ?? 'asc'];
        }
        
        $query = $this->conditionApplier->applySorting($query, $sorting);

        return $query->fetchAll();
    }
}
```

### 3. **Controller Usage**
```php
final class ExampleController
{
    public function __construct(
        private ExampleApplicationService $service
    ) {}

    public function actionSearch(): array
    {
        $searchTerm = $this->request->getQueryParam('q', '');
        $fields = ['name', 'description', 'category'];
        
        if (empty($searchTerm)) {
            throw BadRequestException::invalidParameter('q', 'Search term is required');
        }

        $results = $this->service->search($searchTerm, $fields);
        
        return [
            'data' => $results,
            'count' => count($results),
            'search_term' => $searchTerm,
        ];
    }

    public function actionFilter(): array
    {
        $filters = [
            'status' => $this->request->getQueryParam('status'),
            'category_id' => $this->request->getQueryParam('category_id'),
            'min_price' => $this->request->getQueryParam('min_price'),
            'max_price' => $this->request->getQueryParam('max_price'),
        ];

        // Remove null values
        $filters = array_filter($filters, fn($value) => $value !== null);

        $results = $this->service->findByFilters($filters);
        
        return [
            'data' => $results,
            'count' => count($results),
            'filters' => $filters,
        ];
    }

    public function actionDateRange(): array
    {
        $startDate = $this->request->getQueryParam('start_date');
        $endDate = $this->request->getQueryParam('end_date');
        
        if (!$startDate && !$endDate) {
            throw BadRequestException::invalidParameter('start_date', 'At least one date is required');
        }

        $results = $this->service->findByDateRange('created_at', $startDate, $endDate);
        
        return [
            'data' => $results,
            'count' => count($results),
            'date_range' => [
                'start' => $startDate,
                'end' => $endDate,
            ],
        ];
    }
}
```

---

## 🚀 Best Practices

### 1. **Query Building**
```php
// ✅ Use condition applier
$query = $this->conditionApplier->applyConditions($query, $conditions);

// ❌ Manual condition building
foreach ($conditions as $field => $value) {
    if (is_array($value)) {
        $query->andWhere(['IN', $field, $value]);
    } else {
        $query->andWhere([$field => $value]);
    }
}
```

### 2. **Parameter Binding**
```php
// ✅ Use parameterized queries
$query->andWhere(['LIKE', 'name', '%' . $searchTerm . '%']);

// ❌ Manual string concatenation
$query->andWhere("name LIKE '%" . $searchTerm . "%'");
```

### 3. **Complex Conditions**
```php
// ✅ Use structured condition format
$conditions = [
    'status' => ['in' => ['active', 'pending']],
    'price' => ['between' => [100, 1000]],
    'name' => ['ilike' => 'example'],
];

// ❌ Mixed format
$conditions = [
    'status' => ['active', 'pending'],
    'price' => ['>=', 100, '<=', 1000],
];
```

---

## 📊 Performance Considerations

### 1. **Query Optimization**
- Use appropriate indexes for filtered fields
- Limit result sets with pagination
- Avoid complex subqueries when possible

### 2. **Condition Application**
- Apply conditions in optimal order
- Use efficient operators
- Minimize query complexity

### 3. **Memory Usage**
- Use fetchAll() for small result sets
- Use fetch() for single records
- Process large results in chunks

---

## 🎯 Summary

Query building utilities provide a structured, type-safe way to build database queries in the Yii3 API application. Key benefits include:

- **🛡️ Security**: Parameterized queries prevent SQL injection
- **🔧 Flexibility**: Dynamic condition building
- **📝 Type Safety**: Strong typing prevents errors
- **🧪 Testability**: Easy to unit test query logic
- **⚡ Performance**: Efficient query construction
- **🔄 Reusability**: Composable query components

By following the patterns and best practices outlined in this guide, you can build robust, maintainable query building for your Yii3 API application! 🚀
