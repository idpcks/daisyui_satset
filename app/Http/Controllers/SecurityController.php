<?php

namespace App\Http\Controllers;

use App\Services\PasswordConfirmationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Actions\ConfirmTwoFactorAuthentication;
use Laravel\Fortify\Actions\DisableTwoFactorAuthentication;
use Laravel\Fortify\Actions\EnableTwoFactorAuthentication;
use Laravel\Fortify\Actions\GenerateNewRecoveryCodes;

class SecurityController extends Controller
{
    public function __construct(
        private readonly PasswordConfirmationService $passwordConfirmation,
    ) {}

    public function enable(Request $request, EnableTwoFactorAuthentication $enable): RedirectResponse
    {
        $this->passwordConfirmation->confirm($request);

        $enable($request->user());

        return back()->with('success', 'Autentikasi dua faktor disiapkan. Pindai QR code dan masukkan kode verifikasi.');
    }

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

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $this->passwordConfirmation->confirm($request);

        $disable($request->user());

        return back()->with('success', 'Autentikasi dua faktor dinonaktifkan.');
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $this->passwordConfirmation->confirm($request);

        $generate($request->user());

        return back()->with('success', 'Kode pemulihan baru berhasil dibuat.');
    }
}
