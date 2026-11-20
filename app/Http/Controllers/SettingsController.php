<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\LogoutOtherSessionsRequest;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Http\Requests\Settings\UpdateSystemRequest;
use App\Models\LoginHistory;
use App\Models\Passkey;
use App\Models\UserSession;
use App\Services\SystemSettingsService;
use App\Services\UserSessionService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Fortify\Fortify;

class SettingsController extends Controller
{
    public function __construct(
        private readonly SystemSettingsService $systemSettings,
        private readonly UserSessionService $sessions,
    ) {}

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
        $allowedTabs = ['akun', 'keamanan', 'riwayat'];

        // Tab Sistem hanya tersedia bagi pemilik permission; tab yang tidak
        // diizinkan kembali ke Akun agar URL tidak bisa dipakai memaksa akses.
        if ($request->user()->can('system.manage')) {
            $allowedTabs[] = 'sistem';
        }

        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'akun';
        }

        $this->sessions->cleanupExpired($user);

        $activeSessions = $this->sessions->activeSessions($user, $request->session()->getId());

        // Hanya kirim metadata yang aman: credential dan credential_id tidak
        // boleh bocor ke browser.
        $passkeys = $user->passkeys()
            ->latest()
            ->get()
            ->map(fn (Passkey $passkey): array => [
                'id' => $passkey->id,
                'name' => $passkey->name,
                'authenticator' => $passkey->authenticator,
                'created_at' => $passkey->created_at?->toIso8601String(),
                'last_used_at' => $passkey->last_used_at?->toIso8601String(),
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
            'must_change_password' => $user->must_change_password,
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
            'passkeys' => $passkeys,
            // Data konfigurasi hanya dikirim saat tab Sistem aktif supaya
            // daftar timezone tidak membebani tab lain.
            ...($tab === 'sistem' ? [
                'system' => $this->systemSettings->values(),
                'locales' => collect((array) config('system.locales'))
                    ->map(fn (string $label, string $value): array => [
                        'value' => $value,
                        'label' => $label,
                    ])
                    ->values()
                    ->all(),
                'timezones' => timezone_identifiers_list(),
            ] : []),
        ]);
    }

    /**
     * Perbarui data akun milik pengguna yang sedang login.
     */
    public function updateAccount(UpdateAccountRequest $request): RedirectResponse
    {
        $user = $request->user();

        // `current_password` hanya dipakai untuk konfirmasi, bukan kolom akun.
        $data = Arr::except($request->validated(), ['current_password']);

        $user->update([
            ...$data,
            'updated_by' => $user->username,
        ]);

        return back()->with('success', __('messages.settings.account_updated'));
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
            'must_change_password' => false,
            'updated_by' => $user->username,
        ]);

        return back()->with('success', __('messages.settings.password_updated'));
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

        $this->sessions->logoutOtherDevices($request->validated('password'));

        $this->sessions->terminateSessions($request, $otherSessions);

        return back()->with('success', __('messages.settings.other_sessions_logged_out'));
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
            return back()->with('error', __('messages.settings.current_session_cannot_be_terminated'));
        }

        $this->sessions->terminateSessions($request, [$userSession]);

        return back()->with('success', __('messages.settings.session_logged_out'));
    }

    /**
     * Simpan konfigurasi level aplikasi dan catat perubahannya di audit log.
     */
    public function updateSystem(UpdateSystemRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $changes = $this->systemSettings->update(
            $validated,
            $request->user(),
            $request->ip(),
            (string) $request->userAgent(),
        );

        return back()->with('success', __('messages.settings.system_updated'));
    }
}
