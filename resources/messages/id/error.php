<?php

declare(strict_types=1);

return [
    // Generic HTTP / API
    'bad_request' => 'Permintaan tidak valid atau formatnya salah.',
    'unauthorized' => 'Autentikasi diperlukan atau telah gagal.',
    'forbidden' => 'Anda tidak memiliki izin untuk mengakses resource ini.',
    'not_found' => 'Resource yang diminta tidak ditemukan.',
    'method_not_allowed' => 'Metode HTTP yang digunakan tidak diizinkan untuk endpoint ini.',
    'unsupported_media_type' => 'Tipe media pada permintaan tidak didukung.',
    'too_many_requests' => 'Terlalu banyak permintaan dalam waktu singkat.',
    'internal_error' => 'Terjadi kesalahan internal pada server.',
    'service_unavailable' => 'Layanan sedang tidak tersedia untuk sementara waktu.',

    // Authentication / Authorization
    'auth.authorization_header_missing' => 'Header Authorization tidak ditemukan.',
    'auth.invalid_token' => 'Token akses tidak valid atau telah kedaluwarsa.',
    'auth.invalid_issuer' => 'Issuer token tidak valid.',
    'auth.invalid_audience' => 'Audience token tidak valid.',
    'auth.missing_claim' => 'Token tidak memiliki claim yang diperlukan: {claim}.',

    // Request / Payload
    'request.invalid_json' => 'Body request berisi JSON yang tidak valid.',
    'request.invalid_body' => 'Body request tidak valid atau formatnya salah.',
    'request.missing_parameter' => 'Parameter wajib tidak ditemukan: {param}.',
    'request.invalid_parameter' => 'Nilai parameter tidak valid: {param}.',
    'request.host_not_allowed' => 'Host tidak diizinkan: {host}.',
    'request.origin_not_allowed' => 'Origin tidak diizinkan: {origin}.',

    // Filtering / Sorting / Pagination
    'filter.invalid_keys' => 'Terdapat field yang tidak didukung dalam request: {keys}.',
    'filter.not_allowed' => 'Filter tidak diizinkan: {filter}.',
    'sort.invalid_field' => 'Field untuk sorting tidak valid: {field}.',
    'sort.invalid_direction' => 'Arah sorting tidak valid: {direction}.',
    'pagination.invalid_page' => 'Nilai halaman (page) tidak valid.',
    'pagination.invalid_page_size' => 'Nilai ukuran halaman (page size) tidak valid.',

    // Validation
    'validation.failed' => 'Validasi gagal. Silakan periksa data yang Anda berikan.',

    // Data type / format (generic, reusable)
    'type.string' => '{field} harus berupa string.',
    'type.integer' => '{field} harus berupa bilangan bulat.',
    'type.numeric' => '{field} harus berupa angka.',
    'type.boolean' => '{field} harus berupa nilai boolean.',
    'type.array' => '{field} harus berupa array.',
    'type.object' => '{field} harus berupa objek.',

    'format.email' => '{field} harus berupa alamat email yang valid.',
    'format.url' => '{field} harus berupa URL yang valid.',
    'format.uuid' => '{field} harus berupa UUID yang valid.',
    'format.date' => '{field} harus berupa tanggal yang valid.',
    'format.datetime' => '{field} harus berupa tanggal dan waktu yang valid.',
    'format.json' => '{field} harus berisi JSON yang valid.',

    // Range / length
    'range.min' => '{field} harus bernilai minimal {min}.',
    'range.max' => '{field} tidak boleh melebihi {max}.',
    'length.min' => '{field} harus memiliki minimal {min} karakter.',
    'length.max' => '{field} tidak boleh melebihi {max} karakter.',

    // File / upload
    'file.invalid' => 'File yang diunggah tidak valid.',
    'file.too_large' => 'Ukuran file yang diunggah terlalu besar.',
    'file.invalid_type' => 'Tipe file yang diunggah tidak diizinkan.',

    // Resource
    'resource.not_found' => '{resource} dengan {field}: {value} tidak ditemukan.',
    'resource.conflict' => 'Terjadi konflik pada {resource}.',
    'resource.already_exists' => '{resource} sudah tersedia.',
    'resource.cannot_update' => 'Tidak dapat memperbarui {resource}. Perubahan status dari "{current_status}" ke "{status}" tidak diizinkan.',
    'resource.update_not_allowed_by_status' => 'Perubahan data tidak diizinkan untuk {resource} saat statusnya "{current_status}".',
    'resource.status_already_set' => 'Tidak dapat memperbarui {resource}. Status sudah "{current_status}".',

    // Misc
    'operation.not_allowed' => 'Operasi ini tidak diizinkan.',

    'access.denied' => 'Akses ke resource ini ditolak.',
    'access.insufficient_permissions' => 'Anda tidak memiliki izin yang cukup untuk melakukan tindakan ini.',
    'access.auth_required' => 'Autentikasi diperlukan untuk mengakses resource ini.',

    'rate_limit.exceeded' => 'Terlalu banyak permintaan. Silakan coba lagi dalam {seconds} detik.',
    'rate_limit.try_again' => 'Silakan coba lagi dalam {seconds} detik.',

    ];
