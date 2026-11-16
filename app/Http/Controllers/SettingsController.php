<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\LogoutOtherSessionsRequest;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Models\LoginHistory;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class SettingsController extends Controller
{
    /**
     * Tampilkan halaman pengaturan akun dengan tab Akun, Keamanan, dan Riwayat Login.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();

        // Secret sudah dibuat tetapi kode belum dikonfirmasi: tab Keamanan
        // menampilkan QR code supaya pengguna bisa memindainya.
        $isPending = $user->two_factor_secret !== null && $user->two_factor_confirmed_at === null;

        $tab = $request->string('tab')->toString();

        if (! in_array($tab, ['akun', 'keamanan', 'riwayat'], true)) {
            $tab = 'akun';
        }

        UserSession::query()
            ->whereBelongsTo($user)
            ->where('last_activity', '<', now()->subMinutes((int) config('session.lifetime')))
            ->delete();

        $activeSessions = UserSession::query()
            ->whereBelongsTo($user)
            ->active()
            ->latest('last_activity')
            ->get()
            ->map(fn (UserSession $session): array => [
                'id' => $session->id,
                'ip_address' => $session->ip_address,
                'user_agent' => $session->user_agent,
                'last_activity' => $session->last_activity->toIso8601String(),
                'created_at' => $session->created_at->toIso8601String(),
                'is_current' => $session->session_id === $request->session()->getId(),
            ]);

        $loginHistory = LoginHistory::query()
            ->where(function (Builder $query) use ($user): void {
                $query->where('user_id', $user->id)
                    ->orWhere('email', Str::lower($user->email));
            })
            ->latest()
            ->limit(20)
            ->get()
            ->map(fn (LoginHistory $entry): array => [
                'id' => $entry->id,
                'successful' => $entry->successful,
                'ip_address' => $entry->ip_address,
                'user_agent' => $entry->user_agent,
                'created_at' => $entry->created_at->toIso8601String(),
            ]);

        return Inertia::render('settings/Index', [
            'tab' => $tab,
            'account' => [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
            ],
            'twoFactorEnabled' => $user->hasEnabledTwoFactorAuthentication(),
            'twoFactorPending' => $isPending,
            'qrCodeSvg' => $isPending ? $user->twoFactorQrCodeSvg() : null,
            'secretKey' => $isPending ? Fortify::currentEncrypter()->decrypt($user->two_factor_secret) : null,
            'recoveryCodes' => $user->hasEnabledTwoFactorAuthentication() ? $user->recoveryCodes() : [],
            'loginHistory' => $loginHistory,
            'sessions' => $activeSessions,
        ]);
    }

    /**
     * Perbarui data akun milik pengguna yang sedang login.
     */
    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update([
            ...$data,
            'updated_by' => $user->username,
        ]);

        return back()->with('success', 'Data akun berhasil diperbarui.');
    }

    /**
     * Ubah password akun milik pengguna yang sedang login.
     */
    public function updatePassword(UpdatePasswordRequest $request): RedirectResponse
    {
        $user = $request->user();

        // Kolom password memakai cast `hashed`, jadi nilai polos di-hash otomatis.
        $user->update([
            'password' => $request->validated('password'),
            'updated_by' => $user->username,
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }

    /**
     * Keluarkan semua sesi lain milik pengguna yang sedang login.
     */
    public function logoutOtherSessions(LogoutOtherSessionsRequest $request): RedirectResponse
    {
        $user = $request->user();
        $currentSessionId = $request->session()->getId();
        $otherSessions = UserSession::query()
            ->whereBelongsTo($user)
            ->where('session_id', '!=', $currentSessionId)
            ->get();

        Auth::logoutOtherDevices($request->validated('password'));

        $this->terminateSessions($request, $otherSessions);

        return back()->with('success', 'Semua sesi lain berhasil dikeluarkan.');
    }

    /**
     * Keluarkan satu sesi perangkat milik pengguna yang sedang login.
     */
    public function logoutSession(LogoutOtherSessionsRequest $request, UserSession $userSession): RedirectResponse
    {
        $user = $request->user();

        if ($userSession->user_id !== $user->id) {
            abort(404);
        }

        if ($userSession->session_id === $request->session()->getId()) {
            return back()->with('error', 'Sesi yang sedang Anda pakai tidak dapat dikeluarkan dari sini.');
        }

        $this->terminateSessions($request, [$userSession]);

        return back()->with('success', 'Sesi perangkat berhasil dikeluarkan.');
    }

    /**
     * Hapus data session dan baris registry untuk sesi yang dipilih.
     */
    private function terminateSessions(Request $request, iterable $sessions): void
    {
        $handler = $request->session()->getHandler();

        foreach ($sessions as $session) {
            $handler->destroy($session->session_id);
            $session->delete();
        }
    }
}
