<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserService
{
    /**
     * Daftar pengguna dengan filter, pagination, dan mapping untuk Inertia.
     *
     * @param  array{search: mixed, status: mixed}  $filters
     * @return LengthAwarePaginator<int, array<string, mixed>>
     */
    public function paginateUsers(array $filters, User $actor, bool $isImpersonating): LengthAwarePaginator
    {
        return User::query()
            ->with('roles:id,name')
            ->when($filters['search'] ?? null, fn ($q, $search) => $q->where(
                fn ($query) => $query
                    ->whereLike('name', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('email', "%{$search}%", caseSensitive: false)
                    ->orWhereLike('username', "%{$search}%", caseSensitive: false)
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
                'can_update' => $actor->can('update', $user),
                'can_delete' => $actor->can('delete', $user),
                'can_impersonate' => ! $isImpersonating && $actor->can('impersonate', $user),
            ]);
    }

    /**
     * Buat pengguna baru beserta perannya.
     *
     * @param  array<string, mixed>  $data
     */
    public function createUser(array $data, User $actor): User
    {
        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $data['password'],
            'is_active' => $data['is_active'] ?? true,
            'created_by' => $actor->username,
            'updated_by' => $actor->username,
        ]);

        $user->assignRole($data['role']);

        return $user;
    }

    /**
     * Perbarui data pengguna, termasuk peran dan status aktif.
     *
     * @param  array<string, mixed>  $data
     */
    public function updateUser(User $user, array $data, User $actor): void
    {
        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        $role = $data['role'];
        unset($data['role']);

        $user->update([...$data, 'updated_by' => $actor->username]);
        $user->syncRoles([$role]);
    }

    /**
     * Hapus pengguna secara soft delete dengan pencatatan deleted_by.
     */
    public function deleteUser(User $user, User $actor): void
    {
        $user->update(['deleted_by' => $actor->username]);
        $user->delete();
    }

    /**
     * Ubah status aktif beberapa pengguna sekaligus.
     *
     * @param  array<int, int|string>  $ids
     */
    public function bulkUpdateStatus(array $ids, bool $isActive, User $actor): int
    {
        return User::query()
            ->whereIn('id', $ids)
            ->update([
                'is_active' => $isActive,
                'updated_by' => $actor->username,
            ]);
    }

    /**
     * Nama peran yang boleh diberikan oleh aktor.
     *
     * @return array<int, string>
     */
    public function assignableRoleNames(User $actor): array
    {
        return Role::assignableNamesFor($actor);
    }
}
