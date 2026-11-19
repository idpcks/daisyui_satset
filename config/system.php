<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Bahasa yang Dapat Dipilih
    |--------------------------------------------------------------------------
    |
    | Hanya locale yang punya file terjemahan di lang/ yang boleh dipilih dari
    | halaman Konfigurasi Sistem. Tambahkan entri baru setelah terjemahannya
    | tersedia.
    |
    */

    'locales' => [
        'id' => 'Indonesia',
        'en' => 'English',
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry-After Mode Maintenance
    |--------------------------------------------------------------------------
    |
    | Nilai header Retry-After (detik) yang dikirim bersama respons 503 saat
    | mode maintenance aktif.
    |
    */

    'maintenance_retry' => 3600,

];
