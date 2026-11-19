<?php

namespace App\Http\Controllers;

use App\Http\Requests\Settings\LogoutOtherSessionsRequest;
use App\Http\Requests\Settings\UpdateAccountRequest;
use App\Http\Requests\Settings\UpdatePasswordRequest;
use App\Http\Requests\Settings\UpdateSystemRequest;
use App\Models\LoginHistory;
use App\Models\Passkey;
use App\Models\Setting;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
        $allowedTabs = ['akun', 'keamanan', 'riwayat'];

        // Tab Sistem hanya tersedia bagi pemilik permission; tab yang tidak
        // diizinkan kembali ke Akun agar URL tidak bisa dipakai memaksa akses.
        if ($request->user()->can('system.manage')) {
            $allowedTabs[] = 'sistem';
        }

        if (! in_array($tab, $allowedTabs, true)) {
            $tab = 'akun';
        }

        UserSession::query()
            ->whereBelongsTo($user)
            ->expired()
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
                'system' => $this->systemSettings(),
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
     * Simpan konfigurasi level aplikasi dan catat perubahannya di audit log.
     */
    public function updateSystem(UpdateSystemRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $previous = $this->systemSettings();

        $values = [
            'app_name' => $validated['app_name'],
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'maintenance_enabled' => (bool) $validated['maintenance_enabled'],
            'maintenance_message' => $validated['maintenance_message'] ?? '',
        ];

        Setting::put([
            'app.name' => $values['app_name'],
            'app.locale' => $values['locale'],
            'app.timezone' => $values['timezone'],
            'maintenance.enabled' => $values['maintenance_enabled'],
            'maintenance.message' => $values['maintenance_message'],
        ]);

        // Hanya nilai yang benar-benar berubah yang dicatat, supaya audit log
        // tidak penuh oleh simpan ulang tanpa perubahan.
        $changes = collect($values)
            ->filter(fn (mixed $value, string $key): bool => ($previous[$key] ?? null) !== $value)
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                $key => ['old' => $previous[$key] ?? null, 'new' => $value],
            ]);

        if ($changes->isNotEmpty()) {
            activity('system')
                ->event('system_config_updated')
                ->causedBy($request->user())
                ->withProperties([
                    'changes' => $changes->all(),
                    'ip_address' => $request->ip(),
                    'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
                ])
                ->log('Konfigurasi sistem diperbarui.');
        }

        return back()->with('success', 'Konfigurasi sistem berhasil disimpan.');
    }

    /**
     * Nilai efektif konfigurasi sistem: nilai tersimpan menimpa default config.
     *
     * @return array<string, mixed>
     */
    private function systemSettings(): array
    {
        $settings = Setting::values([
            'app.name' => config('app.name'),
            'app.locale' => config('app.locale'),
            'app.timezone' => config('app.timezone'),
            'maintenance.enabled' => false,
            'maintenance.message' => '',
        ]);

        return [
            'app_name' => $settings['app.name'],
            'locale' => $settings['app.locale'],
            'timezone' => $settings['app.timezone'],
            'maintenance_enabled' => (bool) $settings['maintenance.enabled'],
            'maintenance_message' => (string) ($settings['maintenance.message'] ?? ''),
        ];
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
