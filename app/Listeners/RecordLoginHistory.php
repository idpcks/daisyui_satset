<?php

namespace App\Listeners;

use App\Models\LoginHistory;
use App\Models\User;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Login;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RecordLoginHistory
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly Request $request)
    {
        //
    }

    /**
     * Catat login yang berhasil.
     */
    public function handleLogin(Login $event): void
    {
        if ($event->guard !== 'web' || ! $event->user instanceof User) {
            return;
        }

        if ($this->request->attributes->getBoolean('suppress_login_history')) {
            return;
        }

        $this->record($event->user, true, $event->user->email);
    }

    /**
     * Catat percobaan login yang gagal tanpa menyimpan credential apa pun.
     */
    public function handleFailed(Failed $event): void
    {
        if ($event->guard !== 'web') {
            return;
        }

        if ($event->user instanceof User) {
            $this->record($event->user, false, $event->user->email);

            return;
        }

        $identifier = $event->credentials[(string) config('fortify.username', 'email')] ?? null;

        if (! is_string($identifier) || trim($identifier) === '') {
            return;
        }

        $this->record(null, false, $identifier);
    }

    /**
     * Simpan metadata request tanpa password atau isi credential lainnya.
     */
    private function record(?User $user, bool $successful, string $email): void
    {
        LoginHistory::create([
            'user_id' => $user?->id,
            'email' => Str::lower(trim($email)),
            'successful' => $successful,
            'ip_address' => $this->request->ip(),
            'user_agent' => Str::limit((string) $this->request->userAgent(), 500, ''),
        ]);
    }
}
