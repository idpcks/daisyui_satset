<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Vite;
use Tests\TestCase;

class SecurityHeadersTest extends TestCase
{
    use RefreshDatabase;

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/auth/signin')->assertOk();

        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('X-Frame-Options', 'DENY');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy');
    }

    public function test_content_security_policy_blocks_framing_and_plugins(): void
    {
        $csp = $this->get('/auth/signin')->assertOk()->headers->get('Content-Security-Policy');

        $this->assertStringContainsString("frame-ancestors 'none'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("base-uri 'self'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);

        preg_match('/script-src ([^;]+)/', $csp, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertStringNotContainsString('unsafe-inline', $matches[1]);
    }

    /**
     * Ini yang paling mudah salah: nonce di header harus sama dengan nonce
     * pada tag script. Bila beda, browser menolak semua skrip aplikasi.
     */
    public function test_csp_nonce_matches_the_rendered_script_tags(): void
    {
        $response = $this->get('/auth/signin')->assertOk();

        $csp = $response->headers->get('Content-Security-Policy');
        preg_match("/'nonce-([A-Za-z0-9]+)'/", $csp, $matches);

        $this->assertNotEmpty($matches[1], 'CSP tidak memuat nonce.');

        $html = $response->getContent();

        // Tag <script type="application/json"> milik Inertia tidak dieksekusi
        // browser, jadi tidak perlu nonce. Yang wajib punya nonce adalah skrip
        // tema, @vite, dan skrip bootstrap apa pun yang dieksekusi.
        preg_match_all('/<script([^>]*)>/', $html, $tags);

        foreach ($tags[1] as $attributes) {
            if (str_contains($attributes, 'type="application/json"')) {
                continue;
            }

            $this->assertStringContainsString(
                'nonce="'.$matches[1].'"',
                $attributes,
                'Ada skrip yang dieksekusi tanpa nonce CSP: '.$attributes,
            );
        }

        $this->assertStringContainsString('data-page="app"', $html);
    }

    /**
     * Regresi: Vite dev server bisa jalan dengan APP_DEBUG=false. Mendeteksi
     * hot mode lewat APP_DEBUG membuat CSP memblokir CSS dan HMR WebSocket.
     */
    public function test_csp_allows_the_vite_dev_server_when_hot_file_exists(): void
    {
        $previousHotFile = Vite::hotFile();
        $hotFile = tempnam(sys_get_temp_dir(), 'vite-hot-');

        file_put_contents($hotFile, 'http://127.0.0.1:5173');
        Vite::useHotFile($hotFile);

        try {
            $csp = $this->get('/auth/signin')->assertOk()->headers->get('Content-Security-Policy');

            $this->assertStringContainsString('http://127.0.0.1:5173', $csp);
            $this->assertStringContainsString('ws://127.0.0.1:5173', $csp);

            // style-src juga harus memuat origin Vite, bukan hanya script-src.
            preg_match('/style-src ([^;]+)/', $csp, $matches);
            $this->assertStringContainsString('http://127.0.0.1:5173', $matches[1]);

            // Literal IPv6 bukan host-source CSP yang valid; Chrome mengabaikan
            // source semacam ini, sehingga CSS dan WebSocket tetap diblokir.
            $this->assertStringNotContainsString('[::1]', $csp);
        } finally {
            Vite::useHotFile($previousHotFile);
            @unlink($hotFile);
        }
    }

    public function test_hsts_is_sent_only_on_secure_requests(): void
    {
        $insecure = $this->get('/auth/signin')->assertOk();
        $this->assertNull($insecure->headers->get('Strict-Transport-Security'));

        $secure = $this->get('https://example.test/auth/signin')->assertOk();
        $secure->assertHeader('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
    }

    public function test_headers_can_be_disabled_for_a_proxy_that_already_sets_them(): void
    {
        config(['security.headers_enabled' => false]);

        $response = $this->get('/auth/signin')->assertOk();

        $this->assertNull($response->headers->get('Content-Security-Policy'));
        $this->assertNull($response->headers->get('X-Frame-Options'));
    }
}
