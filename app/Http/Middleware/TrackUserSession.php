<?php

namespace App\Http\Middleware;

use App\Models\UserSession;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class TrackUserSession
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null && $request->hasSession()) {
            $sessionId = $request->session()->getId();

            UserSession::query()->updateOrCreate(
                ['session_id' => $sessionId],
                [
                    'user_id' => $user->getKey(),
                    'ip_address' => $request->ip(),
                    'user_agent' => Str::limit((string) $request->userAgent(), 500, ''),
                    'last_activity' => now(),
                ],
            );

            $response = $next($request);

            $currentSessionId = $request->session()->getId();

            if ($currentSessionId !== $sessionId) {
                UserSession::query()->where('session_id', $sessionId)->delete();
            }

            return $response;
        }

        return $next($request);
    }
}
