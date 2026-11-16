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
        'changeRole',
        'deactivate',
        'delete',
        'impersonate',
    ];

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
     * dari aplikasi.
     */
    public function deactivate(User $actor, User $target): bool
    {
        return $actor->isNot($target);
    }

    /**
     * Hapus pengguna. Akun sendiri tidak boleh dihapus.
     */
    public function delete(User $actor, User $target): bool
    {
        return $actor->isNot($target);
    }

    /**
     * Masuk sebagai pengguna lain.
     *
     * Penolakan dikembalikan sebagai Response agar controller bisa memakai
     * pesan yang spesifik untuk tiap alasan.
     */
    public function impersonate(User $actor, User $target): Response
    {
        if (! $actor->hasRole('superadmin')) {
            return Response::deny('Hanya superadmin yang dapat masuk sebagai pengguna lain.');
        }

        if ($actor->is($target)) {
            return Response::deny('Anda tidak dapat masuk sebagai akun sendiri.');
        }

        if ($target->hasRole('superadmin')) {
            return Response::deny('Akun superadmin lain tidak dapat di-impersonate.');
        }

        if (! $target->is_active) {
            return Response::deny('Akun yang nonaktif tidak dapat di-impersonate.');
        }

        return Response::allow();
    }
}
