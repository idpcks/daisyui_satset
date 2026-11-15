<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class SecurityController extends Controller
{
    /**
     * Buat secret baru dan aktifkan autentikasi dua faktor.
     */
    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $this->ensurePasswordIsConfirmed($request);

        $enable($request->user());

        return back()->with('success', 'Autentikasi dua faktor disiapkan. Pindai QR code dan masukkan kode verifikasi.');
    }

    /**
     * Konfirmasi kode dari aplikasi autentikator sehingga 2FA aktif.
     */
    public function confirm(Request $request, ConfirmTwoFactorAuthentication $confirm): RedirectResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string'],
        ], [], ['code' => 'kode verifikasi']);

        try {
            $confirm($request->user(), $validated['code']);
        } catch (ValidationException) {
            throw ValidationException::withMessages([
                'code' => 'Kode verifikasi tidak valid. Periksa kembali kode dari aplikasi autentikator Anda.',
            ]);
        }

        return back()->with('success', 'Autentikasi dua faktor berhasil diaktifkan.');
    }

    /**
     * Nonaktifkan autentikasi dua faktor beserta kode pemulihannya.
     */
    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $this->ensurePasswordIsConfirmed($request);

        $disable($request->user());

        return back()->with('success', 'Autentikasi dua faktor dinonaktifkan.');
    }

    /**
     * Buat ulang kode pemulihan untuk autentikasi dua faktor.
     */
    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $generate($request->user());

        return back()->with('success', 'Kode pemulihan baru berhasil dibuat.');
    }

    /**
     * Pastikan password bisa dipakai untuk mengonfirmasi aksi sensitif.
     */
    private function ensurePasswordIsConfirmed(Request $request): void
    {
        if (! Hash::check((string) $request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => 'Password tidak cocok.',
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', now()->unix());
    }
}
