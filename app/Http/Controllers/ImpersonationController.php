<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ImpersonationController extends Controller
{
    public const SESSION_IMPERSONATOR_ID = ImpersonationService::SESSION_IMPERSONATOR_ID;

    public const SESSION_STARTED_AT = ImpersonationService::SESSION_STARTED_AT;

    public function __construct(
        private readonly ImpersonationService $impersonation,
    ) {}

    /**
     * Mulai masuk sebagai pengguna lain.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $error = $this->impersonation->start($request->user(), $user, $request);

        if ($error !== null) {
            if (str_contains($error, 'sudah berada')) {
                return back()->with('error', $error);
            }

            return back()->with('error', $error);
        }

        return to_route('dashboard')
            ->with('success', "Anda sekarang masuk sebagai {$user->name}.");
    }

    /**
     * Kembali ke akun superadmin asal.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);
        $impersonator = $impersonatorId ? User::query()->find($impersonatorId) : null;

        $error = $this->impersonation->stop($request);

        if ($error !== null) {
            if (str_contains($error, 'tidak lagi tersedia')) {
                return to_route('login')->with('error', $error);
            }

            return back()->with('error', $error);
        }

        return to_route('dashboard')
            ->with('success', "Anda kembali masuk sebagai {$impersonator?->name}.");
    }
}
