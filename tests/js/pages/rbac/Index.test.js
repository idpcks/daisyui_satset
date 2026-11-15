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
    },
    { id: 'r-admin', name: 'admin', permissions: [...permissions], users_count: 2, is_superadmin: false },
    { id: 'r-operator', name: 'operator', permissions: [], users_count: 0, is_superadmin: false },
    { id: 'r-auditor', name: 'auditor', permissions: [], users_count: 0, is_superadmin: false },
];

const mountPage = () => mount(RbacIndex, { props: { roles, permissions } });

const checkbox = (wrapper, permission, role) =>
    wrapper.get(`input[aria-label="${permission} untuk ${role}"]`);

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
        expect(wrapper.findAll('tbody input[type="checkbox"]')).toHaveLength(
            roles.length * permissions.length,
        );
    });

    it('menyimpan permission peran saat checkbox diubah', async () => {
        const wrapper = mountPage();

        await checkbox(wrapper, 'Kelola pengguna', 'Operator').setValue(true);

        expect(router.put).toHaveBeenCalledWith(
            '/rbac/roles/r-operator',
            { permissions: ['user.manage'] },
            expect.objectContaining({ preserveScroll: true }),
        );

        expect(checkbox(wrapper, 'Kelola pengguna', 'Operator').element.checked).toBe(true);
    });

    it('mengunci semua checkbox superadmin', () => {
        const wrapper = mountPage();

        permissions.forEach((permission) => {
            expect(checkbox(wrapper, permissionLabel(permission), 'Super Admin').element.disabled).toBe(
                true,
            );
        });
    });

    it('mengirim form tambah peran ke endpoint RBAC', async () => {
        const wrapper = mountPage();
        const form = useForm.mock.results[0].value;

        form.name = 'auditor';
        await wrapper.get('form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith(
            '/rbac',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
