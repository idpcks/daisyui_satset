<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Lapisan pertahanan kedua untuk XSS dan clickjacking.
 *
 * Nonce dibuat sekali per request dan dipakai ulang oleh @vite maupun skrip
 * inline di root template, sehingga tidak perlu `unsafe-inline` untuk script.
 * Semua nilai bisa dimatikan lewat env untuk kasus di mana reverse proxy sudah
 * menangani header yang sama.
 */
class AddSecurityHeaders
{
    public function handle(Request $request, Closure $next): Response
    {
        $nonce = Str::random(32);

        Vite::useCspNonce($nonce);

        $response = $next($request);

        if (! config('security.headers_enabled')) {
            return $response;
        }

        $response->headers->set('X-Content-Type-Options', 'nosniff');
        $response->headers->set('X-Frame-Options', 'DENY');
        $response->headers->set('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->headers->set('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');

        if ($request->secure()) {
            $response->headers->set('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicy($nonce));

        return $response;
    }

    /**
     * Kebijakan dibuat ketat di produksi dan sedikit longgar di lokal agar
     * Vite dev server (HMR, port 5173) tetap berfungsi.
     */
    private function contentSecurityPolicy(string $nonce): string
    {
        $self = "'self'";

        $scriptSources = [$self, "'nonce-{$nonce}'"];

        if (config('app.debug')) {
            $scriptSources[] = 'localhost:*';
            $scriptSources[] = '127.0.0.1:*';
        }

        $connectSources = [$self];

        if (config('app.debug')) {
            $connectSources[] = 'ws://localhost:*';
            $connectSources[] = 'ws://127.0.0.1:*';
        }

        return implode('; ', [
            "default-src {$self}",
            'script-src '.implode(' ', $scriptSources),
            // QR code 2FA disuntik via v-html sebagai SVG; style di sini hanya
            // untuk atribut presentasi SVG, bukan stylesheet dari klien.
            "style-src {$self} 'unsafe-inline'",
            // Avatar pengguna berasal dari URL arbitrary, jadi https: perlu
            // diizinkan. http: sengaja tidak: mixed content diblokir browser.
            "img-src {$self} data: blob: https:",
            "font-src {$self} data:",
            'connect-src '.implode(' ', $connectSources),
            "frame-ancestors 'none'",
            "base-uri {$self}",
            "form-action {$self}",
            "object-src 'none'",
        ]);
    }
}
