import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import RbacIndex from '@/pages/rbac/Index.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const roles = [
    {
        id: 'r-super',
        name: 'superadmin',
        permissions: ['role.manage', 'user.manage'],
        users_count: 1,
        is_superadmin: true,
    },
    { id: 'r-admin', name: 'admin', permissions: ['user.manage'], users_count: 2, is_superadmin: false },
    { id: 'r-operator', name: 'operator', permissions: [], users_count: 0, is_superadmin: false },
];

const mountPage = (props = {}) =>
    mount(RbacIndex, {
        props: { roles, permissions: ['role.manage', 'user.manage'], ...props },
    });

describe('halaman rbac/Index', () => {
    it('menampilkan satu kartu untuk setiap peran', () => {
        const wrapper = mountPage();

        expect(wrapper.findAll('.card')).toHaveLength(3);
        expect(wrapper.text()).toContain('Super Admin');
        expect(wrapper.text()).toContain('Admin');
        expect(wrapper.text()).toContain('Operator');
    });

    it('menampilkan pesan saat belum ada peran', () => {
        const wrapper = mountPage({ roles: [] });

        expect(wrapper.findAll('.card')).toHaveLength(0);
        expect(wrapper.text()).toContain('Atur permission yang dimiliki setiap peran.');
    });
});
