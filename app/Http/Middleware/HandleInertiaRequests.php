<?php

namespace App\Http\Middleware;

use App\Http\Controllers\ImpersonationController;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Middleware;
use Laravel\Fortify\Features;

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
            // Status fitur Fortify yang perlu diketahui UI. Config tetap menjadi
            // sumber kebenaran: mematikan fitur juga menghapus rutenya.
            'features' => [
                'passkeys' => Features::enabled(Features::passkeys()),
            ],
            'auth' => [
                'user' => $user?->only('id', 'name', 'username', 'email', 'avatar'),
                'roles' => fn () => $user?->getRoleNames()->values() ?? [],
                'permissions' => fn () => $user
                    ?->getAllPermissions()
                    ->pluck('name')
                    ->values() ?? [],
                'is_superadmin' => fn () => $user?->hasRole('superadmin') ?? false,
                'impersonation' => fn () => $this->impersonation($request),
            ],
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Data sesi impersonation yang dibutuhkan banner dan navigasi global.
     *
     * @return array{active: bool, started_at: string|null, impersonator: array<string, mixed>|null}|null
     */
    private function impersonation(Request $request): ?array
    {
        $impersonatorId = $request->session()->get(ImpersonationController::SESSION_IMPERSONATOR_ID);

        if (! $impersonatorId) {
            return null;
        }

        $impersonator = User::query()->find($impersonatorId);

        return [
            'active' => true,
            'started_at' => $request->session()->get(ImpersonationController::SESSION_STARTED_AT),
            'impersonator' => $impersonator?->only(['id', 'name', 'username']),
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
