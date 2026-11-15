<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class SettingsController extends Controller
{
    /**
     * Tampilkan halaman pengaturan akun dengan tab Akun dan Keamanan.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Secret sudah dibuat tetapi kode belum dikonfirmasi: tab Keamanan
        // menampilkan QR code supaya pengguna bisa memindainya.
        $isPending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        return Inertia::render('settings/Index', [
            'tab' => $request->query('tab') === 'keamanan' ? 'keamanan' : 'akun',
            'account' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPending' => $isPending,
            'qrCodeSvg' => $isPending ? $user->twoFactorQrCodeSvg() : null,
            'secretKey' => $isPending ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret) : null,
            'recoveryCodes' => $user->hasEnabledTwoFactorAuthentication() ? $user->recoveryCodes() : [],
        ]);
    }

    /**
     * Perbarui data akun milik pengguna yang sedang login.
     */
    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update([
            ...$data,
            'updated_by' => $user->username,
        ]);

        return back()->with('success', 'Data akun berhasil diperbarui.');
    }

    /**
     * Ubah password akun milik pengguna yang sedang login.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Kolom password memakai cast `hashed`, jadi nilai polos di-hash otomatis.
        $user->update([
            'password' => $request->validated('password'),
            'updated_by' => $user->username,
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
