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
     * @var array<string, array{class: class-string, label_key: string, description_key: string}>
     */
    private const FLAGS = [
        'passkeys-login' => [
            'class' => Passkeys::class,
            'label_key' => 'messages.features.flags.passkeys_login.label',
            'description_key' => 'messages.features.flags.passkeys_login.description',
        ],
        'magic-link-login' => [
            'class' => MagicLinkLogin::class,
            'label_key' => 'messages.features.flags.magic_link_login.label',
            'description_key' => 'messages.features.flags.magic_link_login.description',
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
                'label' => __($flag['label_key']),
                'description' => __($flag['description_key']),
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
        $flag = self::FLAGS[$feature];

        return [
            'class' => $flag['class'],
            'label' => __($flag['label_key']),
            'description' => __($flag['description_key']),
        ];
    }

    public function toggle(string $feature, bool $active, User $actor, Request $request): void
    {
        $flag = self::FLAGS[$feature];
        $label = __($flag['label_key']);

        Feature::purge($flag['class']);
        Feature::for(null)->activate($flag['class'], $active);

        activity('feature')
            ->event($active ? 'feature_enabled' : 'feature_disabled')
            ->causedBy($actor)
            ->withProperties([
                'feature' => $feature,
                'label' => $label,
                'active' => $active,
                'ip_address' => $request->ip(),
                'user_agent' => mb_substr((string) $request->userAgent(), 0, 500),
            ])
            ->log($active
                ? __('messages.features.activity_enabled', ['label' => $label])
                : __('messages.features.activity_disabled', ['label' => $label]));
    }
}
