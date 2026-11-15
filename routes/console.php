<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwalkan tugas aplikasi di sini, mis.:
//
// Schedule::command('nama-perintah')->hourly()->onOneServer();
//
// Butuh cron `* * * * * php artisan schedule:run` di server.
