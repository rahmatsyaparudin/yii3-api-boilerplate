<?php

declare(strict_types=1);

return [
    // HTTP Prefix (Generic API Errors)
    'http.bad_request' => 'Permintaan tidak valid atau formatnya salah',
    'http.unauthorized' => 'Autentikasi diperlukan atau telah gagal',
    'http.forbidden' => 'Anda tidak memiliki izin untuk mengakses sumber daya ini',
    'http.not_found' => 'Sumber daya yang diminta tidak ditemukan',
    'http.method_not_allowed' => 'Metode HTTP tidak diizinkan untuk endpoint ini',
    'http.unsupported_media_type' => 'Tipe media pada permintaan tidak didukung',
    'http.too_many_requests' => 'Terlalu banyak permintaan. Silakan coba lagi nanti',
    'http.internal_error' => 'Terjadi kesalahan internal pada server',
    'http.service_unavailable' => 'Layanan sedang tidak tersedia untuk sementara waktu',
    'http.missing_request_params' => 'Permintaan tidak valid. Parameter wajib tidak ditemukan.',

    // Security Prefix
    'security.host_not_allowed' => 'Akses ditolak: Host tidak diizinkan',

    // Auth Prefix (Infrastructure Level)
    'auth.header_missing' => 'Header otorisasi tidak ditemukan',
    'auth.invalid_token' => 'Token akses tidak valid atau telah kedaluwarsa',
    'auth.invalid_issuer' => 'Penerbit (issuer) token tidak valid',
    'auth.invalid_audience' => 'Target (audience) token tidak valid',
    'auth.missing_claim' => 'Token tidak memiliki klaim yang diperlukan: {claim}',

    // Request Prefix
    'request.invalid_json' => 'Isi permintaan mengandung JSON yang tidak valid',
    'request.invalid_body' => 'Isi permintaan tidak valid atau formatnya salah',
    'request.missing_parameter' => 'Parameter wajib tidak ditemukan: {param}',
    'request.invalid_parameter' => 'Nilai parameter tidak valid: {param}',
    'request.host_not_allowed' => 'Host tidak diizinkan: {host}',
    'request.origin_not_allowed' => 'Origin tidak diizinkan: {origin}',

    // Filtering
    'filter.invalid_keys' => 'Permintaan mengandung kolom filter yang tidak didukung: {keys}',
    'filter.not_allowed' => 'Penyaringan berdasarkan kolom "{filter}" tidak diizinkan',
    
    // Sorting
    'sort.invalid_field' => 'Kolom pengurutan "{field}" tidak valid atau tidak diizinkan',
    'sort.invalid_direction' => 'Arah pengurutan "{direction}" tidak valid. Gunakan "asc" atau "desc"',
    
    // Pagination
    'pagination.invalid_page' => 'Nomor halaman harus berupa bilangan bulat positif yang valid',
    'pagination.invalid_limit' => 'Nilai ukuran halaman tidak valid',
    'pagination.invalid_page_size' => 'Nilai ukuran halaman tidak valid',
    'pagination.invalid_parameter' => 'Parameter "{parameter}" tidak valid',

    // Route
    'route.field_required' => 'Permintaan tidak valid. {resource} {field} wajib disertakan dalam URL',
    'route.parameter_missing' => 'Parameter {parameter} {resource} wajib disertakan dalam URL',

    // Validation
    'validation.failed' => 'Validasi gagal. Silakan periksa kembali data yang dikirimkan',

    // Data type
    'type.string' => 'Kolom {field} harus berupa teks (string)',
    'type.integer' => 'Kolom {field} harus berupa bilangan bulat (integer)',
    'type.numeric' => 'Kolom {field} harus berupa nilai numerik',
    'type.boolean' => 'Kolom {field} harus berupa nilai boolean (true/false)',
    'type.array' => 'Kolom {field} harus berupa array',
    'type.object' => 'Kolom {field} harus berupa objek',

    // Format
    'format.email' => 'Kolom {field} harus berupa alamat email yang valid',
    'format.url' => 'Kolom {field} harus berupa URL yang valid',
    'format.uuid' => 'Kolom {field} harus berupa UUID yang valid',
    'format.date' => 'Kolom {field} harus berupa tanggal yang valid',
    'format.datetime' => 'Kolom {field} harus berupa tanggal dan waktu yang valid',
    'format.json' => 'Kolom {field} harus berisi format JSON yang valid',

    // Range & Length
    'range.min' => 'Nilai {field} harus lebih besar dari atau sama dengan {min}',
    'range.max' => 'Nilai {field} harus lebih kecil dari atau sama dengan {max}',
    'length.min' => 'Kolom {field} minimal harus berisi {min} karakter',
    'length.max' => 'Kolom {field} tidak boleh lebih dari {max} karakter',

    // File
    'file.invalid' => 'Berkas yang diunggah tidak valid',
    'file.too_large' => 'Berkas yang diunggah melebihi ukuran maksimal yang diizinkan',
    'file.invalid_type' => 'Tipe berkas yang diunggah tidak diizinkan',

    // Resource
    'resource.not_found' => 'Data {resource} dengan {field}: {value} tidak ditemukan',
    'resource.conflict' => 'Terjadi konflik pada data {resource}',
    'resource.already_exists' => '{resource} sudah ada sebelumnya',
    'resource.cannot_update' => 'Tidak dapat memperbarui {resource}. Perubahan status dari "{current_status}" ke "{status}" tidak diizinkan',
    'resource.update_not_allowed_by_status' => 'Perubahan data tidak diizinkan untuk {resource} saat berstatus "{current_status}"',
    'resource.status_already_set' => 'Tidak dapat memperbarui {resource}. Status sudah bernilai "{current_status}"',
    'resource.modification_denied_on_deleted' => 'Tindakan dilarang: Operasi yang diminta tidak dapat dilakukan karena {resource} ditandai sebagai "{status}".',

    // Access & Rate Limit
    'operation.not_allowed' => 'Operasi ini tidak diizinkan',
    'access.denied' => 'Akses ke sumber daya ini ditolak',
    'access.insufficient_permissions' => 'Anda tidak memiliki izin yang cukup untuk melakukan tindakan ini',
    'access.auth_required' => 'Autentikasi diperlukan untuk mengakses sumber daya ini',
    'rate_limit.exceeded' => 'Terlalu banyak permintaan. Silakan coba lagi setelah {seconds} detik',
    'rate_limit.try_again' => 'Silakan coba lagi dalam {seconds} detik',

    'business.violation' => 'Pelanggaran aturan bisnis: {reason}',
    'business.limit_reached' => 'Batas {resource} telah tercapai',
    'business.requirement_unmet' => 'Persyaratan tidak terpenuhi: {reason}',

    'service.error' => 'Gagal memproses permintaan: {reason}',
    'service.unavailable' => 'Layanan "{service}" tidak tersedia',
    'service.failed' => 'Proses layanan gagal dilakukan',

    'factory.detail_info.uninitialized_state' => 'Kesalahan internal: Factory belum diinisialisasi. Anda harus memanggil {methods} sebelum melakukan operasi ini.',

    // Optimistic Locking
    'optimistic.lock.failed' => 'Data {resource} telah dimodifikasi oleh pengguna lain. Silakan refresh dan coba lagi.',
    'lock_version.invalid_negative' => 'Lock version tidak boleh negatif: {value}',
];
