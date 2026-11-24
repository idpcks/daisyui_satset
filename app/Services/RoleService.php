<?php

namespace App\Services;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    /**
     * @var list<string>
     */
    private array $roleOrder = ['superadmin', 'admin', 'operator'];

    /**
     * Daftar peran beserta permission dan metadata untuk Inertia.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function listRoles(User $actor): Collection
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->get()
            ->sortBy(function (Role $role): int {
                $position = array_search($role->name, $this->roleOrder, true);

                return $position === false ? count($this->roleOrder) : $position;
            })
            ->values();

        return $roles->map(fn (Role $role): array => [
            'id' => $role->id,
            'name' => $role->name,
            'permissions' => $role->permissions->pluck('name')->all(),
            'users_count' => $role->users_count,
            'is_superadmin' => $role->name === 'superadmin',
            'can_rename' => $actor->can('rename', $role),
            'can_delete' => $actor->can('delete', $role),
            'can_change_permissions' => $actor->can('changePermissions', $role),
        ]);
    }

    /**
     * Semua nama permission yang tersedia, terurut.
     *
     * @return Collection<int, string>
     */
    public function allPermissionNames(): Collection
    {
        return Permission::query()->orderBy('name')->pluck('name');
    }

    public function createRole(string $name): Role
    {
        return Role::create([
            'name' => $name,
            'guard_name' => 'web',
        ]);
    }

    public function renameRole(Role $role, string $newName): void
    {
        $role->update(['name' => $newName]);

        $this->forgetCachedPermissions();
    }

    public function deleteRole(Role $role): void
    {
        $role->delete();

        $this->forgetCachedPermissions();
    }

    public function syncPermissions(Role $role, array $permissions): void
    {
        $role->syncPermissions($permissions);

        $this->forgetCachedPermissions();
    }

    public function forgetCachedPermissions(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        // Naikkan versi cache agar shared prop permission/role pengguna tidak
        // stale setelah perubahan RBAC.
        Cache::forever('permission_cache_version', (int) Cache::get('permission_cache_version', 1) + 1);
    }
}
