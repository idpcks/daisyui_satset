<?php

/*
|--------------------------------------------------------------------------
| Cross-Origin Resource Sharing (CORS) Configuration
|--------------------------------------------------------------------------
|
| Origin yang boleh memanggil endpoint ini dari browser diatur lewat
| CORS_ALLOWED_ORIGINS (dipisah koma). Mengosongkannya berarti tidak ada
| origin lintas domain yang diizinkan. Isi hanya bila ada klien browser di
| domain lain yang perlu membaca API ini.
|
*/

return [

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['GET', 'POST', 'OPTIONS'],

    'allowed_origins' => array_values(array_filter(explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['Accept', 'Authorization', 'Content-Type', 'X-Requested-With'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
