<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;

class AuthController extends Controller
{
    /**
     * Tampilan login (resources/js/pages/auth/Login.vue).
     * Proses login/logout tetap ditangani Fortify.
     */
    public function showLogin(): Response
    {
        return Inertia::render('auth/Login');
    }

    /**
     * Tampilan konfirmasi password (resources/js/pages/auth/ConfirmPassword.vue).
     * Dipakai RequirePassword saat konfirmasi kedaluwarsa; proses verifikasi
     * tetap ditangani Fortify.
     */
    public function showConfirmPassword(): Response
    {
        return Inertia::render('auth/ConfirmPassword');
    }

    /**
     * Tampilan verifikasi dua faktor (resources/js/pages/auth/TwoFactorChallenge.vue).
     * Proses verifikasi tetap ditangani Fortify.
     */
    public function showTwoFactorChallenge(): Response
    {
        return Inertia::render('auth/TwoFactorChallenge');
    }
}
