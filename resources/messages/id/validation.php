<?php

declare(strict_types=1);

/**
 * Validation messages (Indonesian)
 * Pesan validasi lengkap untuk berbagai kasus penggunaan
 */
return [
    // Required
    'required' => 'Kolom {resource} wajib diisi',
    'required.if' => 'Kolom {resource} wajib diisi jika {other} adalah {value}',
    'required.unless' => 'Kolom {resource} wajib diisi kecuali {other} adalah {value}',
    'required.with' => 'Kolom {resource} wajib diisi jika terdapat {other}',
    'required.without' => 'Kolom {resource} wajib diisi jika tidak terdapat {other}',
    'filled' => 'Kolom {resource} tidak boleh kosong',
    'present' => 'Kolom {resource} harus tersedia',
    
    // String Prefix
    'string.invalid' => 'Kolom {field} harus berupa teks',
    'string.min' => 'Kolom {field} minimal harus {min} karakter',
    'string.max' => 'Kolom {field} maksimal {max} karakter',
    'string.length' => 'Kolom {field} harus tepat {length} karakter',
    'string.between' => 'Kolom {field} harus antara {min} dan {max} karakter',
    'string.alpha' => 'Kolom {field} hanya boleh berisi huruf',
    'string.alpha_num' => 'Kolom {field} hanya boleh berisi huruf dan angka',
    'string.alpha_dash' => 'Kolom {field} hanya boleh berisi huruf, angka, strip, dan garis bawah',
    'string.starts_with' => 'Kolom {field} harus dimulai dengan: {values}',
    'string.ends_with' => 'Kolom {field} harus diakhiri dengan: {values}',
    'string.lowercase' => 'Kolom {field} harus menggunakan huruf kecil',
    'string.uppercase' => 'Kolom {field} harus menggunakan huruf besar',
    
    // Number Prefix
    'number.invalid' => 'Kolom {field} harus berupa angka',
    'number.integer' => 'Kolom {field} harus berupa bilangan bulat',
    'number.decimal' => 'Kolom {field} harus berupa angka desimal',
    'number.min' => 'Nilai kolom {field} minimal harus {min}',
    'number.max' => 'Nilai kolom {field} maksimal {max}',
    'number.between' => 'Nilai kolom {field} harus di antara {min} dan {max}',
    'number.positive' => 'Kolom {field} harus berupa angka positif',
    'number.negative' => 'Kolom {field} harus berupa angka negatif',
    'number.divisible_by' => 'Kolom {field} harus habis dibagi dengan {divisor}',
    
    // Format
    'format.email' => 'Kolom {field} harus berupa alamat email yang valid',
    'format.url' => 'Kolom {field} harus berupa URL yang valid',
    'format.ip' => 'Kolom {field} harus berupa alamat IP yang valid',
    'format.ipv4' => 'Kolom {field} harus berupa alamat IPv4 yang valid',
    'format.ipv6' => 'Kolom {field} harus berupa alamat IPv6 yang valid',
    'format.mac_address' => 'Kolom {field} harus berupa alamat MAC yang valid',
    'format.uuid' => 'Kolom {field} harus berupa UUID yang valid',
    'format.json' => 'Kolom {field} harus berupa string JSON yang valid',
    'format.regex' => 'Format kolom {field} tidak valid',
    
    // Date & Time
    'date.invalid' => 'Kolom {field} harus berupa tanggal yang valid',
    'date.format' => 'Kolom {field} harus sesuai dengan format {format}',
    'date.before' => 'Kolom {field} harus berupa tanggal sebelum {date}',
    'date.after' => 'Kolom {field} harus berupa tanggal setelah {date}',
    'date.before_or_equal' => 'Kolom {field} harus berupa tanggal sebelum atau sama dengan {date}',
    'date.after_or_equal' => 'Kolom {field} harus berupa tanggal setelah atau sama dengan {date}',
    'date.timezone' => 'Kolom {field} harus berupa zona waktu yang valid',
    
    // File Prefix
    'file.invalid' => 'Kolom {resource} harus berupa berkas yang valid',
    'file.mimes' => 'Kolom {resource} harus berupa berkas dengan tipe: {types}',
    'file.mimetypes' => 'Kolom {resource} harus berupa berkas dengan tipe: {types}',
    'file.max_size' => 'Ukuran {resource} tidak boleh lebih dari {size} KB',
    'file.min_size' => 'Ukuran {resource} minimal harus {size} KB',
    
    // Image Prefix
    'image.invalid' => 'Kolom {resource} harus berupa gambar',
    'image.dimensions' => 'Dimensi gambar {resource} tidak valid',
    'image.min_width' => 'Lebar {resource} minimal harus {width} piksel',
    'image.max_width' => 'Lebar {resource} tidak boleh lebih dari {width} piksel',
    'image.min_height' => 'Tinggi {resource} minimal harus {height} piksel',
    'image.max_height' => 'Tinggi {resource} tidak boleh lebih dari {height} piksel',
    
    // Comparison Prefix
    'compare.same' => 'Kolom {resource} harus sama dengan {other}',
    'compare.different' => 'Kolom {resource} harus berbeda dengan {other}',
    'compare.confirmed' => 'Konfirmasi {resource} tidak cocok',
    'compare.in' => '{resource} yang dipilih tidak valid',
    'compare.not_in' => '{resource} yang dipilih tidak valid',
    
    // Boolean Prefix
    'boolean.invalid' => 'Kolom {resource} harus bernilai true atau false',
    'boolean.accepted' => 'Kolom {resource} harus disetujui',
    'boolean.declined' => 'Kolom {resource} harus ditolak',
    
    // Array Prefix
    'array.invalid' => 'Kolom {resource} harus berupa array',
    'array.min' => 'Kolom {resource} minimal harus memiliki {min} item',
    'array.max' => 'Kolom {resource} tidak boleh memiliki lebih dari {max} item',
    'array.between' => 'Kolom {resource} harus memiliki antara {min} dan {max} item',
    'array.distinct' => 'Kolom {resource} memiliki nilai duplikat',
    
    // Existence Prefix
    'exists.invalid' => '{resource} yang dipilih tidak ditemukan',
    'exists.unique' => '{resource} sudah digunakan',
    'exists.already_exists' => '{resource} dengan nilai "{value}" sudah ada sebelumnya',
    'exists.cannot_delete_active' => 'Tidak dapat menghapus {resource} yang masih aktif',

    // Special Prefix
    'special.nullable' => 'Kolom {resource} boleh kosong (null)',
    'special.prohibited' => 'Kolom {resource} dilarang untuk diisi',
    'special.prohibited_if' => 'Kolom {resource} dilarang diisi jika {other} adalah {value}',
    'special.prohibited_unless' => 'Kolom {resource} dilarang diisi kecuali {other} adalah {value}',

    // Status Prefix
    'status.forbid_update' => 'Data {resource} dengan status "{status}" tidak dapat diperbarui',
    'status.invalid_transition' => 'Tidak dapat memperbarui {resource} dari status "{from}" ke "{to}"',
    'status.invalid_on_creation' => '{resource} harus berstatus "active" atau "draft" untuk melanjutkan pembuatan',
    'status.cannot_delete' => 'Tidak dapat menghapus {resource} dengan status "{status}"',
    'status.deletion_restricted' => '{resource} dengan ID {id} tidak dapat dihapus saat berada dalam status "{status}".',

    // Restore Prefix
    'resource.restored' => '{resource} berhasil dipulihkan',
    'resource.restore_failed' => 'Gagal memulihkan {resource}: {error}',
    'resource.not_deleted' => '{resource} tidak dalam status terhapus',

    // Request Prefix
    'request.unknown_parameters' => 'Parameter tidak dikenal: {unknown_keys}. Parameter yang diizinkan: {allowed_keys}',

    // Input Sanitizer Prefix
    'input_sanitizer.input_structure_too_deep' => 'Struktur input terlalu dalam. Kedalaman saat ini {depth}, namun batas maksimum adalah {max_depth}.',
    'input_sanitizer.input_too_long' => 'Input terlalu panjang. Panjang saat ini {length}, namun batas maksimum adalah {max_length}.',
    'input_sanitizer.invalid_encoding' => 'Input berisi enkripsi yang tidak valid. Enkripsi saat ini {encoding}.',
    'input_sanitizer.array_too_large' => 'Array melebihi ukuran maksimum yang diizinkan {max_size} elemen.',
    'input_sanitizer.float_overflow' => 'Nilai di luar rentang. It must be between {min_value} and {max_value}.',
    'input_sanitizer.integer_overflow' => 'Nilai di luar rentang. It must be between {min_value} and {max_value}.',
    'input_sanitizer.invalid_array_key_type' => 'Kunci array memiliki tipe yang tidak valid. Kunci harus berupa string atau integer.',
    'input_sanitizer.invalid_array_key_length' => 'Kunci array terlalu panjang. Panjang maksimum yang diizinkan untuk kunci adalah {max_length} karakter,',
    'input_sanitizer.invalid_array_key' => 'Kunci array {key} berisi karakter atau format yang tidak valid.',
];