<?php

namespace App\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class PasswordConfirmationService
{
    /**
     * Pastikan password cocok dan tandai sesi sebagai terkonfirmasi.
     */
    public function confirm(Request $request): void
    {
        if (! Hash::check((string) $request->input('password'), $request->user()->password)) {
            throw ValidationException::withMessages([
                'password' => __('messages.security.password_mismatch'),
            ]);
        }

        $request->session()->put('auth.password_confirmed_at', now()->unix());
    }
}
