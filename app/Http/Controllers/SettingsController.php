<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\LogoutOtherSessionsRequest;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Models\LoginHistory;
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
        Auth::logoutOtherDevices($request->validated('password'));

        return back()->with('success', 'Semua sesi lain berhasil dikeluarkan.');
    }
}
