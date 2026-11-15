<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rbac\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class RbacController extends Controller
{
    /**
     * Urutan tampil peran dari yang paling tinggi aksesnya.
     *
     * @var list<string>
     */
    private array $roleOrder = ['superadmin', 'admin', 'operator'];

    /**
     * Tampilkan peran beserta permission yang dimilikinya.
     */
    public function index(): Response
    {
        $roles = Role::query()
            ->with('permissions:id,name')
            ->withCount('users')
            ->get()
            ->sortBy(function (Role $role): int {
                $position = array_search($role->name, $this->roleOrder, true);

                return $position === false ? count($this->roleOrder) : $position;
            })
            ->values()
            ->map(fn (Role $role): array => [
                'id' => $role->id,
                'name' => $role->name,
                'permissions' => $role->permissions->pluck('name')->all(),
                'users_count' => $role->users_count,
                'is_superadmin' => $role->name === 'superadmin',
            ]);

        return Inertia::render('rbac/Index', [
            'roles' => $roles,
            'permissions' => Permission::query()->orderBy('name')->pluck('name'),
        ]);
    }

    /**
     * Ganti daftar permission milik satu peran.
     */
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        // Superadmin selalu lolos semua pengecekan lewat Gate::before, jadi
        // daftar permission-nya sengaja dibuat tetap.
        if ($role->name === 'superadmin') {
            return back()->with('error', 'Permission superadmin tidak dapat diubah.');
        }

        $role->syncPermissions($request->validated('permissions', []));

        return back()->with('success', "Permission peran {$role->name} berhasil diperbarui.");
    }
}
