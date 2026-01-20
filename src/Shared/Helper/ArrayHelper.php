<?php

declare(strict_types=1);

namespace App\Shared\Helper;

/**
 * Array Helper Functions
 * 
 * Utility functions for array operations that can be used globally
 * throughout the application.
 */
final class ArrayHelper
{
    /**
     * Check if there are any dirty data changes
     * 
     * @param array $after The new data to be updated
     * @param array $before The current entity data
     * @param array $exclude Array of fields to exclude (supports dot notation for nested fields)
     * @return bool True if there are actual changes
     */
    public static function hasDirtyData(array $after, array $before, array $exclude = []): bool
    {
        $dirtyData = self::getDirtyData($after, $before, $exclude);
        return !empty($dirtyData);
    }
    
    /**
     * Get dirty data from update array by comparing with existing data
     * 
     * This function identifies which fields have actually changed by comparing
     * the new update data with existing entity data. It supports nested exclude
     * patterns like 'detail_info.change_log' to avoid false positives for
     * audit trail updates.
     * 
     * @param array $after The new data to be updated
     * @param array $before The current entity data
     * @param array $exclude Array of fields to exclude (supports dot notation for nested fields)
     * @return array Array of fields that have actually changed
     */
    public static function getDirtyData(array $after, array $before, array $exclude = []): array
    {
        return array_filter(
            $after,
            function ($newValue, $key) use ($before, $exclude) {
                // Check if field should be excluded
                if (self::shouldExcludeField($key, $newValue, $exclude)) {
                    return false;
                }
                
                $existingValue = $before[$key] ?? null;
                
                // Return true if value has actually changed
                return $existingValue !== $newValue;
            },
            ARRAY_FILTER_USE_BOTH
        );
    }
    
    /**
     * Check if a field should be excluded based on exclude patterns
     * 
     * @param string $key The field key
     * @param mixed $value The field value
     * @param array $exclude Array of exclude patterns (supports dot notation)
     * @return bool True if field should be excluded
     */
    private static function shouldExcludeField(string $key, mixed $value, array $exclude): bool
    {
        foreach ($exclude as $pattern) {
            // Handle nested patterns like 'detail_info.change_log'
            if (str_contains($pattern, '.')) {
                [$parentKey, $nestedKey] = explode('.', $pattern, 2);
                
                // Check if this is the parent field and nested key exists in value
                if ($key === $parentKey && is_array($value) && array_key_exists($nestedKey, $value)) {
                    return true;
                }
            } else {
                // Simple field exclusion
                if ($key === $pattern) {
                    return true;
                }
            }
        }
        
        return false;
    }
    
    /**
     * Get updatable keys from data array (excluding id and status)
     * 
     * @param array $data The data array
     * @return array Array of keys that can be updated (excluding id and status)
     */
    public static function getUpdatableKeys(array $data, array $exclude): array
    {
        return array_keys(
            array_diff_key($data, array_flip($exclude))
        );
    }

    
    /**
     * Check if array has any actual changes
     * 
     * @param array $updateData The new data
     * @param array $existingData The current data
     * @return bool True if there are actual changes
     */
    public static function hasChanges(array $after, array $before): bool
    {
        return !empty(self::getDirtyData($after, $before));
    }
    
    /**
     * Get only the fields that are different between two arrays
     * 
     * @param array $newData The new data
     * @param array $oldData The old data
     * @return array Array of differences with old and new values
     */
    public static function getDifferences(array $after, array $before): array
    {
        $differences = [];
        
        foreach ($after as $key => $newValue) {
            $oldValue = $before[$key] ?? null;
            
            if ($oldValue !== $newValue) {
                $differences[$key] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }
        
        return $differences;
    }
}
