<?php

use App\Http\Middleware\CheckUserStatus;
use App\Http\Middleware\EnsurePasskeysAreEnabled;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\TrackUserSession;
use Illuminate\Contracts\Auth\Middleware\AuthenticatesRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\AuthenticateSession;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->web(append: [
            HandleInertiaRequests::class,
            CheckUserStatus::class,
            AuthenticateSession::class,
            TrackUserSession::class,
            // Fortify menimpa config middleware paket passkey, jadi flag
            // ditegakkan di sini dan hanya menyasar rute `passkey.*`.
            EnsurePasskeysAreEnabled::class,
        ]);

        // Jalankan gate passkey sebelum auth/password.confirm agar fitur yang
        // nonaktif membalas 404, bukan redirect yang membocorkan rutenya.
        $middleware->prependToPriorityList(
            before: AuthenticatesRequests::class,
            prepend: EnsurePasskeysAreEnabled::class,
        );
        $middleware->redirectGuestsTo('/login');
        $middleware->redirectUsersTo('/dashboard');

        // Cookie bypass maintenance dibaca oleh stub `public/index.php`
        // sebelum Laravel boot saat halaman 503 di-prerender. Bila cookie ini
        // ikut dienkripsi, superadmin yang baru mengaktifkan maintenance akan
        // terkunci dan hanya bisa memulihkan lewat `php artisan up`.
        $middleware->encryptCookies(except: ['laravel_maintenance']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
