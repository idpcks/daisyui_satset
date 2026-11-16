<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FeatureFlagController;
use App\Http\Controllers\ImpersonationController;
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

    // Bulk status harus dideklarasikan sebelum resource agar tidak dibaca
    // sebagai parameter {user}.
    Route::patch('users/bulk-status', [UsersController::class, 'bulkStatus'])
        ->middleware('can:user.manage')
        ->name('users.bulk-status');

    Route::resource('users', UsersController::class)
        ->only(['store', 'update', 'destroy'])
        ->middleware('can:user.manage');

    // Impersonation hanya untuk superadmin. Route berhenti tetap memakai auth
    // saja karena saat impersonating pengguna aktif adalah target, bukan
    // superadmin; kepemilikan sesi diperiksa di controller.
    //
    // Ability `user.impersonate` hanya menjaga akses route; keputusan
    // berdasarkan akun target ada di UserPolicy::impersonate.
    Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])
        ->middleware('can:user.impersonate')
        ->name('users.impersonate');

    Route::delete('impersonation', [ImpersonationController::class, 'destroy'])
        ->name('impersonation.destroy');

    // Audit log hanya berisi riwayat impersonation dan hanya untuk superadmin.
    Route::get('audit', [AuditLogController::class, 'index'])
        ->middleware('can:view-audit-log')
        ->name('audit.index');

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

    // Feature flag runtime: mengaktifkan/menonaktifkan fitur tanpa deploy,
    // misalnya passkey dan login tautan email.
    Route::get('features', [FeatureFlagController::class, 'index'])
        ->middleware('can:feature.manage')
        ->name('features.index');

    Route::patch('features/{feature}', [FeatureFlagController::class, 'update'])
        ->middleware('can:feature.manage')
        ->name('features.update');

    // Pengaturan akun sendiri: tab Akun, Keamanan, dan Riwayat Login. Setiap
    // pengguna boleh mengubah datanya sendiri dan mengelola 2FA-nya.
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

    Route::delete('settings/security/other-sessions', [SettingsController::class, 'logoutOtherSessions'])
        ->name('settings.security.other-sessions');

    Route::delete('settings/security/sessions/{userSession}', [SettingsController::class, 'logoutSession'])
        ->name('settings.security.sessions.destroy');
});
