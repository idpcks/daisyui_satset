<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserSession;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class UserSessionService
{
    /**
     * Hapus sesi yang sudah kedaluwarsa milik pengguna.
     */
    public function cleanupExpired(User $user): void
    {
        UserSession::query()
            ->whereBelongsTo($user)
            ->expired()
            ->delete();
    }

    /**
     * Daftar sesi aktif milik pengguna, lengkap dengan flag sesi saat ini.
     *
     * @return array<int, array<string, mixed>>
     */
    public function activeSessions(User $user, string $currentSessionId): array
    {
        return UserSession::query()
            ->whereBelongsTo($user)
            ->active()
            ->latest('last_activity')
            ->get()
            ->map(function (UserSession $session) use ($currentSessionId): array {
                return [
                    'id' => $session->id,
                    'ip_address' => $session->ip_address,
                    'user_agent' => $session->user_agent,
                    'last_activity' => Carbon::parse($session->last_activity)->toIso8601String(),
                    'created_at' => $session->created_at->toIso8601String(),
                    'is_current' => $session->session_id === $currentSessionId,
                ];
            })
            ->all();
    }

    /**
     * Keluarkan perangkat lain milik pengguna yang sedang login.
     */
    public function logoutOtherDevices(string $password): void
    {
        Auth::logoutOtherDevices($password);
    }

    /**
     * Hapus data session dan baris registry untuk sesi yang dipilih.
     */
    public function terminateSessions(Request $request, iterable $sessions): void
    {
        $handler = $request->session()->getHandler();

        foreach ($sessions as $session) {
            $handler->destroy($session->session_id);
            $session->delete();
        }
    }
}
