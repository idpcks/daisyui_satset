<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\RbacController;
use App\Http\Controllers\SecurityController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

// Login/logout ditangani Fortify, tetapi tampilannya memakai Inertia.
Route::get('/', fn () => to_route('login'))
    ->middleware('guest')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Daftar pengguna tampil sebagai halaman index, sedangkan tambah/ubah
    // memakai modal sehingga hanya aksi tulis yang butuh route sendiri.
    Route::get('users', [UsersController::class, 'index'])
        ->middleware('can:user.manage')
        ->name('users.index');

    Route::resource('users', UsersController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:user.manage');

    // RBAC: peran dan permission yang menempel padanya.
    Route::get('rbac', [RbacController::class, 'index'])
        ->middleware('can:role.manage')
        ->name('rbac.index');

    Route::post('rbac/roles', [RbacController::class, 'store'])
        ->middleware('can:role.manage')
        ->name('rbac.roles.store');

    Route::patch('rbac/roles/{role}', [RbacController::class, 'rename'])
        ->middleware('can:role.manage')
        ->name('rbac.roles.rename');

    Route::put('rbac/roles/{role}', [RbacController::class, 'update'])
        ->middleware('can:role.manage')
        ->name('rbac.roles.update');

    Route::delete('rbac/roles/{role}', [RbacController::class, 'destroy'])
        ->middleware('can:role.manage')
        ->name('rbac.roles.destroy');

    // Pengaturan akun sendiri: tab Akun dan Keamanan. Setiap pengguna boleh
    // mengubah datanya sendiri dan mengelola 2FA-nya.
    Route::get('settings', [SettingsController::class, 'index'])
        ->name('settings.index');

    Route::put('settings/account', [SettingsController::class, 'updateAccount'])
        ->name('settings.account.update');

    Route::put('settings/password', [SettingsController::class, 'updatePassword'])
        ->name('settings.password.update');

    Route::post('settings/security/two-factor', [SecurityController::class, 'enable'])
        ->name('settings.security.enable');

    Route::post('settings/security/two-factor/confirm', [SecurityController::class, 'confirm'])
        ->name('settings.security.confirm');

    Route::delete('settings/security/two-factor', [SecurityController::class, 'disable'])
        ->name('settings.security.disable');

    Route::post('settings/security/recovery-codes', [SecurityController::class, 'regenerateRecoveryCodes'])
        ->name('settings.security.recovery-codes');
});
