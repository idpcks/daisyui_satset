<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'appName' => config('app.name'),
            // Prefix sub-path (mis. "/admin"); kosong bila di root domain.
            'basePath' => config('app.base_path', ''),
            'auth' => [
                'user' => $user?->only('id', 'name', 'username', 'email', 'avatar'),
                'roles' => fn () => $user?->getRoleNames()->values() ?? [],
                'permissions' => fn () => $user
                    ?->getAllPermissions()
                    ->pluck('name')
                    ->values() ?? [],
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Sertakan prefix sub-path pada URL halaman yang dikirim ke Inertia.
     *
     * Reverse proxy membuang prefix sub-path sebelum request sampai ke
     * Laravel, sehingga tanpa ini Inertia menimpa address bar browser menjadi
     * "/login" (tanpa prefix). Bila base_path kosong (aplikasi di root
     * domain), perilakunya sama seperti bawaan.
     */
    public function urlResolver()
    {
        return function (Request $request) {
            return rtrim((string) config('app.base_path', ''), '/').$request->getRequestUri();
        };
    }
}
