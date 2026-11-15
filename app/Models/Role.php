<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    use HasUuids;

    /**
     * Nama peran yang boleh diberikan oleh pengguna yang sedang login.
     *
     * @return list<string>
     */
    public static function assignableNamesFor(?User $actor): array
    {
        return static::query()
            ->where('guard_name', 'web')
            ->when(
                ! $actor?->hasRole('superadmin'),
                fn ($query) => $query->where('name', '!=', 'superadmin'),
            )
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }
}
