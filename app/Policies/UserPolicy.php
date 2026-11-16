<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\Response;

/**
 * Aturan kontekstual manajemen pengguna.
 *
 * Akses dasar ke halaman pengguna tetap dijaga middleware `can:user.manage`.
 * Policy ini hanya memutuskan hal yang bergantung pada akun target, sehingga
 * aturannya tidak lagi tersebar sebagai pengecekan manual di controller.
 */
class UserPolicy
{
    /**
     * Ability yang aturannya bergantung pada target dan karenanya tidak boleh
     * dilewati oleh Gate::before milik superadmin.
     *
     * @var list<string>
     */
    public const CONTEXTUAL_ABILITIES = [
        'update',
        'changeRole',
        'deactivate',
        'delete',
        'impersonate',
    ];

    /**
     * Ubah data pengguna.
     *
     * Akun superadmin hanya boleh diubah oleh superadmin lain. Tanpa aturan
     * ini, pengguna dengan `user.manage` bisa mengganti peran akun superadmin
     * lewat formulir ubah karena pilihan peran untuknya tidak memuat
     * superadmin.
     */
    public function update(User $actor, User $target): Response
    {
        if (! $this->isSuperadmin($actor) && $this->isSuperadmin($target)) {
            return Response::deny('Akun superadmin tidak dapat diubah.');
        }

        return Response::allow();
    }

    /**
     * Ubah peran pengguna.
     *
     * Peran akun sendiri tidak dapat diubah. Superadmin yang menyimpan
     * formulir akunnya sendiri tetap mengirim peran yang sama, jadi peran
     * superadmin dikecualikan seperti sebelumnya.
     */
    public function changeRole(User $actor, User $target, string $role): bool
    {
        return $actor->isNot($target) || $role === 'superadmin';
    }

    /**
     * Nonaktifkan pengguna.
     *
     * Akun sendiri tidak boleh dinonaktifkan agar pengguna tidak terkunci
     * dari aplikasi, dan akun superadmin hanya boleh dinonaktifkan oleh
     * superadmin lain.
     */
    public function deactivate(User $actor, User $target): Response
    {
        if ($actor->is($target)) {
            return Response::deny('Anda tidak dapat menonaktifkan akun sendiri.');
        }

        if (! $this->isSuperadmin($actor) && $this->isSuperadmin($target)) {
            return Response::deny('Akun superadmin tidak dapat dinonaktifkan.');
        }

        return Response::allow();
    }

    /**
     * Hapus pengguna.
     *
     * Akun sendiri dan akun superadmin (bagi non-superadmin) tidak boleh
     * dihapus.
     */
    public function delete(User $actor, User $target): Response
    {
        if ($actor->is($target)) {
            return Response::deny('Anda tidak dapat menghapus akun sendiri.');
        }

        if (! $this->isSuperadmin($actor) && $this->isSuperadmin($target)) {
            return Response::deny('Akun superadmin tidak dapat dihapus.');
        }

        return Response::allow();
    }

    /**
     * Masuk sebagai pengguna lain.
     *
     * Penolakan dikembalikan sebagai Response agar controller bisa memakai
     * pesan yang spesifik untuk tiap alasan.
     */
    public function impersonate(User $actor, User $target): Response
    {
        if (! $this->isSuperadmin($actor)) {
            return Response::deny('Hanya superadmin yang dapat masuk sebagai pengguna lain.');
        }

        if ($actor->is($target)) {
            return Response::deny('Anda tidak dapat masuk sebagai akun sendiri.');
        }

        if ($this->isSuperadmin($target)) {
            return Response::deny('Akun superadmin lain tidak dapat di-impersonate.');
        }

        if (! $target->is_active) {
            return Response::deny('Akun yang nonaktif tidak dapat di-impersonate.');
        }

        return Response::allow();
    }

    /**
     * Peran superadmin memberi akses penuh lewat Gate::before, sehingga
     * akunnya diperlakukan sebagai akun yang dilindungi.
     */
    private function isSuperadmin(User $user): bool
    {
        return $user->hasRole('superadmin');
    }
}
