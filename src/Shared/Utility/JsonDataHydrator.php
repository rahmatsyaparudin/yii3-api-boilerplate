<?php

declare(strict_types=1);

namespace App\Shared\Utility;

final class JsonDataHydrator
{
    /**
     * Normalize JSON fields in row(s) data
     * 
     * @param array<string, mixed>|array<int, array<string, mixed>> $data
     * @param string[]                                               $jsonFields
     *
     * @return array<string, mixed>|array<int, array<string, mixed>>
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
     * @param array<int|string, mixed> $row
     * @param string[]             $jsonFields
     *
     * @return array<int|string, mixed>
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
     * @param array<string, mixed> $array
     *
     * @return array<string, mixed>
     */
    public function denormalizeArray(array $array): array
    {
        return $array;
    }
}
