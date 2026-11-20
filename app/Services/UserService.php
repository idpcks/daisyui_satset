<?php

namespace App\Services;

use App\Models\Role;
use App\Models\User;
use App\Notifications\SetPasswordLinkNotification;
use App\Support\GeneratedPassword;
use EmailMagicLink\Facades\EmailMagicLink;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Log;

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
                'must_change_password' => $user->must_change_password,
                'role' => $user->roles->first()?->name,
                'can_update' => $actor->can('update', $user),
                'can_delete' => $actor->can('delete', $user),
                'can_impersonate' => ! $isImpersonating && $actor->can('impersonate', $user),
            ]);
    }

    /**
     * Buat pengguna baru beserta perannya.
     *
     * Jika password tidak diisi, sistem membuat password acak yang memenuhi
     * standar OWASP dan menandai akun agar wajib menggantinya saat login
     * pertama.
     *
     * @param  array<string, mixed>  $data
     * @return array{user: User, password: string|null, magic_link_sent: bool}
     */
    public function createUser(array $data, User $actor): array
    {
        $generated = blank($data['password'] ?? null);
        $plainPassword = $generated
            ? GeneratedPassword::make()
            : (string) $data['password'];

        $user = User::create([
            'name' => $data['name'],
            'username' => $data['username'],
            'email' => $data['email'],
            'password' => $plainPassword,
            'is_active' => $data['is_active'] ?? true,
            'must_change_password' => $generated,
            'created_by' => $actor->username,
            'updated_by' => $actor->username,
        ]);

        $user->assignRole($data['role']);

        $magicLinkSent = false;

        if ($generated) {
            try {
                $issued = EmailMagicLink::issueLink($user);
                $user->notify(new SetPasswordLinkNotification(
                    $issued->url,
                    $issued->expiresInMinutes,
                ));
                $magicLinkSent = true;
            } catch (\Throwable $e) {
                Log::warning('Gagal mengirim magic link untuk user baru.', [
                    'user_id' => $user->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }

        return [
            'user' => $user,
            'password' => $generated ? $plainPassword : null,
            'magic_link_sent' => $magicLinkSent,
        ];
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
     * Reset password pengguna dan kirim tautan aman ke emailnya.
     *
     * @return array{user: User, password: string, magic_link_sent: bool}
     */
    public function resetPassword(User $user, User $actor): array
    {
        $plainPassword = GeneratedPassword::make();

        $user->update([
            'password' => $plainPassword,
            'must_change_password' => true,
            'updated_by' => $actor->username,
        ]);

        $magicLinkSent = $this->sendPasswordLink($user);

        return [
            'user' => $user,
            'password' => $plainPassword,
            'magic_link_sent' => $magicLinkSent,
        ];
    }

    /**
     * Kirim ulang tautan aman untuk mengatur password tanpa mengubah password.
     */
    public function sendPasswordLink(User $user): bool
    {
        try {
            $issued = EmailMagicLink::issueLink($user);

            $user->notify(new SetPasswordLinkNotification(
                $issued->url,
                $issued->expiresInMinutes,
            ));

            return true;
        } catch (\Throwable $e) {
            Log::warning('Gagal mengirim tautan password.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
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
