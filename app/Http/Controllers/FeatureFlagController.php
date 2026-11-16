<?php

namespace App\Http\Controllers;

use App\Features\MagicLinkLogin;
use App\Features\Passkeys;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Pennant\Feature;

class FeatureFlagController extends Controller
{
    /**
     * Flag yang dapat dikelola dari panel beserta metadata tampilannya.
     *
     * @var array<string, array{class: class-string, label: string, description: string}>
     */
    private const FLAGS = [
        'passkeys-login' => [
            'class' => Passkeys::class,
            'label' => 'Login Passkey',
            'description' => 'Login dan pengelolaan passkey. Saat nonaktif, rute passkey membalas 404 dan panelnya disembunyikan dari halaman Pengaturan.',
        ],
        'magic-link-login' => [
            'class' => MagicLinkLogin::class,
            'label' => 'Login Tautan Email',
            'description' => 'Login lewat tautan yang dikirim ke email. Saat nonaktif, seluruh rute magic link membalas 404.',
        ],
    ];

    /**
     * Tampilkan seluruh flag beserta status aktifnya.
     */
    public function index(): Response
    {
        $flags = collect(self::FLAGS)
            ->map(fn (array $flag, string $name): array => [
                'name' => $name,
                'label' => $flag['label'],
                'description' => $flag['description'],
                'active' => (bool) Feature::active($flag['class']),
            ])
            ->values();

        return Inertia::render('features/Index', [
            'flags' => $flags,
        ]);
    }

    /**
     * Aktifkan atau nonaktifkan satu flag.
     */
    public function update(Request $request, string $feature): RedirectResponse
    {
        abort_unless(array_key_exists($feature, self::FLAGS), 404);

        $validated = $request->validate([
            'active' => ['required', 'boolean'],
        ]);

        $flag = self::FLAGS[$feature];
        $active = (bool) $validated['active'];

        // Hapus nilai per pengguna yang mungkin tersisa dari sebelum flag
        // dikelola secara global, lalu simpan satu nilai untuk scope global.
        Feature::purge($flag['class']);
        Feature::for(null)->activate($flag['class'], $active);

        activity('feature')
            ->event($active ? 'feature_enabled' : 'feature_disabled')
            ->causedBy($request->user())
            ->withProperties([
                'feature' => $feature,
                'label' => $flag['label'],
                'active' => $active,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ])
            ->log($active
                ? "Fitur {$flag['label']} diaktifkan."
                : "Fitur {$flag['label']} dinonaktifkan.");

        return back()->with(
            'success',
            $active
                ? "Fitur {$flag['label']} berhasil diaktifkan."
                : "Fitur {$flag['label']} berhasil dinonaktifkan.",
        );
    }
}
