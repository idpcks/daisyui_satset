<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rbac\StoreRoleRequest;
use App\Http\Requests\Rbac\UpdateRoleNameRequest;
use App\Http\Requests\Rbac\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\PermissionRegistrar;

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
     * Buat peran baru tanpa permission.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        return back()->with('success', "Peran {$role->name} berhasil ditambahkan.");
    }

    /**
     * Ubah nama satu peran tanpa menyentuh permission dan penggunanya.
     */
    public function rename(UpdateRoleNameRequest $request, Role $role): RedirectResponse
    {
        if ($role->name === 'superadmin') {
            return back()->with('error', 'Nama peran superadmin tidak dapat diubah.');
        }

        $oldName = $role->name;
        $role->update(['name' => $request->validated('name')]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Peran {$oldName} diubah menjadi {$role->name}.");
    }

    /**
     * Hapus peran yang tidak lagi dipakai pengguna.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->name === 'superadmin') {
            return back()->with('error', 'Peran superadmin tidak dapat dihapus.');
        }

        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            return back()->with(
                'error',
                "Peran {$role->name} masih dipakai {$usersCount} pengguna, jadi belum bisa dihapus.",
            );
        }

        $name = $role->name;
        $role->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('success', "Peran {$name} berhasil dihapus.");
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
