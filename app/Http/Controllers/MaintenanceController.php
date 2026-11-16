<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Http\MaintenanceModeBypassCookie;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class MaintenanceController extends Controller
{
    /**
     * Tampilkan status maintenance mode saat ini.
     */
    public function index(): Response
    {
        $maintenance = app()->maintenanceMode();
        $active = $maintenance->active();

        /** @var array<string, mixed> $data */
        $data = $active ? $maintenance->data() : [];

        return Inertia::render('maintenance/Index', [
            'active' => $active,
            'retry' => is_numeric($data['retry'] ?? null) ? (int) $data['retry'] : null,
        ]);
    }

    /**
     * Aktifkan maintenance mode lewat perintah `down` bawaan Laravel.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'retry' => ['nullable', 'integer', 'min:1', 'max:86400'],
        ]);

        if (app()->maintenanceMode()->active()) {
            return back()->with('error', 'Maintenance mode sudah aktif.');
        }

        // Secret ini dipakai untuk cookie bypass agar superadmin yang
        // mengaktifkan tetap bisa membuka panel. Cookie hanya berlaku 12 jam;
        // setelah itu pemulihan harus lewat `php artisan up` di server.
        $secret = Str::random(40);

        $options = [
            '--secret' => $secret,
            '--render' => 'errors.503',
        ];

        if (isset($validated['retry'])) {
            $options['--retry'] = (string) $validated['retry'];
        }

        if (Artisan::call('down', $options) !== 0) {
            return back()->with(
                'error',
                'Gagal mengaktifkan maintenance mode. Pastikan direktori storage/framework dapat ditulis.',
            );
        }

        activity('maintenance')
            ->event('maintenance_enabled')
            ->causedBy($request->user())
            ->withProperties([
                'retry' => $validated['retry'] ?? null,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ])
            ->log('Maintenance mode diaktifkan.');

        return back()
            ->withCookie(MaintenanceModeBypassCookie::create($secret))
            ->with(
                'success',
                'Maintenance mode diaktifkan. Akses Anda tetap dibuka lewat cookie bypass selama 12 jam.',
            );
    }

    /**
     * Nonaktifkan maintenance mode lewat perintah `up`.
     *
     * Aksi ini hanya bisa dijangkau selama cookie bypass masih valid. Bila
     * cookie hilang atau kedaluwarsa, jalankan `php artisan up` dari server.
     */
    public function destroy(Request $request): RedirectResponse
    {
        if (! app()->maintenanceMode()->active()) {
            return back()->with('error', 'Maintenance mode sedang tidak aktif.');
        }

        if (Artisan::call('up') !== 0) {
            return back()->with(
                'error',
                'Gagal menonaktifkan maintenance mode. Periksa log aplikasi lalu coba lagi.',
            );
        }

        activity('maintenance')
            ->event('maintenance_disabled')
            ->causedBy($request->user())
            ->withProperties([
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ])
            ->log('Maintenance mode dinonaktifkan.');

        return redirect()
            ->route('maintenance.index')
            ->withCookie(cookie()->forget('laravel_maintenance'))
            ->with('success', 'Maintenance mode dinonaktifkan. Aplikasi kembali dapat diakses semua pengguna.');
    }
}
