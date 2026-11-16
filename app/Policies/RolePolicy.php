<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Aturan kontekstual manajemen peran.
 *
 * Akses dasar ke halaman RBAC tetap dijaga middleware `can:role.manage`.
 * Policy ini hanya memutuskan hal yang bergantung pada peran target, sehingga
 * aturannya tidak lagi tersebar sebagai pengecekan manual di controller.
 *
 * Penolakan dikembalikan sebagai Response agar controller bisa memakai pesan
 * yang spesifik untuk tiap alasan.
 */
class RolePolicy
{
    /**
     * Ability yang aturannya bergantung pada target dan karenanya tidak boleh
     * dilewati oleh Gate::before milik superadmin.
     *
     * @var list<string>
     */
    public const CONTEXTUAL_ABILITIES = [
        'rename',
        'delete',
        'changePermissions',
    ];

    /**
     * Ubah nama peran. Nama superadmin dipakai Gate::before dan seeder, jadi
     * tidak boleh berubah.
     */
    public function rename(User $actor, Role $role): Response
    {
        if ($this->isSuperadmin($role)) {
            return Response::deny('Nama peran superadmin tidak dapat diubah.');
        }

        return Response::allow();
    }

    /**
     * Hapus peran. Superadmin tidak boleh dihapus, dan peran yang masih
     * dipakai pengguna harus tetap ada.
     */
    public function delete(User $actor, Role $role): Response
    {
        if ($this->isSuperadmin($role)) {
            return Response::deny('Peran superadmin tidak dapat dihapus.');
        }

        $usersCount = $role->users()->count();

        if ($usersCount > 0) {
            return Response::deny(
                "Peran {$role->name} masih dipakai {$usersCount} pengguna, jadi belum bisa dihapus.",
            );
        }

        return Response::allow();
    }

    /**
     * Ganti daftar permission peran. Superadmin selalu lolos semua pengecekan
     * lewat Gate::before, jadi daftar permission-nya sengaja dibuat tetap.
     */
    public function changePermissions(User $actor, Role $role): Response
    {
        if ($this->isSuperadmin($role)) {
            return Response::deny('Permission superadmin tidak dapat diubah.');
        }

        return Response::allow();
    }

    /**
     * Peran superadmin adalah peran yang menjalankan Gate::before.
     */
    private function isSuperadmin(Role $role): bool
    {
        return $role->name === 'superadmin';
    }
}
