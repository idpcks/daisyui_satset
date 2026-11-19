<?php

return [

    /*
     * Kirim header keamanan (CSP, X-Frame-Options, HSTS, dan lain-lain).
     * Berguna untuk dimatikan bila reverse proxy sudah menangani semuanya.
     */
    'headers_enabled' => env('SECURITY_HEADERS_ENABLED', true),

];
