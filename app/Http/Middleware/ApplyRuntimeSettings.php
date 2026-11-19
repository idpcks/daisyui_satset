<?php

namespace App\Http\Middleware;

use App\Models\Setting;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ApplyRuntimeSettings
{
    /**
     * Terapkan setelan yang dapat diubah admin ke config aplikasi.
     *
     * Hanya berjalan pada request web. Console dan queue tetap memakai nilai
     * dari .env agar migrasi dan worker tidak bergantung pada tabel settings.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $settings = Setting::values();

        $name = $settings['app.name'] ?? null;

        if (is_string($name) && $name !== '') {
            config(['app.name' => $name]);
        }

        $locale = $settings['app.locale'] ?? null;

        if (is_string($locale) && array_key_exists($locale, (array) config('system.locales'))) {
            config(['app.locale' => $locale]);
            app()->setLocale($locale);
        }

        $timezone = $settings['app.timezone'] ?? null;

        if (is_string($timezone) && in_array($timezone, timezone_identifiers_list(), true)) {
            config(['app.timezone' => $timezone]);
            date_default_timezone_set($timezone);
        }

        return $next($request);
    }
}
