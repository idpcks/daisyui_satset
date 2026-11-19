<?php

namespace App\Providers;

use App\Actions\Fortify\CreateNewUser;
use App\Actions\Fortify\ResetUserPassword;
use App\Actions\Fortify\UpdateUserPassword;
use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\RedirectIfTwoFactorAuthenticatable;
use Laravel\Fortify\Fortify;
use Laravel\Passkeys\Passkeys;

class FortifyServiceProvider extends ServiceProvider
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
        Fortify::createUsersUsing(CreateNewUser::class);
        Fortify::updateUserPasswordsUsing(UpdateUserPassword::class);
        Fortify::resetUserPasswordsUsing(ResetUserPassword::class);
        Fortify::redirectUserForTwoFactorAuthenticationUsing(RedirectIfTwoFactorAuthenticatable::class);

        // Tampilan login (Inertia): resources/js/pages/auth/Login.vue
        Fortify::loginView(fn () => app(AuthController::class)->showLogin());

        // ConfirmablePasswordController@show membutuhkan binding ini. Tanpa itu,
        // GET /user/confirm-password gagal dengan "not instantiable", sehingga
        // alur RequirePassword berujung error saat konfirmasi kedaluwarsa.
        Fortify::confirmPasswordView(fn () => app(AuthController::class)->showConfirmPassword());

        // Tampilan verifikasi 2FA (Inertia): resources/js/pages/auth/TwoFactorChallenge.vue
        Fortify::twoFactorChallengeView(fn () => app(AuthController::class)->showTwoFactorChallenge());

        // Kustomisasi proses autentikasi
        Fortify::authenticateUsing(function (Request $request) {
            // Ambil data user tanpa filter scope active() agar kita bisa membaca status aslinya
            $user = User::where(Fortify::username(), $request->input(Fortify::username()))->first();

            // Jika user ditemukan dan password cocok
            if ($user && Hash::check($request->input('password'), $user->password)) {

                // Cek status aktif (Sesuaikan 'is_active' dengan nama kolom database Anda)
                if (! $user->is_active) {
                    throw ValidationException::withMessages([
                        Fortify::username() => __('auth.account_inactive'),
                    ]);
                }

                // Jika aktif, loloskan
                return $user;
            }

            // Jika email tidak ada atau password salah
            return null;
        });

        // Login passkey tidak melewati authenticateUsing di atas, jadi status
        // akun harus diperiksa di hook ini. Pengguna yang sudah di-soft-delete
        // juga tidak punya relasi user lagi (null) sehingga ikut ditolak,
        // bukan berakhir dengan TypeError saat guard memanggil login(null).
        Passkeys::authorizeLoginUsing(
            fn (Request $request, ?User $user): bool => $user !== null && $user->is_active,
        );

        RateLimiter::for('login', function (Request $request) {
            $throttleKey = Str::transliterate(Str::lower($request->input(Fortify::username())).'|'.$request->ip());

            return Limit::perMinute(5)->by($throttleKey);
        });

        RateLimiter::for('two-factor', function (Request $request) {
            return Limit::perMinute(5)->by($request->session()->get('login.id'));
        });

        // Kunci throttle tidak boleh berasal dari nilai yang dikendalikan klien:
        // `credential.id` bisa diganti, dan ID sesi ikut berganti begitu cookie
        // sesi dihapus, sehingga keduanya bisa dipakai untuk melewati batas.
        // Alamat IP berasal dari koneksi, bukan dari cookie, jadi tidak bisa
        // direset dengan menghapus cookie.
        RateLimiter::for('passkeys', function (Request $request) {
            return Limit::perMinute(10)->by($request->ip());
        });

        // Konfirmasi password melindungi aksi sensitif (2FA, kode pemulihan,
        // passkey). Tanpa batas, sesi yang dibajak bisa dipakai menebak password
        // tanpa henti. Key memakai ID pengguna + IP: keduanya tidak bisa direset
        // klien, sama seperti limiter login dan passkey di atas.
        RateLimiter::for('password-confirm', function (Request $request) {
            return Limit::perMinute(5)->by(
                ($request->user()?->getAuthIdentifier() ?? 'guest').'|'.$request->ip()
            );
        });
    }
}
