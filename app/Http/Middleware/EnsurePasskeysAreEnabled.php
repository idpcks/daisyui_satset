<?php

namespace App\Http\Middleware;

use App\Features\Passkeys;
use Closure;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;
use Symfony\Component\HttpFoundation\Response;

/**
 * Menegakkan feature flag passkey pada rute yang didaftarkan laravel/passkeys.
 *
 * Middleware tidak bisa dipasang lewat config paket karena Fortify menimpa
 * `passkeys.middleware` dengan `fortify.middleware`. Middleware ini hanya
 * menyentuh rute `passkey.*`, sehingga rute Fortify lain tidak terpengaruh.
 */
class EnsurePasskeysAreEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($request->routeIs('passkey.*') && ! Feature::active(Passkeys::class)) {
            abort(404);
        }

        return $next($request);
    }
}
