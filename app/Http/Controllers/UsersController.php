<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest\BulkUpdateUserStatusRequest;
use App\Http\Requests\UserRequest\StoreUserRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class UsersController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $filters = [
            'search' => $request->input('search'),
            'status' => $request->input('status'),
        ];

        $actor = $request->user();
        $isImpersonating = $request->session()->has(ImpersonationController::SESSION_IMPERSONATOR_ID);

        $users = User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(
                fn ($query) => $query
                    ->where('name', 'ilike', "%{$search}%")
                    ->orWhere('email', 'ilike', "%{$search}%")
                    ->orWhere('username', 'ilike', "%{$search}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $status) => match ($status) {
                'active' => $q->where('is_active', true),
                'inactive' => $q->where('is_active', false),
                default => $q,
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString()
            ->through(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'username' => $user->username,
                'email' => $user->email,
                'is_active' => $user->is_active,
                'role' => $user->roles->first()?->name,
                'can_impersonate' => ! $isImpersonating && $actor->can('impersonate', $user),
            ]);

        return Inertia::render('users/Index', [
            'users' => $users,
            'filters' => $filters,
            'roles' => Role::assignableNamesFor($request->user()),
        ]);
    }

    /**
     * Aktifkan atau nonaktifkan beberapa pengguna sekaligus.
     */
    public function bulkStatus(BulkUpdateUserStatusRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();
        $isActive = (bool) $data['is_active'];

        $blocked = ! $isActive && User::query()
            ->whereIn('id', $data['ids'])
            ->get()
            ->contains(fn (User $user): bool => $actor->cannot('deactivate', $user));

        if ($blocked) {
            return back()->withErrors(['ids' => 'Akun sendiri tidak dapat dinonaktifkan.']);
        }

        $updated = User::query()
            ->whereIn('id', $data['ids'])
            ->update([
                'is_active' => $isActive,
                'updated_by' => $actor->username,
            ]);

        $status = $isActive ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "{$updated} pengguna berhasil {$status}.");
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $request->user()->username,
            'updated_by' => $request->user()->username,
        ]);

        $user->assignRole($data['role']);

        return to_route('users.index')->with('success', 'Pengguna baru berhasil ditambahkan.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        $actor = $request->user();

        if (! ($data['is_active'] ?? true) && $actor->cannot('deactivate', $user)) {
            return back()->withErrors(['is_active' => 'Anda tidak dapat menonaktifkan akun sendiri.']);
        }

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $role = $data['role'];
        unset($data['role']);

        if ($actor->cannot('changeRole', [$user, $role])) {
            return back()->withErrors(['role' => 'Anda tidak dapat mengubah peran akun sendiri.']);
        }

        $user->update([...$data, 'updated_by' => $actor->username]);
        $user->syncRoles([$role]);

        return to_route('users.index')->with('success', 'Data pengguna berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()->cannot('delete', $user)) {
            return back()->with('error', 'Anda tidak dapat menghapus akun sendiri.');
        }

        $user->update(['deleted_by' => $request->user()->username]);
        $user->delete();

        return to_route('users.index')->with('success', 'Pengguna berhasil dihapus.');
    }
}
