<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Config;

class SystemSettingsService
{
    /**
     * Nilai efektif konfigurasi sistem: nilai tersimpan menimpa default config.
     *
     * @return array<string, mixed>
     */
    public function values(): array
    {
        $settings = Setting::values([
            'app.name' => config('app.name'),
            'app.locale' => config('app.locale'),
            'app.timezone' => config('app.timezone'),
            'maintenance.enabled' => false,
            'maintenance.message' => '',
        ]);

        return [
            'app_name' => $settings['app.name'],
            'locale' => $settings['app.locale'],
            'timezone' => $settings['app.timezone'],
            'maintenance_enabled' => (bool) $settings['maintenance.enabled'],
            'maintenance_message' => (string) ($settings['maintenance.message'] ?? ''),
        ];
    }

    /**
     * Simpan konfigurasi sistem dan catat perubahan yang benar-benar terjadi.
     *
     * @param  array<string, mixed>  $validated
     * @return Collection<string, array{old: mixed, new: mixed}>
     */
    public function update(array $validated, User $actor, string $ipAddress, string $userAgent): Collection
    {
        $previous = $this->values();

        $values = [
            'app_name' => $validated['app_name'],
            'locale' => $validated['locale'],
            'timezone' => $validated['timezone'],
            'maintenance_enabled' => (bool) $validated['maintenance_enabled'],
            'maintenance_message' => $validated['maintenance_message'] ?? '',
        ];

        Setting::put([
            'app.name' => $values['app_name'],
            'app.locale' => $values['locale'],
            'app.timezone' => $values['timezone'],
            'maintenance.enabled' => $values['maintenance_enabled'],
            'maintenance.message' => $values['maintenance_message'],
        ]);

        $changes = collect($values)
            ->filter(fn (mixed $value, string $key): bool => ($previous[$key] ?? null) !== $value)
            ->mapWithKeys(fn (mixed $value, string $key): array => [
                $key => ['old' => $previous[$key] ?? null, 'new' => $value],
            ]);

        if ($changes->isNotEmpty()) {
            activity('system')
                ->event('system_config_updated')
                ->causedBy($actor)
                ->withProperties([
                    'changes' => $changes->all(),
                    'ip_address' => $ipAddress,
                    'user_agent' => mb_substr($userAgent, 0, 500),
                ])
                ->log('Konfigurasi sistem diperbarui.');
        }

        return $changes;
    }
}
