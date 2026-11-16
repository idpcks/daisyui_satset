<?php

namespace App\Providers;

use App\Models\Passkey;
use App\Models\Role;
use App\Models\User;
use App\Policies\RolePolicy;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;
use Laravel\Pennant\Feature;
use Laravel\Pennant\Middleware\EnsureFeaturesAreActive;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Feature flag di aplikasi ini adalah saklar global, bukan per akun:
        // flag yang dimatikan admin harus berlaku juga untuk tamu dan pengguna
        // lain. Tanpa ini Pennant memakai pengguna yang sedang login sebagai
        // scope, sehingga perubahan hanya tersimpan untuk akun tersebut.
        Feature::resolveScopeUsing(fn () => null);

        // Fitur yang dinonaktifkan lewat feature flag harus tampak seperti rute
        // yang tidak ada, bukan 400, agar keberadaannya tidak bocor lewat respons.
        EnsureFeaturesAreActive::whenInactive(fn () => abort(404));

        $this->forcePublicUrl();
        $this->forceSecureSessionCookie();

        // Tabel passkeys memakai UUID, jadi model bawaan paket perlu diganti.
        Passkeys::usePasskeyModel(Passkey::class);

        // Superadmin selalu lolos pengecekan permission. Role lain tetap
        // mengikuti permission yang diberikan di RolePermissionSeeder.
        //
        // Ability kontekstual dikunci per model target, bukan hanya per nama
        // ability, agar aturannya tetap berlaku untuk superadmin tanpa
        // mencabut bypass superadmin pada policy model lain yang memakai nama
        // ability sama seperti update atau delete.
        $contextualAbilities = [
            User::class => UserPolicy::CONTEXTUAL_ABILITIES,
            Role::class => RolePolicy::CONTEXTUAL_ABILITIES,
        ];

        Gate::before(function (User $user, string $ability, array $arguments) use ($contextualAbilities): ?bool {
            if (! $user->hasRole('superadmin')) {
                return null;
            }

            $target = $arguments[0] ?? null;

            foreach ($contextualAbilities as $model => $abilities) {
                if ($target instanceof $model && in_array($ability, $abilities, true)) {
                    return null;
                }
            }

            return true;
        });

        // Ability eksplisit agar pengguna non-superadmin tidak pernah lolos
        // hanya karena memiliki permission pengelolaan pengguna. Namanya
        // memakai namespace permission (user.*) dan sengaja berbeda dari
        // UserPolicy::impersonate: Gate::before milik Spatie memetakan nama
        // ability ke nama permission dan berjalan lebih dulu, sehingga nama
        // yang bertabrakan bisa meloloskan pengguna tanpa mengevaluasi policy.
        Gate::define('user.impersonate', fn (User $user): bool => $user->hasRole('superadmin'));
    }

    /**
     * Samakan URL yang dihasilkan aplikasi dengan APP_URL.
     *
     * Dua hal yang tidak ditangani Laravel secara otomatis: base path saat
     * aplikasi dipasang di sub-path dan skema https di balik reverse proxy.
     * Tanpa ini, `route()` dan `asset()` menghasilkan URL tanpa prefix atau
     * ber-skema http sehingga aset diblokir browser.
     */
    private function forcePublicUrl(): void
    {
        $appUrl = (string) config('app.url');

        if ((string) config('app.base_path') !== '') {
            URL::forceRootUrl($appUrl);
        }

        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }

    /**
     * Paksa cookie sesi hanya dikirim lewat HTTPS saat APP_URL memakai https.
     *
     * `SESSION_SECURE_COOKIE` tidak punya default, jadi deployment HTTPS yang
     * lupa mengisinya tetap mengirim cookie sesi lewat HTTP. Menurunkannya dari
     * APP_URL membuat perilaku aman menjadi default: saat APP_URL memakai
     * https, cookie Secure selalu aktif.
     */
    private function forceSecureSessionCookie(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            config(['session.secure' => true]);
        }
    }
}
