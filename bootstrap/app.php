<?php

use App\Http\Middleware\AddSecurityHeaders;
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
            AddSecurityHeaders::class,
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

        // Hanya percayai X-Forwarded-For dari proxy yang disebut eksplisit.
        // Kosong = jangan percaya siapa pun, sehingga klien tidak bisa
        // memalsukan IP di riwayat login, registry sesi, dan audit log.
        // Skema URL tidak ikut dipercayai dari header: forceScheme() di
        // AppServiceProvider sudah menurunkannya dari APP_URL.
        $proxies = array_values(array_filter(
            array_map('trim', explode(',', (string) env('TRUSTED_PROXIES', ''))),
        ));

        if ($proxies !== []) {
            $middleware->trustProxies(
                at: $proxies,
                headers: Request::HEADER_X_FORWARDED_FOR,
            );
        }
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );
    })->create();
