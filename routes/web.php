<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureFlagController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Login/logout ditangani Fortify, tetapi tampilannya memakai Inertia.
Route::get('/', fn () => to_route('login'))
    ->middleware('guest')
    ->name('home');

Route::post('locale', [LocaleController::class, 'update'])
    ->name('locale.update');

Route::middleware('auth')->group(function () {
    Route::get('/app', DashboardController::class)->name('dashboard');

    // Daftar pengguna tampil sebagai halaman index, sedangkan tambah/ubah
    // memakai modal sehingga hanya aksi tulis yang butuh route sendiri.
    Route::prefix('users')->name('users.')->group(function () {
        Route::middleware('can:user.manage')->group(function () {
            Route::get('/', [UsersController::class, 'index'])->name('index');

            // Bulk status harus dideklarasikan sebelum resource agar tidak
            // dibaca sebagai parameter {user}.
            Route::patch('bulk-status', [UsersController::class, 'bulkStatus'])
                ->name('bulk-status');

            Route::post('/', [UsersController::class, 'store'])->name('store');
            Route::match(['put', 'patch'], '{user}', [UsersController::class, 'update'])
                ->name('update');
            Route::delete('{user}', [UsersController::class, 'destroy'])->name('destroy');
        });

        // Impersonation hanya untuk superadmin. Route berhenti tetap memakai
        // auth saja karena saat impersonating pengguna aktif adalah target,
        // bukan superadmin; kepemilikan sesi diperiksa di controller.
        //
        // Ability `user.impersonate` hanya menjaga akses route; keputusan
        // berdasarkan akun target ada di UserPolicy::impersonate.
        Route::post('{user}/impersonate', [ImpersonationController::class, 'store'])
            ->middleware('can:user.impersonate')
            ->name('impersonate');
    });

    Route::prefix('impersonation')->name('impersonation.')->group(function () {
        Route::delete('/', [ImpersonationController::class, 'destroy'])
            ->name('destroy');
    });

    // Audit log hanya berisi riwayat impersonation; aksesnya diatur lewat
    // permission audit.view yang bisa diberikan ke peran mana pun di RBAC.
    Route::prefix('audit')->name('audit.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])
            ->middleware('can:audit.view')
            ->name('index');
    });

    // RBAC: peran dan permission yang menempel padanya.
    Route::prefix('rbac')->name('rbac.')->group(function () {
        Route::get('/', [RbacController::class, 'index'])
            ->middleware('can:role.manage')
            ->name('index');

        Route::prefix('roles')->name('roles.')->group(function () {
            Route::post('/', [RbacController::class, 'store'])
                ->middleware('can:role.manage')
                ->name('store');

            Route::patch('{role}', [RbacController::class, 'rename'])
                ->middleware('can:role.manage')
                ->name('rename');

            Route::put('{role}', [RbacController::class, 'update'])
                ->middleware('can:role.manage')
                ->name('update');

            Route::delete('{role}', [RbacController::class, 'destroy'])
                ->middleware('can:role.manage')
                ->name('destroy');
        });
    });

    // Feature flag runtime: mengaktifkan/menonaktifkan fitur tanpa deploy,
    // misalnya passkey dan login tautan email.
    Route::prefix('features')->name('features.')->group(function () {
        Route::get('/', [FeatureFlagController::class, 'index'])
            ->middleware('can:feature.manage')
            ->name('index');

        Route::patch('{feature}', [FeatureFlagController::class, 'update'])
            ->middleware('can:feature.manage')
            ->name('update');
    });

    // Pengaturan akun sendiri: tab Akun, Keamanan, dan Riwayat Login. Setiap
    // pengguna boleh mengubah datanya sendiri dan mengelola 2FA-nya.
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingsController::class, 'index'])
            ->name('index');

        Route::put('account', [SettingsController::class, 'updateAccount'])
            ->name('account.update');

        Route::put('password', [SettingsController::class, 'updatePassword'])
            ->name('password.update');

        // Tab Sistem hanya bisa diubah pengguna dengan permission ini, dan
        // hanya mereka yang melihat tab-nya di halaman Pengaturan.
        Route::put('system', [SettingsController::class, 'updateSystem'])
            ->middleware('can:system.manage')
            ->name('system.update');

        Route::prefix('security')->name('security.')->group(function () {
            Route::post('two-factor', [SecurityController::class, 'enable'])
                ->middleware('throttle:password-confirm')
                ->name('enable');

            Route::post('two-factor/confirm', [SecurityController::class, 'confirm'])
                ->name('confirm');

            Route::delete('two-factor', [SecurityController::class, 'disable'])
                ->middleware('throttle:password-confirm')
                ->name('disable');

            Route::post('recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])
                ->middleware('throttle:password-confirm')
                ->name('recovery-codes');

            Route::delete('other-sessions', [SettingsController::class, 'logoutOtherSessions'])
                ->name('other-sessions');

            Route::delete('sessions/{userSession}', [SettingsController::class, 'logoutSession'])
                ->name('sessions.destroy');
        });
    });
});
