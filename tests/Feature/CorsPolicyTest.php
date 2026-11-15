<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CorsPolicyTest extends TestCase
{
    /**
     * Konfigurasi bawaan tidak boleh mengizinkan semua origin, karena itu
     * membuat situs lain bisa membaca API ini dari browser.
     */
    public function test_shipped_configuration_does_not_allow_wildcard_origin(): void
    {
        $allowedOrigins = config('cors.allowed_origins');

        $this->assertIsArray($allowedOrigins);
        $this->assertNotContains('*', $allowedOrigins);
    }

    /**
     * Origin di luar daftar tidak menerima header CORS, sehingga browser
     * menolak membaca responsnya.
     */
    public function test_cross_origin_request_from_unlisted_origin_gets_no_cors_header(): void
    {
        Route::get('/api/cors-probe', fn (): array => ['ok' => true]);

        $response = $this->withHeaders(['Origin' => 'https://situs-lain.test'])
            ->getJson('/api/cors-probe');

        $response->assertOk();
        $response->assertHeaderMissing('Access-Control-Allow-Origin');
    }

    /**
     * Origin yang didaftarkan lewat CORS_ALLOWED_ORIGINS tetap dilayani.
     */
    public function test_cross_origin_request_from_listed_origin_gets_cors_header(): void
    {
        config(['cors.allowed_origins' => ['https://app.example.test']]);

        Route::get('/api/cors-probe', fn (): array => ['ok' => true]);

        $response = $this->withHeaders(['Origin' => 'https://app.example.test'])
            ->getJson('/api/cors-probe');

        $response->assertOk();
        $response->assertHeader('Access-Control-Allow-Origin', 'https://app.example.test');
    }
}
