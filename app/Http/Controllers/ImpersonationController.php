<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ImpersonationController extends Controller
{
    /**
     * Key session untuk superadmin asli selama mode impersonation.
     */
    public const SESSION_IMPERSONATOR_ID = 'impersonator_id';

    /**
     * Key session waktu mulai impersonation.
     */
    public const SESSION_STARTED_AT = 'impersonation_started_at';

    /**
     * Mulai masuk sebagai pengguna lain.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $actor = $request->user();

        if ($request->session()->has(self::SESSION_IMPERSONATOR_ID)) {
            return back()->with('error', 'Anda sudah berada dalam mode impersonation.');
        }

        $authorization = Gate::inspect('impersonate', $user);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $startedAt = now();

        // Perpindahan akun oleh sistem bukan login pengguna sebenarnya.
        $request->attributes->set('suppress_login_history', true);

        activity('impersonation')
            ->event('impersonation_started')
            ->causedBy($actor)
            ->performedOn($user)
            ->withProperties([
                ...$this->auditProperties($actor, $user, $request),
                'started_at' => $startedAt->toIso8601String(),
            ])
            ->log("{$actor->name} masuk sebagai {$user->name}.");

        Auth::login($user);

        $request->session()->put([
            self::SESSION_IMPERSONATOR_ID => $actor->id,
            self::SESSION_STARTED_AT => $startedAt->toIso8601String(),
        ]);
        $request->session()->regenerate();

        return to_route('dashboard')
            ->with('success', "Anda sekarang masuk sebagai {$user->name}.");
    }

    /**
     * Kembali ke akun superadmin asal.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);

        if (! $impersonatorId) {
            return back()->with('error', 'Anda tidak sedang dalam mode impersonation.');
        }

        $impersonator = User::query()->find($impersonatorId);
        $target = $request->user();

        if (! $impersonator || ! $impersonator->is_active || ! $impersonator->hasRole('superadmin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return to_route('login')
                ->with('error', 'Akun asal tidak lagi tersedia. Silakan masuk kembali.');
        }

        $startedAt = Carbon::parse(
            $request->session()->get(self::SESSION_STARTED_AT) ?? now(),
        );
        $stoppedAt = now();

        activity('impersonation')
            ->event('impersonation_stopped')
            ->causedBy($impersonator)
            ->performedOn($target)
            ->withProperties([
                ...$this->auditProperties($impersonator, $target, $request),
                'started_at' => $startedAt->toIso8601String(),
                'stopped_at' => $stoppedAt->toIso8601String(),
                'duration_seconds' => max(0, (int) $startedAt->diffInSeconds($stoppedAt)),
            ])
            ->log("{$impersonator->name} kembali dari akun {$target->name}.");

        $request->session()->forget([
            self::SESSION_IMPERSONATOR_ID,
            self::SESSION_STARTED_AT,
        ]);

        // Kembali ke akun asal bukan login pengguna sebenarnya.
        $request->attributes->set('suppress_login_history', true);

        Auth::login($impersonator);
        $request->session()->regenerate();

        return to_route('dashboard')
            ->with('success', "Anda kembali masuk sebagai {$impersonator->name}.");
    }

    /**
     * Data pelaku dan target yang tetap terbaca walau pengguna dihapus.
     *
     * @return array<string, string|null>
     */
    private function auditProperties(User $actor, User $target, Request $request): array
    {
        return [
            'impersonator_name' => $actor->name,
            'impersonator_username' => $actor->username,
            'impersonated_name' => $target->name,
            'impersonated_username' => $target->username,
            'ip_address' => $request->ip(),
            'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
        ];
    }
}
