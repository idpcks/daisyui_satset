<?php

namespace App\Listeners;

use App\Models\UserSession;
use Illuminate\Auth\Events\CurrentDeviceLogout;
use Illuminate\Auth\Events\Logout;
use Illuminate\Http\Request;

class ForgetUserSession
{
    /**
     * Create the event listener.
     */
    public function __construct(private readonly Request $request)
    {
        //
    }

    /**
     * Hapus registry saat pengguna logout dari perangkat ini.
     */
    public function handleLogout(Logout $event): void
    {
        $this->forget();
    }

    /**
     * Hapus registry saat sesi perangkat ini dianggap tidak valid.
     */
    public function handleCurrentDeviceLogout(CurrentDeviceLogout $event): void
    {
        $this->forget();
    }

    /**
     * Hapus baris registry untuk session ID request saat ini.
     */
    private function forget(): void
    {
        if (! $this->request->hasSession()) {
            return;
        }

        UserSession::query()
            ->where('session_id', $this->request->session()->getId())
            ->delete();
    }
}
