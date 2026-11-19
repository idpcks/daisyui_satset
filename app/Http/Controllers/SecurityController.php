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

        return back()->with('success', __('messages.security.two_factor_initiated'));
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
                'code' => __('messages.security.two_factor_invalid_code'),
            ]);
        }

        return back()->with('success', __('messages.security.two_factor_enabled'));
    }

    public function disable(Request $request, DisableTwoFactorAuthentication $disable): RedirectResponse
    {
        $this->passwordConfirmation->confirm($request);

        $disable($request->user());

        return back()->with('success', __('messages.security.two_factor_disabled'));
    }

    public function regenerateRecoveryCodes(Request $request, GenerateNewRecoveryCodes $generate): RedirectResponse
    {
        $this->passwordConfirmation->confirm($request);

        $generate($request->user());

        return back()->with('success', __('messages.security.recovery_codes_regenerated'));
    }
}
