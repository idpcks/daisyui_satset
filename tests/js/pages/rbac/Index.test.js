import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetRouter, router, useForm } from '@tests/helpers/inertia.js';
import { permissionLabel } from '@/lib/labels';
import RbacIndex from '@/pages/rbac/Index.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const permissions = ['role.manage', 'user.manage'];

const roles = [
    {
        id: 'r-super',
        name: 'superadmin',
        permissions: [...permissions],
        users_count: 1,
        is_superadmin: true,
        can_rename: false,
        can_delete: false,
        can_change_permissions: false,
    },
    {
        id: 'r-admin',
        name: 'admin',
        permissions: [...permissions],
        users_count: 2,
        is_superadmin: false,
        can_rename: true,
        can_delete: false,
        can_change_permissions: true,
    },
    {
        id: 'r-operator',
        name: 'operator',
        permissions: [],
        users_count: 0,
        is_superadmin: false,
        can_rename: true,
        can_delete: true,
        can_change_permissions: true,
    },
    {
        id: 'r-auditor',
        name: 'auditor',
        permissions: [],
        users_count: 0,
        is_superadmin: false,
        can_rename: true,
        can_delete: true,
        can_change_permissions: true,
    },
];

const mountPage = () => mount(RbacIndex, { props: { roles, permissions } });

const checkbox = (wrapper, permission, role) =>
    wrapper.get(`input[aria-label="${permission} untuk ${role}"]`);

const permissionCheckboxes = (wrapper) => wrapper.findAll('tbody input[data-permission-checkbox]');

describe('halaman rbac/Index', () => {
    beforeEach(() => {
        useForm.mockClear();
        resetRouter();
    });

    it('menampilkan daftar peran di kolom kiri dan matriks permission di kolom kanan', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Peran');
        expect(wrapper.text()).toContain('Tambah peran');
        expect(wrapper.text()).toContain('Matriks Permission');
        expect(wrapper.text()).toContain('Super Admin');
        expect(wrapper.text()).toContain('Auditor');
        expect(wrapper.text()).toContain('2 pengguna');
        expect(permissionCheckboxes(wrapper)).toHaveLength(roles.length * permissions.length);
    });

    it('menyimpan permission peran lewat tombol Simpan', async () => {
        const wrapper = mountPage();

        await checkbox(wrapper, 'Kelola pengguna', 'Operator').setValue(true);

        expect(router.put).not.toHaveBeenCalled();

        await wrapper.get('button[aria-label="Simpan permission Operator"]').trigger('click');

        expect(router.put).toHaveBeenCalledWith(
            '/rbac/roles/r-operator',
            { permissions: ['user.manage'] },
            expect.objectContaining({
                only: ['roles', 'flash'],
                preserveScroll: true,
                preserveState: true,
            }),
        );

        expect(checkbox(wrapper, 'Kelola pengguna', 'Operator').element.checked).toBe(true);
    });

    it('membatalkan perubahan yang belum disimpan', async () => {
        const wrapper = mountPage();

        await checkbox(wrapper, 'Kelola pengguna', 'Operator').setValue(true);
        await wrapper.get('button[aria-label="Batalkan perubahan Operator"]').trigger('click');

        expect(checkbox(wrapper, 'Kelola pengguna', 'Operator').element.checked).toBe(false);
        expect(router.put).not.toHaveBeenCalled();
    });

    it('mengembalikan checkbox ke kondisi server saat penyimpanan gagal', async () => {
        const wrapper = mountPage();

        await checkbox(wrapper, 'Kelola pengguna', 'Operator').setValue(true);
        await wrapper.get('button[aria-label="Simpan permission Operator"]').trigger('click');

        router.put.mock.calls[0][2].onError();
        await wrapper.vm.$nextTick();

        expect(checkbox(wrapper, 'Kelola pengguna', 'Operator').element.checked).toBe(false);
    });

    it('memfilter permission berdasarkan nama atau label', async () => {
        const wrapper = mountPage();

        await wrapper.get('input[aria-label="Cari permission"]').setValue('pengguna');

        expect(permissionCheckboxes(wrapper)).toHaveLength(roles.length);
        expect(wrapper.text()).toContain('1 dari 2 permission');
        expect(wrapper.text()).not.toContain('role.manage');
    });

    it('mengelompokkan permission dan bisa melipat kelompoknya', async () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Role');
        expect(wrapper.text()).toContain('User');

        await wrapper.get('button[aria-label="Sembunyikan kelompok User"]').trigger('click');

        expect(permissionCheckboxes(wrapper)).toHaveLength(roles.length);
        expect(wrapper.text()).not.toContain('user.manage');
    });

    it('memilih semua permission yang tampil untuk satu peran', async () => {
        const wrapper = mountPage();

        await wrapper
            .get(
                'input[data-role-checkbox][aria-label="Pilih semua permission yang tampil untuk Operator"]',
            )
            .setValue(true);

        expect(router.put).not.toHaveBeenCalled();

        await wrapper.get('button[aria-label="Simpan permission Operator"]').trigger('click');

        expect(router.put).toHaveBeenCalledWith(
            '/rbac/roles/r-operator',
            { permissions: ['role.manage', 'user.manage'] },
            expect.objectContaining({ only: ['roles', 'flash'] }),
        );
    });

    it('memilih semua permission dalam satu kelompok untuk satu peran', async () => {
        const wrapper = mountPage();

        await wrapper
            .get(
                'input[data-group-checkbox][aria-label="Pilih semua permission User yang tampil untuk Operator"]',
            )
            .setValue(true);

        await wrapper.get('button[aria-label="Simpan permission Operator"]').trigger('click');

        expect(router.put).toHaveBeenCalledWith(
            '/rbac/roles/r-operator',
            { permissions: ['user.manage'] },
            expect.objectContaining({ only: ['roles', 'flash'] }),
        );
    });

    it('menandai pilihan sebagian pada checkbox kelompok dan peran', () => {
        const wrapper = mount(RbacIndex, {
            props: {
                permissions: ['role.manage', 'user.manage', 'user.view'],
                roles: [
                    {
                        id: 'r-admin',
                        name: 'admin',
                        permissions: ['role.manage', 'user.manage'],
                        users_count: 0,
                        is_superadmin: false,
                        can_rename: true,
                        can_delete: true,
                        can_change_permissions: true,
                    },
                ],
            },
        });

        const groupCheckbox = wrapper.get(
            'input[data-group-checkbox][aria-label="Pilih semua permission User yang tampil untuk Admin"]',
        );
        const roleCheckbox = wrapper.get(
            'input[data-role-checkbox][aria-label="Pilih semua permission yang tampil untuk Admin"]',
        );

        expect(groupCheckbox.element.checked).toBe(false);
        expect(groupCheckbox.element.indeterminate).toBe(true);
        expect(roleCheckbox.element.indeterminate).toBe(true);
    });

    it('mengunci semua checkbox superadmin', () => {
        const wrapper = mountPage();

        permissions.forEach((permission) => {
            expect(
                checkbox(wrapper, permissionLabel(permission), 'Super Admin').element.disabled,
            ).toBe(true);
        });
    });

    it('mengirim form tambah peran ke endpoint RBAC', async () => {
        const wrapper = mountPage();
        const form = useForm.mock.results[0].value;

        form.name = 'auditor';
        await wrapper.get('#create-role-form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith(
            '/rbac/roles',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('mengirim nama peran baru lewat modal ubah nama', async () => {
        const wrapper = mountPage();
        const renameForm = useForm.mock.results[1].value;

        await wrapper.get('button[aria-label="Ubah nama Admin"]').trigger('click');

        expect(wrapper.find('dialog').element.open).toBe(true);

        renameForm.name = 'administrator';
        await wrapper.get('#rename-role-form').trigger('submit');

        expect(renameForm.patch).toHaveBeenCalledWith(
            '/rbac/roles/r-admin',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('menghapus peran setelah konfirmasi', async () => {
        const wrapper = mountPage();

        await wrapper.get('button[aria-label="Hapus peran Operator"]').trigger('click');
        await wrapper.get('.modal-action .btn-error').trigger('click');

        expect(router.delete).toHaveBeenCalledWith(
            '/rbac/roles/r-operator',
            expect.objectContaining({ preserveScroll: true }),
        );
        expect(router.delete.mock.calls[0][1]).toHaveProperty('data', undefined);
    });

    it('tidak menyediakan aksi ubah/hapus untuk superadmin', () => {
        const wrapper = mountPage();

        expect(wrapper.find('button[aria-label="Ubah nama Super Admin"]').exists()).toBe(false);
        expect(wrapper.find('button[aria-label="Hapus peran Super Admin"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Tetap');
    });

    it('mengikuti izin server per peran', () => {
        const wrapper = mountPage();

        // Peran yang masih dipakai pengguna tidak menampilkan tombol hapus,
        // tetapi namanya masih boleh diubah.
        expect(wrapper.find('button[aria-label="Ubah nama Admin"]').exists()).toBe(true);
        expect(wrapper.find('button[aria-label="Hapus peran Admin"]').exists()).toBe(false);

        // Peran yang tidak dipakai menampilkan keduanya.
        expect(wrapper.find('button[aria-label="Ubah nama Operator"]').exists()).toBe(true);
        expect(wrapper.find('button[aria-label="Hapus peran Operator"]').exists()).toBe(true);
    });
});
