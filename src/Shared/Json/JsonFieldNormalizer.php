<?php

declare(strict_types=1);

namespace App\Shared\Json;

final class JsonFieldNormalizer
{
    /**
     * @param array<string, mixed> $row
     * @param string[]             $jsonFields
     *
     * @return array<string, mixed>
     */
    public function normalizeRow(array $row, array $jsonFields): array
    {
        foreach ($jsonFields as $field) {
            if (!\array_key_exists($field, $row)) {
                continue;
            }

            $value = $row[$field];

            if (\is_string($value)) {
                $decoded     = \json_decode($value, true);
                $row[$field] = \is_array($decoded) ? $decoded : [];
            }
        }

        return $row;
    }

    /**
     * @param array<int, array<string, mixed>> $rows
     * @param string[]                         $jsonFields
     *
     * @return array<int, array<string, mixed>>
     */
    public function normalizeRows(array $rows, array $jsonFields): array
    {
        return \array_map(
            fn (array $row) => $this->normalizeRow($row, $jsonFields),
            $rows,
        );
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
