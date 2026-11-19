<?php

namespace App\Http\Controllers;

use App\Http\Requests\Rbac\StoreRoleRequest;
use App\Http\Requests\Rbac\UpdateRoleNameRequest;
use App\Http\Requests\Rbac\UpdateRolePermissionsRequest;
use App\Models\Permission;
use App\Models\Role;
use App\Services\RoleService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class RbacController extends Controller
{
    public function __construct(
        private readonly RoleService $roles,
    ) {}

    /**
     * Tampilkan peran beserta permission yang dimilikinya.
     */
    public function index(Request $request): Response
    {
        $actor = $request->user();

        return Inertia::render('rbac/Index', [
            'roles' => $this->roles->listRoles($actor),
            'permissions' => $this->roles->allPermissionNames(),
        ]);
    }

    /**
     * Buat peran baru tanpa permission.
     */
    public function store(StoreRoleRequest $request): RedirectResponse
    {
        $role = $this->roles->createRole($request->validated('name'));

        return back()->with('success', __('messages.roles.created', ['name' => $role->name]));
    }

    /**
     * Ubah nama satu peran tanpa menyentuh permission dan penggunanya.
     */
    public function rename(UpdateRoleNameRequest $request, Role $role): RedirectResponse
    {
        $authorization = Gate::inspect('rename', $role);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $oldName = $role->name;
        $newName = $request->validated('name');
        $this->roles->renameRole($role, $newName);

        return back()->with('success', __('messages.roles.renamed', ['old' => $oldName, 'new' => $role->name]));
    }

    /**
     * Hapus peran yang tidak lagi dipakai pengguna.
     */
    public function destroy(Role $role): RedirectResponse
    {
        $authorization = Gate::inspect('delete', $role);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $name = $role->name;
        $this->roles->deleteRole($role);

        return back()->with('success', __('messages.roles.deleted', ['name' => $name]));
    }

    /**
     * Ganti daftar permission milik satu peran.
     */
    public function update(UpdateRolePermissionsRequest $request, Role $role): RedirectResponse
    {
        $authorization = Gate::inspect('changePermissions', $role);

        if ($authorization->denied()) {
            return back()->with('error', $authorization->message());
        }

        $this->roles->syncPermissions($role, $request->validated('permissions', []));

        return back()->with('success', __('messages.roles.permissions_updated', ['name' => $role->name]));
    }
}
