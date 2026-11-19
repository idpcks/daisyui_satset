<?php

return [
    'users' => [
        'created' => 'Pengguna baru berhasil ditambahkan.',
        'updated' => 'Data pengguna berhasil diperbarui.',
        'deleted' => 'Pengguna berhasil dihapus.',
        'bulk_updated' => ':count pengguna berhasil :status.',
        'activated' => 'diaktifkan',
        'deactivated' => 'dinonaktifkan',
    ],
    'roles' => [
        'created' => 'Peran :name berhasil ditambahkan.',
        'renamed' => 'Peran :old diubah menjadi :new.',
        'deleted' => 'Peran :name berhasil dihapus.',
        'permissions_updated' => 'Permission peran :name berhasil diperbarui.',
    ],
    'features' => [
        'enabled' => 'Fitur :label berhasil diaktifkan.',
        'disabled' => 'Fitur :label berhasil dinonaktifkan.',
        'activity_enabled' => 'Fitur :label diaktifkan.',
        'activity_disabled' => 'Fitur :label dinonaktifkan.',
        'flags' => [
            'passkeys_login' => [
                'label' => 'Login Passkey',
                'description' => 'Login dan pengelolaan passkey. Saat nonaktif, rute passkey membalas 404 dan panelnya disembunyikan dari halaman Pengaturan.',
            ],
            'magic_link_login' => [
                'label' => 'Login Tautan Email',
                'description' => 'Login lewat tautan yang dikirim ke email. Saat nonaktif, seluruh rute magic link membalas 404.',
            ],
        ],
    ],
    'impersonation' => [
        'already_impersonating' => 'Anda sudah berada dalam mode impersonation.',
        'started' => 'Anda sekarang masuk sebagai :name.',
        'activity_started' => ':actor masuk sebagai :target.',
        'not_impersonating' => 'Anda tidak sedang dalam mode impersonation.',
        'origin_unavailable' => 'Akun asal tidak lagi tersedia. Silakan masuk kembali.',
        'stopped' => 'Anda kembali masuk sebagai :name.',
        'activity_stopped' => ':actor kembali dari akun :target.',
    ],
    'security' => [
        'two_factor_initiated' => 'Autentikasi dua faktor disiapkan. Pindai QR code dan masukkan kode verifikasi.',
        'two_factor_invalid_code' => 'Kode verifikasi tidak valid. Periksa kembali kode dari aplikasi autentikator Anda.',
        'two_factor_enabled' => 'Autentikasi dua faktor berhasil diaktifkan.',
        'two_factor_disabled' => 'Autentikasi dua faktor dinonaktifkan.',
        'recovery_codes_regenerated' => 'Kode pemulihan baru berhasil dibuat.',
        'password_mismatch' => 'Password tidak cocok.',
    ],
    'settings' => [
        'account_updated' => 'Data akun berhasil diperbarui.',
        'password_updated' => 'Password berhasil diubah.',
        'other_sessions_logged_out' => 'Semua sesi lain berhasil dikeluarkan.',
        'current_session_cannot_be_terminated' => 'Sesi yang sedang Anda pakai tidak dapat dikeluarkan dari sini.',
        'session_logged_out' => 'Sesi perangkat berhasil dikeluarkan.',
        'system_updated' => 'Konfigurasi sistem berhasil disimpan.',
        'activity_system_updated' => 'Konfigurasi sistem diperbarui.',
    ],
    'policies' => [
        'user' => [
            'update_superadmin_denied' => 'Akun superadmin tidak dapat diubah.',
            'change_own_role_denied' => 'Anda tidak dapat mengubah peran akun sendiri.',
            'deactivate_self_denied' => 'Anda tidak dapat menonaktifkan akun sendiri.',
            'deactivate_superadmin_denied' => 'Akun superadmin tidak dapat dinonaktifkan.',
            'delete_self_denied' => 'Anda tidak dapat menghapus akun sendiri.',
            'delete_superadmin_denied' => 'Akun superadmin tidak dapat dihapus.',
            'impersonate_superadmin_only' => 'Hanya superadmin yang dapat masuk sebagai pengguna lain.',
            'impersonate_self_denied' => 'Anda tidak dapat masuk sebagai akun sendiri.',
            'impersonate_other_superadmin_denied' => 'Akun superadmin lain tidak dapat di-impersonate.',
            'impersonate_inactive_denied' => 'Akun yang nonaktif tidak dapat di-impersonate.',
        ],
        'role' => [
            'rename_superadmin_denied' => 'Nama peran superadmin tidak dapat diubah.',
            'delete_superadmin_denied' => 'Peran superadmin tidak dapat dihapus.',
            'delete_in_use_denied' => 'Peran :name masih dipakai :count pengguna, jadi belum bisa dihapus.',
            'change_superadmin_permissions_denied' => 'Permission superadmin tidak dapat diubah.',
        ],
    ],
    'system' => [
        'maintenance_default_message' => 'Aplikasi sedang dalam perbaikan. Silakan coba lagi nanti.',
    ],
];
