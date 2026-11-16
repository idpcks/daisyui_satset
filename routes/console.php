<?php

use App\Models\UserSession;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('user-sessions:prune', function (): void {
    $pruned = UserSession::query()->expired()->delete();

    $this->info("{$pruned} baris sesi kedaluwarsa dihapus.");
})->purpose('Hapus baris registry sesi yang sudah kedaluwarsa');

// Registry sesi hanya dibersihkan saat pemiliknya membuka halaman pengaturan,
// jadi baris milik akun yang jarang login akan menumpuk. Scheduler menutup
// celah itu untuk semua pengguna.
//
// Butuh cron `* * * * * php artisan schedule:run` di server.
Schedule::command('user-sessions:prune')->hourly()->onOneServer();
