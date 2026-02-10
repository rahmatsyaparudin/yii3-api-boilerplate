<?php

declare(strict_types=1);

namespace App\Shared\Utility;

/**
 * JsonHandler mengelola operasi encoding, decoding, dan transformasi field JSON.
 */
final class JsonHandler
{
    /**
     * Entry point utama untuk memproses field JSON dalam data (single/multiple rows).
     */
    public function handle(array $data, array $jsonFields): array
    {
        if (empty($data)) {
            return [];
        }

        // Cek jika data adalah list of arrays (multiple rows)
        if (isset($data[0]) && is_array($data[0])) {
            return array_map(
                fn(array $row) => $this->processRow($row, $jsonFields),
                $data
            );
        }

        return $this->processRow($data, $jsonFields);
    }

    /**
     * Alias untuk handle() agar kompatibel dengan kode lama jika diperlukan.
     */
    public function castFields(array $data, array $jsonFields): array
    {
        return $this->handle($data, $jsonFields);
    }

    /**
     * Decode string JSON menjadi array secara aman.
     */
    public function decode(?string $value, array $fallback = []): array
    {
        if ($value === null || $value === '') {
            return $fallback;
        }

        try {
            $decoded = json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return is_array($decoded) ? $decoded : $fallback;
        } catch (\JsonException) {
            return $fallback;
        }
    }

    /**
     * Encode data menjadi string JSON.
     */
    public function encode(mixed $value): string
    {
        try {
            return json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (\JsonException) {
            return '[]';
        }
    }

    /**
     * Validasi string JSON.
     */
    public function isValid(string $value): bool
    {
        if (empty($value)) {
            return false;
        }

        try {
            json_decode($value, true, 512, JSON_THROW_ON_ERROR);
            return true;
        } catch (\JsonException) {
            return false;
        }
    }

    /**
     * Logika internal untuk memproses satu baris data.
     */
    private function processRow(array $row, array $jsonFields): array
    {
        foreach ($jsonFields as $field) {
            if (isset($row[$field]) && is_string($row[$field])) {
                $row[$field] = $this->decode($row[$field]);
            }
        }
        return $row;
    }

    /**
     * Memperbarui data JSON yang ada dengan data baru secara mendalam (recursive).
     */
    public function merge(string $existingJson, array $newData): string
    {
        $existingArray = $this->decode($existingJson);
        $merged = array_replace_recursive($existingArray, $newData);
        return $this->encode($merged);
    }

    /**
     * Mengubah nested array menjadi flat array dengan dot notation.
     * Contoh: ['user' => ['id' => 1]] menjadi ['user.id' => 1]
     */
    public function flatten(array $array, string $prefix = ''): array
    {
        $result = [];
        foreach ($array as $key => $value) {
            $newKey = $prefix === '' ? (string)$key : $prefix . '.' . $key;
            if (is_array($value) && !empty($value)) {
                $result = array_merge($result, $this->flatten($value, $newKey));
            } else {
                $result[$newKey] = $value;
            }
        }
        return $result;
    }

    /**
     * Kebalikan dari flatten. Mengubah dot notation kembali menjadi nested array.
     */
    public function expand(array $flatArray): array
    {
        $result = [];
        foreach ($flatArray as $key => $value) {
            $keys = explode('.', (string)$key);
            $current = &$result;
            foreach ($keys as $nestedKey) {
                if (!isset($current[$nestedKey]) || !is_array($current[$nestedKey])) {
                    $current[$nestedKey] = [];
                }
                $current = &$current[$nestedKey];
            }
            $current = $value;
        }
        return $result;
    }

    /**
     * Mengambil nilai dari string JSON berdasarkan dot notation tanpa men-decode seluruhnya.
     */
    public function get(string $json, string $path, mixed $default = null): mixed
    {
        $data = $this->decode($json);
        $keys = explode('.', $path);
        
        foreach ($keys as $key) {
            if (!is_array($data) || !array_key_exists($key, $data)) {
                return $default;
            }
            $data = $data[$key];
        }
        
        return $data;
    }

    /**
     * Menyembunyikan data sensitif di dalam array berdasarkan daftar key.
     * Sangat berguna sebelum menyimpan data JSON ke dalam Log.
     */
    public function mask(array $data, array $sensitiveKeys = ['password', 'token', 'secret', 'key']): array
    {
        array_walk_recursive($data, function (&$value, $key) use ($sensitiveKeys) {
            if (in_array(strtolower((string)$key), $sensitiveKeys, true)) {
                $value = '********';
            }
        });

        return $data;
    }

    /**
     * Mempercantik tampilan JSON (Pretty Print).
     */
    public function pretty(mixed $value): string
    {
        return $this->encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    /**
     * Mengecek apakah sebuah value ada di dalam JSON (Deep Search).
     */
    public function contains(string $json, mixed $needle): bool
    {
        $data = $this->decode($json);
        $flat = $this->flatten($data); // Menggunakan fungsi flatten sebelumnya
        return in_array($needle, $flat, true);
    }

    /**
     * Membersihkan array dari nilai null atau kosong secara rekursif.
     * Sangat bagus sebelum di-encode untuk disimpan ke MongoDB.
     */
    public function filter(array $data): array
    {
        $result = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $value = $this->filter($value);
            }

            if ($value !== null && $value !== '' && $value !== []) {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    /**
     * Melakukan pengecekan cepat (regex/string) apakah string adalah JSON.
     * Lebih cepat daripada isValid() karena tidak melakukan full parsing.
     */
    public function looksLikeJson(mixed $value): bool
    {
        if (!is_string($value) || $value === '') {
            return false;
        }

        $value = trim($value);
        if ($value === '') {
            return false;
        }

        return (str_starts_with($value, '{') && str_ends_with($value, '}')) || 
               (str_starts_with($value, '[') && str_ends_with($value, ']'));
    }

    /**
     * Memastikan data yang diberikan menjadi array. 
     * Jika string JSON, di-decode. Jika sudah array, dikembalikan langsung.
     */
    public function wrap(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if ($this->looksLikeJson($value)) {
            return $this->decode((string)$value);
        }

        return $value !== null ? [$value] : [];
    }
}