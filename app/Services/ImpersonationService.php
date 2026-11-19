<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class ImpersonationService
{
    public const SESSION_IMPERSONATOR_ID = 'impersonator_id';

    public const SESSION_STARTED_AT = 'impersonation_started_at';

    /**
     * Mulai impersonation. Mengembalikan pesan error bila gagal, null bila sukses.
     */
    public function start(User $actor, User $target, Request $request): ?string
    {
        if ($request->session()->has(self::SESSION_IMPERSONATOR_ID)) {
            return 'Anda sudah berada dalam mode impersonation.';
        }

        $authorization = Gate::inspect('impersonate', $target);

        if ($authorization->denied()) {
            return $authorization->message();
        }

        $startedAt = now();

        $request->attributes->set('suppress_login_history', true);

        activity('impersonation')
            ->event('impersonation_started')
            ->causedBy($actor)
            ->performedOn($target)
            ->withProperties([
                ...$this->auditProperties($actor, $target, $request),
                'started_at' => $startedAt->toIso8601String(),
            ])
            ->log("{$actor->name} masuk sebagai {$target->name}.");

        Auth::login($target);

        $request->session()->put([
            self::SESSION_IMPERSONATOR_ID => $actor->id,
            self::SESSION_STARTED_AT => $startedAt->toIso8601String(),
        ]);
        $request->session()->regenerate();

        return null;
    }

    /**
     * Akhiri impersonation. Mengembalikan pesan error bila gagal, null bila sukses.
     */
    public function stop(Request $request): ?string
    {
        $impersonatorId = $request->session()->get(self::SESSION_IMPERSONATOR_ID);

        if (! $impersonatorId) {
            return 'Anda tidak sedang dalam mode impersonation.';
        }

        $impersonator = User::query()->find($impersonatorId);
        $target = $request->user();

        if (! $impersonator || ! $impersonator->is_active || ! $impersonator->hasRole('superadmin')) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return 'Akun asal tidak lagi tersedia. Silakan masuk kembali.';
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

        $request->attributes->set('suppress_login_history', true);

        Auth::login($impersonator);
        $request->session()->regenerate();

        return null;
    }

    /**
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
