<?php

namespace App\Services;

use App\Features\MagicLinkLogin;
use App\Features\Passkeys;
use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Pennant\Feature;

class FeatureFlagService
{
    /**
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
     * @return array<int, array<string, mixed>>
     */
    public function listFlags(): array
    {
        return collect(self::FLAGS)
            ->map(fn (array $flag, string $name): array => [
                'name' => $name,
                'label' => $flag['label'],
                'description' => $flag['description'],
                'active' => (bool) Feature::active($flag['class']),
            ])
            ->values()
            ->all();
    }

    public function exists(string $feature): bool
    {
        return array_key_exists($feature, self::FLAGS);
    }

    /**
     * @return array{class: class-string, label: string, description: string}
     */
    public function flag(string $feature): array
    {
        return self::FLAGS[$feature];
    }

    public function toggle(string $feature, bool $active, User $actor, Request $request): void
    {
        $flag = self::FLAGS[$feature];

        Feature::purge($flag['class']);
        Feature::for(null)->activate($flag['class'], $active);

        activity('feature')
            ->event($active ? 'feature_enabled' : 'feature_disabled')
            ->causedBy($actor)
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
    }
}
