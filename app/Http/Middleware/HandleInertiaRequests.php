<?php

namespace App\Http\Middleware;

use App\Features\MagicLinkLogin;
use App\Features\Passkeys;
use App\Http\Controllers\ImpersonationController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Middleware;
use Laravel\Pennant\Feature;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            'locale' => fn () => app()->getLocale(),
            'availableLocales' => (array) config('system.locales'),
            // Terjemahan jarang berubah, jadi cache berdasarkan locale dan
            // mtime file agar edit bahasa langsung terbaca tanpa restart cache.
            'translations' => function (): array {
                $locale = app()->getLocale();
                $file = lang_path($locale.'.json');
                $version = is_file($file) ? (string) filemtime($file) : '0';

                return Cache::remember(
                    "translations:{$locale}:{$version}",
                    3600,
                    fn (): array => (array) json_decode((string) file_get_contents($file), true),
                );
            },
            // Prefix sub-path (mis. "/admin"); kosong bila di root domain.
            'basePath' => config('app.base_path', ''),
            // Status fitur yang perlu diketahui UI. Dimuat lazily agar query
            // feature flag hanya berjalan saat prop ini benar-benar dikirim.
            // Config Fortify tetap menjadi saklar utama: mematikan fitur di sana
            // juga menghapus rutenya.
            'features' => [
                'passkeys' => fn (): bool => Feature::active(Passkeys::class),
                'magic_link' => fn (): bool => (bool) config('email-magic-link.enabled')
                    && Feature::active(MagicLinkLogin::class),
            ],
            'auth' => [
                'user' => $user?->only('id', 'name', 'username', 'email', 'avatar'),
                // Cache per pengguna dan versi permission global; versi dinaikkan
                // saat peran/permission berubah agar tidak stale.
                'roles' => fn () => $user
                    ? Cache::remember(
                        "auth:{$user->id}:roles:v".Cache::get('permission_cache_version', 1),
                        300,
                        fn () => $user->getRoleNames()->values()->all(),
                    )
                    : [],
                'permissions' => fn () => $user
                    ? Cache::remember(
                        "auth:{$user->id}:permissions:v".Cache::get('permission_cache_version', 1),
                        300,
                        fn () => $user->getAllPermissions()->pluck('name')->values()->all(),
                    )
                    : [],
                'is_superadmin' => fn () => $user
                    ? Cache::remember(
                        "auth:{$user->id}:is_superadmin:v".Cache::get('permission_cache_version', 1),
                        300,
                        fn () => $user->hasRole('superadmin'),
                    )
                    : false,
                'impersonation' => fn () => $this->impersonation($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
                'generated_password' => fn () => $request->session()->get('generated_password'),
                'generated_for' => fn () => $request->session()->get('generated_for'),
                'magic_link_sent' => fn () => $request->session()->get('magic_link_sent'),
            ],
        ];
    }

    /**
     * Data sesi impersonation yang dibutuhkan banner dan navigasi global.
     *
     * @return array{active: bool, started_at: string|null, impersonator: array<string, mixed>|null}|null
     */
    private function impersonation(Request $request): ?array
    {
        $impersonatorId = $request->session()->get(ImpersonationController::SESSION_IMPERSONATOR_ID);

        if (! $impersonatorId) {
            return null;
        }

        $impersonator = User::query()->find($impersonatorId);

        return [
            'active' => true,
            'started_at' => $request->session()->get(ImpersonationController::SESSION_STARTED_AT),
            'impersonator' => $impersonator?->only(['id', 'name', 'username']),
        ];
    }

    /**
     * Sertakan prefix sub-path pada URL halaman yang dikirim ke Inertia.
     *
     * Reverse proxy membuang prefix sub-path sebelum request sampai ke
     * Laravel, sehingga tanpa ini Inertia menimpa address bar browser menjadi
     * "/auth/signin" (tanpa prefix). Bila base_path kosong (aplikasi di root
     * domain), perilakunya sama seperti bawaan.
     */
    public function urlResolver()
    {
        return function (Request $request) {
            return rtrim((string) config('app.base_path', ''), '/').$request->getRequestUri();
        };
    }
}
