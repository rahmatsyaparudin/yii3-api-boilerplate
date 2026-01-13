<?php

declare(strict_types=1);

return [
    // Generic HTTP / API
    'bad_request' => 'Permintaan tidak valid',
    'unauthorized' => 'Unauthorized Access',
    'forbidden' => 'Terlarang',
    'not_found' => 'Tidak ditemukan',
    'method_not_allowed' => 'Metode tidak diizinkan',
    'unsupported_media_type' => 'Tipe media tidak didukung',
    'too_many_requests' => 'Terlalu banyak permintaan',
    'internal_error' => 'Kesalahan server internal',
    'service_unavailable' => 'Layanan tidak tersedia',

    // Authentication / Authorization
    'auth.authorization_header_missing' => 'Header Authorization tidak ditemukan',
    'auth.invalid_token' => 'Token tidak valid atau sudah kedaluwarsa',
    'auth.invalid_issuer' => 'Issuer token tidak valid',
    'auth.invalid_audience' => 'Audience token tidak valid',
    'auth.missing_claim' => 'Token tidak memiliki claim yang dibutuhkan: {claim}',

    // Request / Payload
    'request.invalid_json' => 'Payload JSON tidak valid',
    'request.invalid_body' => 'Body request tidak valid',
    'request.missing_parameter' => 'Parameter wajib tidak ada: {param}',
    'request.invalid_parameter' => 'Parameter tidak valid: {param}',
    'request.host_not_allowed' => 'Host tidak diizinkan: {host}',
    'request.origin_not_allowed' => 'Origin tidak diizinkan: {origin}',

    // Filtering / Sorting / Pagination
    'filter.invalid_keys' => 'Kunci filter tidak valid: {keys}',
    'filter.not_allowed' => 'Filter tidak diizinkan: {filter}',
    'sort.invalid_field' => 'Field sort tidak valid: {field}',
    'sort.invalid_direction' => 'Arah sort tidak valid: {direction}',
    'pagination.invalid_page' => 'Nilai page tidak valid',
    'pagination.invalid_page_size' => 'Nilai page_size tidak valid',

    // Data type / format (generic, usable outside validator)
    'type.string' => '{field} harus berupa teks',
    'type.integer' => '{field} harus berupa bilangan bulat',
    'type.numeric' => '{field} harus berupa angka',
    'type.boolean' => '{field} harus bernilai benar atau salah',
    'type.array' => '{field} harus berupa array',
    'type.object' => '{field} harus berupa objek',

    'format.email' => '{field} harus berupa alamat email yang valid',
    'format.url' => '{field} harus berupa URL yang valid',
    'format.uuid' => '{field} harus berupa UUID yang valid',
    'format.date' => '{field} harus berupa tanggal yang valid',
    'format.datetime' => '{field} harus berupa datetime yang valid',
    'format.json' => '{field} harus berupa JSON yang valid',

    // Range / length
    'range.min' => '{field} minimal {min}',
    'range.max' => '{field} maksimal {max}',
    'length.min' => '{field} minimal {min} karakter',
    'length.max' => '{field} maksimal {max} karakter',

    // File / upload
    'file.invalid' => 'File tidak valid',
    'file.too_large' => 'Ukuran file terlalu besar',
    'file.invalid_type' => 'Tipe file tidak valid',

    // Resource / DB
    'resource.not_found' => '{resource} tidak ditemukan',
    'resource.conflict' => 'Terjadi konflik pada {resource}',
    'resource.already_exists' => '{resource} sudah ada',

    // Misc
    'operation.not_allowed' => 'Operasi tidak diizinkan',

    'access.denied' => 'Akses ditolak',
    'access.insufficient_permissions' => 'Anda tidak memiliki izin yang cukup untuk melakukan tindakan ini',
    'access.auth_required' => 'Autentikasi diperlukan untuk mengakses sumber daya ini',

    'rate_limit.exceeded' => 'Terlalu banyak permintaan. Silakan coba lagi dalam {seconds} detik.',
    'rate_limit.try_again' => 'Silakan coba lagi dalam {seconds} detik.',
];
