<?php

declare(strict_types=1);

/**
 * Validation messages (Indonesian)
 * Pesan validasi lengkap untuk berbagai kasus penggunaan
 */
return [
    // Required
    'required' => '{field} wajib diisi',
    'required_if' => '{field} wajib diisi ketika {other} adalah {value}',
    'required_unless' => '{field} wajib diisi kecuali {other} adalah {value}',
    'required_with' => '{field} wajib diisi ketika {other} ada',
    'required_without' => '{field} wajib diisi ketika {other} tidak ada',
    'filled' => '{field} harus memiliki nilai ketika ada',
    'present' => 'Field {field} harus ada',
    
    // String
    'string' => '{field} harus berupa teks',
    'min_length' => '{field} minimal {min} karakter',
    'max_length' => '{field} maksimal {max} karakter',
    'length' => '{field} harus tepat {length} karakter',
    'length_between' => '{field} harus antara {min} dan {max} karakter',
    'alpha' => '{field} hanya boleh berisi huruf',
    'alpha_num' => '{field} hanya boleh berisi huruf dan angka',
    'alpha_dash' => '{field} hanya boleh berisi huruf, angka, strip dan garis bawah',
    'starts_with' => '{field} harus dimulai dengan: {values}',
    'ends_with' => '{field} harus diakhiri dengan: {values}',
    'lowercase' => '{field} harus huruf kecil',
    'uppercase' => '{field} harus huruf besar',
    
    // Numeric
    'numeric' => '{field} harus berupa angka',
    'integer' => '{field} harus berupa bilangan bulat',
    'decimal' => '{field} harus berupa angka desimal',
    'min_value' => '{field} minimal {min}',
    'max_value' => '{field} maksimal {max}',
    'between' => '{field} harus antara {min} dan {max}',
    'positive' => '{field} harus berupa angka positif',
    'negative' => '{field} harus berupa angka negatif',
    'divisible_by' => '{field} harus dapat dibagi dengan {divisor}',
    
    // Format
    'email' => '{field} harus berupa alamat email yang valid',
    'url' => '{field} harus berupa URL yang valid',
    'ip' => '{field} harus berupa alamat IP yang valid',
    'ipv4' => '{field} harus berupa alamat IPv4 yang valid',
    'ipv6' => '{field} harus berupa alamat IPv6 yang valid',
    'mac_address' => '{field} harus berupa alamat MAC yang valid',
    'uuid' => '{field} harus berupa UUID yang valid',
    'json' => '{field} harus berupa string JSON yang valid',
    'regex' => 'Format {field} tidak valid',
    
    // Date & Time
    'date' => '{field} harus berupa tanggal yang valid',
    'date_format' => '{field} harus sesuai format {format}',
    'before' => '{field} harus tanggal sebelum {date}',
    'after' => '{field} harus tanggal setelah {date}',
    'before_or_equal' => '{field} harus tanggal sebelum atau sama dengan {date}',
    'after_or_equal' => '{field} harus tanggal setelah atau sama dengan {date}',
    'timezone' => '{field} harus berupa zona waktu yang valid',
    
    // File & Upload
    'file' => '{field} harus berupa file',
    'image' => '{field} harus berupa gambar',
    'mimes' => '{field} harus berupa file dengan tipe: {types}',
    'mimetypes' => '{field} harus berupa file dengan tipe: {types}',
    'file_size' => '{field} tidak boleh lebih dari {size} KB',
    'file_min_size' => '{field} minimal {size} KB',
    'dimensions' => '{field} memiliki dimensi gambar yang tidak valid',
    'min_width' => '{field} minimal {width} piksel lebarnya',
    'max_width' => '{field} maksimal {width} piksel lebarnya',
    'min_height' => '{field} minimal {height} piksel tingginya',
    'max_height' => '{field} maksimal {height} piksel tingginya',
    
    // Comparison
    'same' => '{field} harus sama dengan {other}',
    'different' => '{field} harus berbeda dari {other}',
    'confirmed' => 'Konfirmasi {field} tidak cocok',
    'in' => '{field} yang dipilih tidak valid',
    'not_in' => '{field} yang dipilih tidak valid',
    
    // Boolean
    'boolean' => '{field} harus bernilai benar atau salah',
    'accepted' => '{field} harus diterima',
    'declined' => '{field} harus ditolak',
    
    // Array
    'array' => '{field} harus berupa array',
    'array_min' => '{field} minimal harus memiliki {min} item',
    'array_max' => '{field} tidak boleh lebih dari {max} item',
    'array_between' => '{field} harus memiliki antara {min} dan {max} item',
    'distinct' => '{field} memiliki nilai duplikat',
    
    // Existence
    'exists' => '{field} yang dipilih tidak valid',
    'unique' => '{field} sudah digunakan',
    
    // Special
    'nullable' => '{field} boleh kosong',
    'prohibited' => 'Field {field} dilarang',
    'prohibited_if' => '{field} dilarang ketika {other} adalah {value}',
    'prohibited_unless' => '{field} dilarang kecuali {other} adalah {value}',

    'cannot_delete_active' => 'Tidak dapat menghapus {resource} yang sedang aktif',
    'already_exists' => '{resource} dengan nilai "{value}" sudah ada',

    // Field Status for Data 
    'status.forbid_update' => 'Data {resource} dengan status "{status}" tidak dapat dilakukan pembaruan.',
    'status.cannot_update' => 'Tidak dapat mengubah status {resource} dari "{current_status}" menjadi "{status}".',
    'status.invalid_on_creation' => '{resource} harus dalam status "active" atau "draft" saat proses pembuatan.'
];