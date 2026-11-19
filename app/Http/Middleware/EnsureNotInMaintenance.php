<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureNotInMaintenance
{
    /**
     * Route yang tetap dapat diakses saat maintenance agar superadmin bisa
     * masuk dan mematikan mode ini.
     *
     * @var list<string>
     */
    private const ALLOWED_ROUTES = [
        'login',
        'login.store',
        'logout',
        'two-factor.login',
        'two-factor.login.store',
    ];

    /**
     * Balas 503 untuk semua pengguna kecuali superadmin saat mode maintenance
     * aktif.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::values();

        if (! (bool) ($settings['maintenance.enabled'] ?? false)) {
            return $next($request);
        }

        if ($request->user()?->hasRole('superadmin')) {
            return $next($request);
        }

        if ($request->routeIs(...self::ALLOWED_ROUTES)) {
            return $next($request);
        }

        $message = $settings['maintenance.message'] ?? null;

        return response()
            ->view('errors.503', [
                'message' => is_string($message) && $message !== ''
                    ? $message
                    : 'Aplikasi sedang dalam perbaikan. Silakan coba lagi nanti.',
            ], 503)
            ->header('Retry-After', (string) config('system.maintenance_retry', 3600));
    }
}
