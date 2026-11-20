<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsurePasswordChanged
{
    /**
     * Paksa pengguna dengan password sementara untuk mengganti password
     * sebelum mengakses halaman lain.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user instanceof User && $user->must_change_password) {
            if (! $request->routeIs('settings.index', 'settings.password.update', 'logout')) {
                return redirect()
                    ->route('settings.index', ['tab' => 'keamanan'])
                    ->with('error', 'Anda harus mengganti password sebelum melanjutkan.');
            }
        }

        return $next($request);
    }
}
