<?php

declare(strict_types=1);

/**
 * Application messages (Indonesian)
 */
return [
    // General Success
    'success' => 'Sukses',
    'ok' => 'OK',
    'completed' => '{resource} berhasil selesai',
    'processed' => '{resource} berhasil diproses',
    
    // Global CRUD Operations
    'resource.created' => '{resource} berhasil dibuat.',
    'resource.updated' => '{resource} berhasil diperbarui.',
    'resource.deleted' => '{resource} berhasil dihapus.',
    'resource.restored' => '{resource} berhasil dipulihkan.',
    'resource.list_retrieved' => 'Daftar {resource} berhasil dimuat.',
    'resource.details_retrieved' => 'Detail {resource} berhasil dimuat.',
    'resource.no_changes_detected' => 'Tidak ada perubahan data pada {resource}. Data yang dikirimkan sama dengan data saat ini.',
    
    // Specific Operations (non-CRUD)
    'user.activated' => 'Pengguna {name} berhasil diaktifkan',
    'user.deactivated' => 'Pengguna {name} berhasil dinonaktifkan',
    'user.password_changed' => 'Kata sandi berhasil diubah',
    'user.profile_updated' => 'Profil berhasil diperbarui',
    
    // Authentication
    'auth.login_success' => 'Login berhasil',
    'auth.logout_success' => 'Logout berhasil',
    'auth.password_reset_sent' => 'Tautan atur ulang kata sandi telah dikirim ke email Anda',
    'auth.password_reset_success' => 'Kata sandi berhasil diatur ulang',
    'auth.email_verified' => 'Alamat email berhasil diverifikasi',
    'auth.account_created' => 'Akun Anda berhasil dibuat',
    
    // Data Operations
    'data.imported' => 'Data berhasil diimpor',
    'data.exported' => 'Data berhasil diekspor',
    'data.synced' => 'Data berhasil disinkronkan',
    'data.backed_up' => 'Data berhasil dicadangkan',
    'data.restored' => 'Data berhasil dipulihkan',
    'data.cleared' => 'Data berhasil dihapus bersih',
    
    // File Operations
    'file.uploaded' => 'Berkas berhasil diunggah',
    'file.downloaded' => 'Berkas berhasil diunduh',
    'file.deleted' => 'Berkas berhasil dihapus',
    'file.moved' => 'Berkas berhasil dipindahkan',
    'file.copied' => 'Berkas berhasil disalin',
    
    // Settings & Configuration
    'settings.updated' => 'Pengaturan berhasil diperbarui',
    'settings.saved' => 'Pengaturan berhasil disimpan',
    'settings.reset' => 'Pengaturan berhasil dikembalikan ke standar',
    'configuration.updated' => 'Konfigurasi berhasil diperbarui',
    
    // System Operations
    'system.cache_cleared' => 'Cache berhasil dibersihkan',
    'system.maintenance_enabled' => 'Mode pemeliharaan telah diaktifkan',
    'system.maintenance_disabled' => 'Mode pemeliharaan telah dinonaktifkan',
    'system.restarted' => 'Sistem berhasil dijalankan ulang',
    
    // Notifications
    'notification.sent' => 'Notifikasi berhasil dikirim',
    'notification.marked_read' => 'Notifikasi ditandai sebagai sudah dibaca',
    'notification.marked_unread' => 'Notifikasi ditandai sebagai belum dibaca',
    'notification.deleted' => 'Notifikasi berhasil dihapus',
    
    // Reports & Analytics
    'report.generated' => 'Laporan berhasil dibuat',
    'report.downloaded' => 'Laporan berhasil diunduh',
    'analytics.data_refreshed' => 'Data analitik berhasil diperbarui',
    
    // Bulk Operations
    'bulk.created' => '{resource} berhasil dibuat',
    'bulk.updated' => '{resource} berhasil diperbarui',
    'bulk.deleted' => '{resource} berhasil dihapus',
    'bulk.imported' => '{resource} berhasil diimpor',
    'bulk.exported' => '{resource} berhasil diekspor',
    
    // API Operations
    'api.request_processed' => 'Permintaan API berhasil diproses',
    'api.rate_limit_reset' => 'Pembatasan akses (rate limit) API berhasil diatur ulang',
    'api.key_generated' => 'Kunci API berhasil dibuat',
    'api.key_revoked' => 'Kunci API berhasil dicabut',
];
