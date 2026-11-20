<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Throwable;

#[Fillable(['key', 'value', 'created_by', 'updated_by', 'deleted_by'])]
class Setting extends Model
{
    use HasUuids;

    /**
     * Satu cache untuk seluruh setelan agar setiap request cukup membaca satu
     * kali dari database.
     */
    public const CACHE_KEY = 'app-settings';

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'value' => 'json',
        ];
    }

    /**
     * Seluruh setelan sebagai peta key => value.
     *
     * @param  array<string, mixed>  $defaults
     * @return array<string, mixed>
     */
    public static function values(array $defaults = []): array
    {
        try {
            $stored = Cache::rememberForever(self::CACHE_KEY, fn (): array => self::query()
                ->get(['key', 'value'])
                ->mapWithKeys(fn (self $setting): array => [$setting->key => $setting->value])
                ->all());
        } catch (Throwable) {
            // Tabel belum ada (mis. saat migrate) atau cache sedang mati:
            // aplikasi tetap berjalan dengan nilai default dari config.
            return $defaults;
        }

        return [...$defaults, ...$stored];
    }

    /**
     * Simpan beberapa setelan sekaligus lalu buang cache-nya.
     *
     * @param  array<string, mixed>  $values
     */
    public static function put(array $values): void
    {
        foreach ($values as $key => $value) {
            self::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }

        Cache::forget(self::CACHE_KEY);
    }
}
