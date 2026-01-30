<?php

declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * JSON Data Hydrator Utility
 * 
 * This final class provides utilities for normalizing and denormalizing JSON
 * fields in database row data. It handles conversion between JSON strings
 * and PHP arrays for proper data hydration and serialization.
 * 
 * @package App\Shared\Utility
 * 
 * @example
 * // Normalize single row with JSON fields
 * $hydrator = new JsonDataHydrator();
 * $row = ['id' => 1, 'metadata' => '{"key": "value"}', 'settings' => '{"theme": "dark"}'];
 * $normalized = $hydrator->normalize($row, ['metadata', 'settings']);
 * // Result: ['id' => 1, 'metadata' => ['key' => 'value'], 'settings' => ['theme' => 'dark']]
 * 
 * @example
 * // Normalize multiple rows
 * $rows = [
 *     ['id' => 1, 'data' => '{"name": "John"}'],
 *     ['id' => 2, 'data' => '{"name": "Jane"}']
 * ];
 * $normalized = $hydrator->normalize($rows, ['data']);
 * // Result: [['id' => 1, 'data' => ['name' => 'John']], ['id' => 2, 'data' => ['name' => 'Jane']]]
 * 
 * @example
 * // In repository layer
 * public function findAll(): array
 * {
 *     $rows = $this->query->all();
 *     return $this->hydrator->normalize($rows, ['metadata', 'settings']);
 * }
 * 
 * @example
 * // In service layer with named arguments
 * public function processUserData(array $userData): array
 * {
 *     $hydrator = new JsonDataHydrator();
 *     $normalized = $hydrator->normalize(
 *         data: $userData,
 *         jsonFields: ['preferences', 'profile']
 *     );
 *     return $this->processor->process($normalized);
 * }
 * 
 * @example
 * // Handling invalid JSON
 * $row = ['id' => 1, 'data' => 'invalid json'];
 * $normalized = $hydrator->normalize($row, ['data']);
 * // Result: ['id' => 1, 'data' => []] (empty array for invalid JSON)
 */
final class JsonDataHydrator
{
    /**
     * Normalize JSON fields in row(s) data
     * 
     * Converts JSON string fields to PHP arrays for both single rows
     * and collections of rows. Automatically detects single vs multiple rows.
     * 
     * @param array<string, mixed>|array<int, array<string, mixed>> $data Row data or collection of rows
     * @param string[] $jsonFields List of field names that contain JSON data
     * @return array<string, mixed>|array<int, array<string, mixed>> Normalized data
     * 
     * @example
     * // Single row normalization
     * $row = ['id' => 1, 'config' => '{"theme": "dark"}'];
     * $normalized = $hydrator->normalize($row, ['config']);
     * // Result: ['id' => 1, 'config' => ['theme' => 'dark']]
     * 
     * @example
     * // Multiple rows normalization
     * $rows = [
     *     ['id' => 1, 'data' => '{"name": "John"}'],
     *     ['id' => 2, 'data' => '{"name": "Jane"}']
     * ];
     * $normalized = $hydrator->normalize($rows, ['data']);
     * // Result: [
     *     ['id' => 1, 'data' => ['name' => 'John']],
     *     ['id' => 2, 'data' => ['name' => 'Jane']]
     * ]
     * 
     * @example
     * // Using named arguments
     * $normalized = $hydrator->normalize(
     *     data: $databaseRows,
     *     jsonFields: ['metadata', 'settings', 'preferences']
     * );
     * 
     * @example
     * // In repository with multiple JSON fields
     * public function findWithMetadata(): array
     * {
     *     $rows = $this->createQuery()
     *         ->select(['id', 'name', 'metadata', 'settings', 'preferences'])
     *         ->all();
     *     
     *     return $this->hydrator->normalize(
     *         data: $rows,
     *         jsonFields: ['metadata', 'settings', 'preferences']
     *     );
     * }
     * 
     * @example
     * // Handling edge cases
     * $row = ['id' => 1, 'data' => null, 'config' => ''];
     * $normalized = $hydrator->normalize($row, ['data', 'config']);
     * // Result: ['id' => 1, 'data' => null, 'config' => ''] (unchanged for non-string values)
     */
    public function normalize(array $data, array $jsonFields): array
    {
        // Check if it's multiple rows (array of arrays) or single row
        if (isset($data[0]) && is_array($data[0])) {
            // Multiple rows
            return array_map(
                fn(array $row) => $this->normalizeRow($row, $jsonFields),
                $data
            );
        }
        
        // Single row
        return $this->normalizeRow($data, $jsonFields);
    }

    /**
     * Normalize JSON fields in a single row
     * 
     * Converts JSON string fields to PHP arrays for a single data row.
     * Handles invalid JSON gracefully by converting to empty arrays.
     * 
     * @param array<int|string, mixed> $row Single row data
     * @param string[] $jsonFields List of field names that contain JSON data
     * @return array<int|string, mixed> Normalized row data
     * 
     * @example
     * // Basic normalization
     * $row = ['id' => 1, 'metadata' => '{"key": "value"}'];
     * $normalized = $this->normalizeRow($row, ['metadata']);
     * // Result: ['id' => 1, 'metadata' => ['key' => 'value']]
     * 
     * @example
     * // Multiple fields
     * $row = [
     *     'id' => 1,
     *     'config' => '{"theme": "dark"}',
     *     'settings' => '{"notifications": true}'
     * ];
     * $normalized = $this->normalizeRow($row, ['config', 'settings']);
     * // Result: [
     *     'id' => 1,
     *     'config' => ['theme' => 'dark'],
     *     'settings' => ['notifications' => true]
     * ]
     * 
     * @example
     * // Invalid JSON handling
     * $row = ['id' => 1, 'data' => 'invalid json', 'config' => '{"valid": true}'];
     * $normalized = $this->normalizeRow($row, ['data', 'config']);
     * // Result: ['id' => 1, 'data' => [], 'config' => ['valid' => true]]
     * 
     * @example
     * // Non-string values are ignored
     * $row = ['id' => 1, 'data' => ['already' => 'array'], 'config' => '{"json": "string"}'];
     * $normalized = $this->normalizeRow($row, ['data', 'config']);
     * // Result: ['id' => 1, 'data' => ['already' => 'array'], 'config' => ['json' => 'string']]
     * 
     * @example
     * // Missing fields are ignored
     * $row = ['id' => 1, 'name' => 'John'];
     * $normalized = $this->normalizeRow($row, ['metadata', 'settings']);
     * // Result: ['id' => 1, 'name' => 'John'] (unchanged)
     */
    private function normalizeRow(array $row, array $jsonFields): array
    {
        foreach ($jsonFields as $field) {
            if (!array_key_exists($field, $row)) {
                continue;
            }

            $value = $row[$field];

            if (is_string($value)) {
                $decoded     = json_decode($value, true);
                $row[$field] = is_array($decoded) ? $decoded : [];
            }
        }

        return $row;
    }

    /**
     * Denormalize array data
     * 
     * Currently a placeholder method that returns the input array unchanged.
     * Intended for future implementation of array-to-JSON conversion.
     * 
     * @param array<string, mixed> $array Array data to denormalize
     * @return array<string, mixed> Denormalized array data
     * 
     * @example
     * // Current implementation (no-op)
     * $data = ['key' => 'value'];
     * $denormalized = $hydrator->denormalizeArray($data);
     * // Result: ['key' => 'value'] (unchanged)
     * 
     * @example
     * // Future implementation might convert arrays to JSON
     * // $data = ['config' => ['theme' => 'dark']];
     * // $denormalized = $hydrator->denormalizeArray($data);
     * // Future Result: ['config' => '{"theme":"dark"}']
     * 
     * @example
     * // In repository for saving data
     * public function save(array $data): void
     * {
     *     $denormalized = $this->hydrator->denormalizeArray($data);
     *     $this->db->insert('users', $denormalized);
     * }
     * 
     * @example
     * // In service layer
     * public function updateSettings(int $userId, array $settings): void
     * {
     *     $data = ['id' => $userId, 'settings' => $settings];
     *     $denormalized = $this->hydrator->denormalizeArray($data);
     *     $this->repository->update($denormalized);
     * }
     * 
     * @example
     * // Using named arguments
     * $denormalized = $hydrator->denormalizeArray(
     *     array: $processedData
     * );
     */
    public function denormalizeArray(array $array): array
    {
        return $array;
    }
}
