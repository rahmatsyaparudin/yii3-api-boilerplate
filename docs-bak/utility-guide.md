# Utility Guide

## 📋 Overview

Utility functions provide helpful, reusable functionality for common operations throughout the Yii3 API application. These utilities simplify complex operations and ensure consistent behavior across the codebase.

---

## 🏗️ Utility Architecture

### Directory Structure

```
src/Shared/Utility/
├── Arrays.php            # Array manipulation utilities
└── JsonDataHydrator.php  # JSON data hydration utilities
```

### Design Principles

#### **1. **Reusability**
- Generic utility functions
- No coupling to specific domains
- Easy to use across different contexts

#### **2. **Performance**
- Optimized algorithms
- Minimal memory usage
- Efficient data processing

#### **3. **Type Safety**
- Strong typing with PHP 8+ features
- Proper parameter validation
- Clear return types

#### **4. **Error Handling**
- Graceful error handling
- Meaningful error messages
- Fail-safe behavior

---

## 📁 Utility Components

### 1. Arrays

**Purpose**: Advanced array manipulation and processing utilities

```php
<?php

declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * Array manipulation utilities
 */
final class Arrays
{
    /**
     * Flatten multidimensional array
     */
    public static function flatten(array $array, string $separator = '.'): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $flattened = self::flatten($value, $separator);
                foreach ($flattened as $subKey => $subValue) {
                    $result[$key . $separator . $subKey] = $subValue;
                }
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Unflatten array with dot notation
     */
    public static function unflatten(array $array, string $separator = '.'): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            if (str_contains($key, $separator)) {
                $keys = explode($separator, $key);
                $current = &$result;
                
                foreach ($keys as $i => $subKey) {
                    if ($i === count($keys) - 1) {
                        $current[$subKey] = $value;
                    } else {
                        if (!isset($current[$subKey]) || !is_array($current[$subKey])) {
                            $current[$subKey] = [];
                        }
                        $current = &$current[$subKey];
                    }
                }
            } else {
                $result[$key] = $value;
            }
        }
        
        return $result;
    }

    /**
     * Filter array by key pattern
     */
    public static function filterByKeyPattern(array $array, string $pattern): array
    {
        return array_filter(
            $array,
            fn($key) => preg_match($pattern, $key),
            ARRAY_FILTER_USE_KEY
        );
    }

    /**
     * Filter array by value callback
     */
    public static function filterByCallback(array $array, callable $callback): array
    {
        return array_filter($array, $callback, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Map array with key preservation
     */
    public static function mapWithKeys(array $array, callable $callback): array
    {
        $result = [];
        
        foreach ($array as $key => $value) {
            $result[$key] = $callback($value, $key);
        }
        
        return $result;
    }

    /**
     * Group array by key or callback
     */
    public static function groupBy(array $array, string|callable $keyOrCallback): array
    {
        $result = [];
        
        foreach ($array as $item) {
            $key = is_callable($keyOrCallback) 
                ? $keyOrCallback($item) 
                : ($item[$keyOrCallback] ?? null);
                
            if ($key !== null) {
                $result[$key][] = $item;
            }
        }
        
        return $result;
    }

    /**
     * Sort array by multiple fields
     */
    public static function sortByMultiple(array $array, array $fields): array
    {
        usort($array, function ($a, $b) use ($fields) {
            foreach ($fields as $field => $direction) {
                $aValue = $a[$field] ?? null;
                $bValue = $b[$field] ?? null;
                
                if ($aValue === $bValue) {
                    continue;
                }
                
                $comparison = $aValue <=> $bValue;
                return $direction === 'desc' ? -$comparison : $comparison;
            }
            
            return 0;
        });
        
        return $array;
    }

    /**
     * Get nested value using dot notation
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        $keys = explode('.', $key);
        $current = $array;
        
        foreach ($keys as $subKey) {
            if (!is_array($current) || !array_key_exists($subKey, $current)) {
                return $default;
            }
            
            $current = $current[$subKey];
        }
        
        return $current;
    }

    /**
     * Set nested value using dot notation
     */
    public static function set(array &$array, string $key, mixed $value): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $i => $subKey) {
            if ($i === count($keys) - 1) {
                $current[$subKey] = $value;
            } else {
                if (!isset($current[$subKey]) || !is_array($current[$subKey])) {
                    $current[$subKey] = [];
                }
                $current = &$current[$subKey];
            }
        }
    }

    /**
     * Check if nested key exists
     */
    public static function has(array $array, string $key): bool
    {
        $keys = explode('.', $key);
        $current = $array;
        
        foreach ($keys as $subKey) {
            if (!is_array($current) || !array_key_exists($subKey, $current)) {
                return false;
            }
            
            $current = $current[$subKey];
        }
        
        return true;
    }

    /**
     * Remove nested key using dot notation
     */
    public static function remove(array &$array, string $key): void
    {
        $keys = explode('.', $key);
        $current = &$array;
        
        foreach ($keys as $i => $subKey) {
            if ($i === count($keys) - 1) {
                unset($current[$subKey]);
                return;
            }
            
            if (!is_array($current) || !array_key_exists($subKey, $current)) {
                return;
            }
            
            $current = &$current[$subKey];
        }
    }

    /**
     * Get all keys from multidimensional array
     */
    public static function getAllKeys(array $array, string $separator = '.'): array
    {
        $keys = [];
        
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $subKeys = self::getAllKeys($value, $separator);
                foreach ($subKeys as $subKey) {
                    $keys[] = $key . $separator . $subKey;
                }
            } else {
                $keys[] = $key;
            }
        }
        
        return $keys;
    }

    /**
     * Convert array to XML
     */
    public static function toXml(array $array, string $rootNode = 'root'): string
    {
        $xml = new \SimpleXMLElement('<' . $rootNode . '/>');
        self::arrayToXml($array, $xml);
        
        return $xml->asXML();
    }

    /**
     * Convert XML to array
     */
    public static function fromXml(string $xml): array
    {
        $xmlObject = simplexml_load_string($xml);
        
        if ($xmlObject === false) {
            return [];
        }
        
        return self::xmlToArray($xmlObject);
    }

    /**
     * Convert array to SimpleXMLElement
     */
    private static function arrayToXml(array $array, \SimpleXMLElement $xml): void
    {
        foreach ($array as $key => $value) {
            if (is_array($value)) {
                if (is_numeric($key)) {
                    $key = 'item_' . $key;
                }
                
                $subNode = $xml->addChild($key);
                self::arrayToXml($value, $subNode);
            } else {
                $xml->addChild($key, htmlspecialchars((string) $value));
            }
        }
    }

    /**
     * Convert SimpleXMLElement to array
     */
    private static function xmlToArray(\SimpleXMLElement $xml): array
    {
        $array = [];
        
        foreach ($xml->children() as $child) {
            $key = $child->getName();
            $value = self::xmlToArray($child);
            
            if (isset($array[$key])) {
                if (!is_array($array[$key]) || !isset($array[$key][0])) {
                    $array[$key] = [$array[$key]];
                }
                $array[$key][] = $value;
            } else {
                $array[$key] = $value;
            }
        }
        
        return $array;
    }

    /**
     * Calculate array difference recursively
     */
    public static function diffRecursive(array $array1, array $array2): array
    {
        $diff = [];
        
        foreach ($array1 as $key => $value) {
            if (!array_key_exists($key, $array2)) {
                $diff[$key] = $value;
            } elseif (is_array($value)) {
                if (is_array($array2[$key])) {
                    $subDiff = self::diffRecursive($value, $array2[$key]);
                    if (!empty($subDiff)) {
                        $diff[$key] = $subDiff;
                    }
                } else {
                    $diff[$key] = $value;
                }
            } elseif ($value !== $array2[$key]) {
                $diff[$key] = $value;
            }
        }
        
        return $diff;
    }

    /**
     * Merge arrays recursively
     */
    public static function mergeRecursive(array ...$arrays): array
    {
        $result = [];
        
        foreach ($arrays as $array) {
            foreach ($array as $key => $value) {
                if (is_array($value) && isset($result[$key]) && is_array($result[$key])) {
                    $result[$key] = self::mergeRecursive($result[$key], $value);
                } else {
                    $result[$key] = $value;
                }
            }
        }
        
        return $result;
    }

    /**
     * Pick specific keys from array
     */
    public static function pick(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Exclude specific keys from array
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }

    /**
     * Convert array to query string
     */
    public static function toQueryString(array $array): string
    {
        return http_build_query(self::flatten($array));
    }

    /**
     * Convert query string to array
     */
    public static function fromQueryString(string $queryString): array
    {
        parse_str($queryString, $result);
        return self::unflatten($result);
    }

    /**
     * Validate array structure
     */
    public static function validateStructure(array $array, array $structure): bool
    {
        foreach ($structure as $key => $expectedType) {
            if (!array_key_exists($key, $array)) {
                return false;
            }
            
            $value = $array[$key];
            
            if ($expectedType === 'array') {
                if (!is_array($value)) {
                    return false;
                }
            } elseif ($expectedType === 'string') {
                if (!is_string($value)) {
                    return false;
                }
            } elseif ($expectedType === 'int') {
                if (!is_int($value)) {
                    return false;
                }
            } elseif ($expectedType === 'float') {
                if (!is_float($value)) {
                    return false;
                }
            } elseif ($expectedType === 'bool') {
                if (!is_bool($value)) {
                    return false;
                }
            } elseif (is_array($expectedType)) {
                if (!is_array($value) || !self::validateStructure($value, $expectedType)) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Get array statistics
     */
    public static function getStats(array $array): array
    {
        $stats = [
            'count' => count($array),
            'keys' => array_keys($array),
            'values' => array_values($array),
            'is_associative' => array_keys($array) !== range(0, count($array) - 1),
            'max_depth' => self::getMaxDepth($array),
            'total_size' => strlen(serialize($array)),
        ];
        
        // Add numeric statistics if all values are numeric
        if (self::allNumeric($array)) {
            $stats['numeric'] = [
                'sum' => array_sum($array),
                'avg' => array_sum($array) / count($array),
                'min' => min($array),
                'max' => max($array),
            ];
        }
        
        return $stats;
    }

    /**
     * Get maximum depth of array
     */
    private static function getMaxDepth(array $array, int $depth = 0): int
    {
        $maxDepth = $depth;
        
        foreach ($array as $value) {
            if (is_array($value)) {
                $maxDepth = max($maxDepth, self::getMaxDepth($value, $depth + 1));
            }
        }
        
        return $maxDepth;
    }

    /**
     * Check if all values are numeric
     */
    private static function allNumeric(array $array): bool
    {
        foreach ($array as $value) {
            if (!is_numeric($value)) {
                return false;
            }
        }
        
        return true;
    }
}
```

**Usage Example**:
```php
// Flatten array
$flat = Arrays::flatten([
    'user' => [
        'name' => 'John',
        'email' => 'john@example.com',
    ],
    'settings' => [
        'theme' => 'dark',
        'notifications' => true,
    ],
]);
// Result: ['user.name' => 'John', 'user.email' => 'john@example.com', 'settings.theme' => 'dark', 'settings.notifications' => true]

// Get nested value
$name = Arrays::get($data, 'user.profile.name', 'Default Name');

// Set nested value
Arrays::set($data, 'user.profile.name', 'Jane');

// Group by field
$usersByRole = Arrays::groupBy($users, 'role');

// Sort by multiple fields
$sorted = Arrays::sortByMultiple($users, ['name' => 'asc', 'age' => 'desc']);

// Convert to XML
$xml = Arrays::toXml($data, 'users');

// Get array statistics
$stats = Arrays::getStats($data);
```

---

### 2. JsonDataHydrator

**Purpose**: JSON data hydration and object mapping utilities

```php
<?php

declare(strict_types=1);

namespace App\Shared\Utility;

use App\Shared\Exception\BadRequestException;

/**
 * JSON Data Hydrator
 */
final class JsonDataHydrator
{
    /**
     * Hydrate object from JSON data
     */
    public static function hydrate(string $json, string $className, array $options = []): object
    {
        $data = self::decodeJson($json);
        return self::hydrateFromArray($data, $className, $options);
    }

    /**
     * Hydrate object from array data
     */
    public static function hydrateFromArray(array $data, string $className, array $options = []): object
    {
        if (!class_exists($className)) {
            throw new BadRequestException("Class {$className} does not exist");
        }

        $reflection = new \ReflectionClass($className);
        $instance = $reflection->newInstanceWithoutConstructor();

        foreach ($data as $key => $value) {
            $propertyName = self::mapKeyToProperty($key, $options['key_map'] ?? []);
            
            if ($reflection->hasProperty($propertyName)) {
                $property = $reflection->getProperty($propertyName);
                $property->setAccessible(true);
                
                $typedValue = self::typeCastValue($value, $property, $options);
                $property->setValue($instance, $typedValue);
            }
        }

        return $instance;
    }

    /**
     * Extract object to array
     */
    public static function extract(object $object, array $options = []): array
    {
        $reflection = new \ReflectionClass($object);
        $properties = $reflection->getProperties();
        
        $data = [];
        
        foreach ($properties as $property) {
            $property->setAccessible(true);
            $value = $property->getValue($object);
            
            $key = self::mapPropertyToKey($property->getName(), $options['key_map'] ?? []);
            
            if ($value !== null) {
                $data[$key] = self::formatValue($value, $options);
            }
        }
        
        return $data;
    }

    /**
     * Convert object to JSON
     */
    public static function toJson(object $object, array $options = []): string
    {
        $data = self::extract($object, $options);
        return json_encode($data, $options['json_flags'] ?? JSON_PRETTY_PRINT);
    }

    /**
     * Decode JSON string
     */
    private static function decodeJson(string $json): array
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException('Invalid JSON: ' . json_last_error_msg());
        }
        
        if (!is_array($data)) {
            throw new BadRequestException('JSON must be an array or object');
        }
        
        return $data;
    }

    /**
     * Map key to property name
     */
    private static function mapKeyToProperty(string $key, array $keyMap): string
    {
        return $keyMap[$key] ?? self::toCamelCase($key);
    }

    /**
     * Map property name to key
     */
    private static function mapPropertyToKey(string $property, array $keyMap): string
    {
        $reverseMap = array_flip($keyMap);
        return $reverseMap[$property] ?? self::toSnakeCase($property);
    }

    /**
     * Convert to camel case
     */
    private static function toCamelCase(string $string): string
    {
        return lcfirst(str_replace('_', '', ucwords($string, '_')));
    }

    /**
     * Convert to snake case
     */
    private static function toSnakeCase(string $string): string
    {
        return strtolower(preg_replace('/([A-Z])/', '_$1', $string));
    }

    /**
     * Type cast value based on property type
     */
    private static function typeCastValue(mixed $value, \ReflectionProperty $property, array $options): mixed
    {
        $type = $property->getType();
        
        if ($type === null) {
            return $value;
        }

        $typeName = $type->getName();
        
        return match ($typeName) {
            'int' => self::castToInt($value),
            'float' => self::castToFloat($value),
            'string' => self::castToString($value),
            'bool' => self::castToBool($value),
            'array' => self::castToArray($value),
            'DateTime' => self::castToDateTime($value, $options['date_format'] ?? 'Y-m-d H:i:s'),
            'DateTimeImmutable' => self::castToDateTimeImmutable($value, $options['date_format'] ?? 'Y-m-d H:i:s'),
            default => self::castToObject($value, $typeName, $options),
        };
    }

    /**
     * Cast to integer
     */
    private static function castToInt(mixed $value): int
    {
        if (is_int($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (int) $value;
        }
        
        throw new BadRequestException("Cannot cast to int: " . gettype($value));
    }

    /**
     * Cast to float
     */
    private static function castToFloat(mixed $value): float
    {
        if (is_float($value)) {
            return $value;
        }
        
        if (is_numeric($value)) {
            return (float) $value;
        }
        
        throw new BadRequestException("Cannot cast to float: " . gettype($value));
    }

    /**
     * Cast to string
     */
    private static function castToString(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }
        
        if (is_numeric($value) || is_bool($value)) {
            return (string) $value;
        }
        
        throw new BadRequestException("Cannot cast to string: " . gettype($value));
    }

    /**
     * Cast to boolean
     */
    private static function castToBool(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $lower = strtolower($value);
            return in_array($lower, ['true', '1', 'yes', 'on'], true);
        }
        
        if (is_numeric($value)) {
            return $value > 0;
        }
        
        throw new BadRequestException("Cannot cast to bool: " . gettype($value));
    }

    /**
     * Cast to array
     */
    private static function castToArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            $decoded = json_decode($value, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }
        
        throw new BadRequestException("Cannot cast to array: " . gettype($value));
    }

    /**
     * Cast to DateTime
     */
    private static function castToDateTime(mixed $value, string $format): \DateTime
    {
        if ($value instanceof \DateTime) {
            return $value;
        }
        
        if (is_string($value)) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
            
            // Try default format
            $date = new \DateTime($value);
            return $date;
        }
        
        throw new BadRequestException("Cannot cast to DateTime: " . gettype($value));
    }

    /**
     * Cast to DateTimeImmutable
     */
    private static function castToDateTimeImmutable(mixed $value, string $format): \DateTimeImmutable
    {
        if ($value instanceof \DateTimeImmutable) {
            return $value;
        }
        
        if ($value instanceof \DateTime) {
            return \DateTimeImmutable::createFromMutable($value);
        }
        
        if (is_string($value)) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);
            if ($date !== false) {
                return $date;
            }
            
            // Try default format
            $date = new \DateTimeImmutable($value);
            return $date;
        }
        
        throw new BadRequestException("Cannot cast to DateTimeImmutable: " . gettype($value));
    }

    /**
     * Cast to object
     */
    private static function castToObject(mixed $value, string $className, array $options): object
    {
        if (is_object($value) && $value instanceof $className) {
            return $value;
        }
        
        if (is_array($value)) {
            return self::hydrateFromArray($value, $className, $options);
        }
        
        throw new BadRequestException("Cannot cast to {$className}: " . gettype($value));
    }

    /**
     * Format value for output
     */
    private static function formatValue(mixed $value, array $options): mixed
    {
        if ($value instanceof \DateTimeInterface) {
            return $value->format($options['date_format'] ?? 'Y-m-d H:i:s');
        }
        
        if (is_object($value)) {
            return self::extract($value, $options);
        }
        
        if (is_array($value)) {
            return array_map(fn($item) => self::formatValue($item, $options), $value);
        }
        
        return $value;
    }

    /**
     * Validate JSON schema
     */
    public static function validateSchema(string $json, array $schema): bool
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
        
        return self::validateArraySchema($data, $schema);
    }

    /**
     * Validate array schema
     */
    private static function validateArraySchema(array $data, array $schema): bool
    {
        foreach ($schema as $key => $rules) {
            if (!array_key_exists($key, $data)) {
                return false;
            }
            
            $value = $data[$key];
            
            if (isset($rules['type'])) {
                if (!self::validateType($value, $rules['type'])) {
                    return false;
                }
            }
            
            if (isset($rules['required']) && $rules['required'] && $value === null) {
                return false;
            }
            
            if (isset($rules['min']) && is_numeric($value) && $value < $rules['min']) {
                return false;
            }
            
            if (isset($rules['max']) && is_numeric($value) && $value > $rules['max']) {
                return false;
            }
            
            if (isset($rules['pattern']) && is_string($value) && !preg_match($rules['pattern'], $value)) {
                return false;
            }
            
            if (isset($rules['enum']) && !in_array($value, $rules['enum'], true)) {
                return false;
            }
            
            if (isset($rules['schema']) && is_array($value)) {
                if (!self::validateArraySchema($value, $rules['schema'])) {
                    return false;
                }
            }
        }
        
        return true;
    }

    /**
     * Validate type
     */
    private static function validateType(mixed $value, string $type): bool
    {
        return match ($type) {
            'string' => is_string($value),
            'int', 'integer' => is_int($value),
            'float', 'number' => is_float($value) || is_int($value),
            'bool', 'boolean' => is_bool($value),
            'array' => is_array($value),
            'object' => is_object($value),
            'null' => $value === null,
            default => false,
        };
    }

    /**
     * Convert JSON to CSV
     */
    public static function jsonToCsv(string $json, array $options = []): string
    {
        $data = json_decode($json, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new BadRequestException('Invalid JSON');
        }
        
        if (!is_array($data) || empty($data)) {
            return '';
        }
        
        $headers = array_keys($data[0]);
        $csv = implode(',', $headers) . "\n";
        
        foreach ($data as $row) {
            $values = [];
            foreach ($headers as $header) {
                $value = $row[$header] ?? '';
                $values[] = self::escapeCsvValue($value);
            }
            $csv .= implode(',', $values) . "\n";
        }
        
        return $csv;
    }

    /**
     * Escape CSV value
     */
    private static function escapeCsvValue(mixed $value): string
    {
        $value = (string) $value;
        
        if (str_contains($value, ',') || str_contains($value, '"') || str_contains($value, "\n")) {
            $value = '"' . str_replace('"', '""', $value) . '"';
        }
        
        return $value;
    }
}
```

**Usage Example**:
```php
// Hydrate object from JSON
$user = JsonDataHydrator::hydrate($json, User::class, [
    'key_map' => [
        'user_name' => 'name',
        'user_email' => 'email',
    ],
]);

// Extract object to array
$data = JsonDataHydrator::extract($user, [
    'key_map' => [
        'name' => 'user_name',
        'email' => 'user_email',
    ],
]);

// Convert to JSON
$json = JsonDataHydrator::toJson($user, [
    'json_flags' => JSON_PRETTY_PRINT,
]);

// Validate schema
$schema = [
    'name' => ['type' => 'string', 'required' => true],
    'email' => ['type' => 'string', 'required' => true, 'pattern' => '/^[^@]+@[^@]+\.[^@]+$/'],
    'age' => ['type' => 'int', 'min' => 0, 'max' => 120],
];
$isValid = JsonDataHydrator::validateSchema($json, $schema);

// Convert to CSV
$csv = JsonDataHydrator::jsonToCsv($json);
```

---

## 🔧 Integration Patterns

### 1. **Controller Integration**
```php
final class ExampleController
{
    public function actionCreate(): array
    {
        $json = $this->request->getBody()->getContents();
        
        // Validate and hydrate
        if (!JsonDataHydrator::validateSchema($json, $this->getCreateSchema())) {
            throw BadRequestException::invalidJson('Invalid data format');
        }
        
        $command = JsonDataHydrator::hydrate($json, CreateExampleCommand::class);
        
        $result = $this->service->create($command);
        
        return JsonDataHydrator::extract($result);
    }

    private function getCreateSchema(): array
    {
        return [
            'name' => ['type' => 'string', 'required' => true, 'min_length' => 2],
            'email' => ['type' => 'string', 'required' => true],
            'status' => ['type' => 'string', 'enum' => ['active', 'inactive']],
        ];
    }
}
```

### 2. **Service Integration**
```php
final class ExampleApplicationService
{
    public function list(RequestParams $params): PaginatedResult
    {
        $data = $this->repository->findAll($params);
        
        // Transform data
        $transformed = array_map(
            fn($item) => Arrays::pick($item, ['id', 'name', 'email', 'status']),
            $data['items']
        );
        
        // Group by status
        $grouped = Arrays::groupBy($transformed, 'status');
        
        return new PaginatedResult(
            data: $transformed,
            total: $data['total'],
            page: $params->pagination->page,
            pageSize: $params->pagination->limit
        );
    }
}
```

### 3. **Repository Integration**
```php
final class ExampleRepository
{
    public function findAll(SearchCriteria $criteria): array
    {
        $query = $this->createQueryBuilder()
            ->select('*')
            ->from('example');
        
        // Apply filters
        foreach ($criteria->filters as $field => $value) {
            if (is_array($value)) {
                $query->andWhere([$field => $value]);
            } else {
                $query->andWhere([$field => $value]);
            }
        }
        
        // Apply sorting
        foreach ($criteria->sort as $field => $direction) {
            $query->addOrderBy([$field => $direction]);
        }
        
        $data = $query
            ->offset($criteria->getOffset())
            ->limit($criteria->limit)
            ->fetchAll();
        
        // Transform data
        return array_map(
            fn($item) => Arrays::except($item, ['password', 'secret']),
            $data
        );
    }
}
```

---

## 🚀 Best Practices

### 1. **Array Manipulation**
```php
// ✅ Use utility methods
$filtered = Arrays::filterByKeyPattern($data, '/^user_/');

// ❌ Manual implementation
$filtered = [];
foreach ($data as $key => $value) {
    if (preg_match('/^user_/', $key)) {
        $filtered[$key] = $value;
    }
}
```

### 2. **JSON Processing**
```php
// ✅ Use hydrator for type safety
$user = JsonDataHydrator::hydrate($json, User::class);

// ❌ Manual casting
$user = new User();
$user->setName($data['name']);
$user->setEmail($data['email']);
```

### 3. **Data Transformation**
```php
// ✅ Use utility methods
$flattened = Arrays::flatten($nestedData);

// ❌ Manual flattening
$flattened = [];
foreach ($nestedData as $key => $value) {
    if (is_array($value)) {
        foreach ($value as $subKey => $subValue) {
            $flattened[$key . '.' . $subKey] = $subValue;
        }
    } else {
        $flattened[$key] = $value;
    }
}
```

---

## 📊 Performance Considerations

### 1. **Memory Usage**
- Use references for large arrays
- Avoid unnecessary array copies
- Process data in chunks when possible

### 2. **Processing Speed**
- Use built-in PHP functions
- Optimize regex patterns
- Cache expensive operations

### 3. **JSON Processing**
- Validate before processing
- Use efficient parsing methods
- Handle large JSON files carefully

---

## 🎯 Summary

Utility functions provide essential, reusable functionality for the Yii3 API application. Key benefits include:

- **🔄 Reusability**: Generic functions for common tasks
- **🛡️ Type Safety**: Strong typing and validation
- **⚡ Performance**: Optimized algorithms
- **🧪 Testability**: Easy to unit test
- **📦 Modularity**: Focused, single-purpose functions
- **🚀 Efficiency**: Minimal overhead and fast execution

By following the patterns and best practices outlined in this guide, you can build efficient, maintainable utility functions for your Yii3 API application! 🚀
