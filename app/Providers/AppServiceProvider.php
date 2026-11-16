<?php

namespace App\Providers;

use App\Models\Passkey;
use App\Models\User;
use App\Policies\UserPolicy;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Laravel\Passkeys\Passkeys;

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
        $this->forcePublicUrl();

        // Tabel passkeys memakai UUID, jadi model bawaan paket perlu diganti.
        Passkeys::usePasskeyModel(Passkey::class);

        // Superadmin selalu lolos pengecekan permission. Role lain tetap
        // mengikuti permission yang diberikan di RolePermissionSeeder.
        //
        // Ability kontekstual di UserPolicy dikecualikan agar aturannya juga
        // berlaku untuk superadmin, misalnya larangan menghapus akun sendiri.
        Gate::before(function (User $user, string $ability): ?bool {
            if (! $user->hasRole('superadmin')) {
                return null;
            }

            return in_array($ability, UserPolicy::CONTEXTUAL_ABILITIES, true) ? null : true;
        });

        // Ability eksplisit agar pengguna non-superadmin tidak pernah lolos
        // hanya karena memiliki permission pengelolaan pengguna.
        Gate::define('impersonate', fn (User $user): bool => $user->hasRole('superadmin'));
        Gate::define('view-audit-log', fn (User $user): bool => $user->hasRole('superadmin'));
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
}
