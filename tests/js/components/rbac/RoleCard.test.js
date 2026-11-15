import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import RoleCard from '@/components/rbac/RoleCard.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const permissions = ['role.manage', 'user.manage'];

const makeRole = (overrides = {}) => ({
    id: 'role-1',
    name: 'admin',
    permissions: ['user.manage'],
    users_count: 2,
    is_superadmin: false,
    ...overrides,
});

const mountCard = (role = makeRole()) => mount(RoleCard, { props: { role, permissions } });

describe('komponen rbac/RoleCard', () => {
    beforeEach(() => {
        useForm.mockClear();
    });

    it('menampilkan label peran, jumlah pengguna, dan semua permission', () => {
        const wrapper = mountCard();
        const checkboxes = wrapper.findAll('input[type="checkbox"]');

        expect(wrapper.find('h2').text()).toBe('Admin');
        expect(wrapper.text()).toContain('2 pengguna');
        expect(checkboxes).toHaveLength(2);
        expect(wrapper.text()).toContain('Kelola pengguna');
        expect(wrapper.text()).toContain('Kelola peran & permission');
    });

    it('mencentang permission yang sudah dimiliki peran', () => {
        const wrapper = mountCard();
        const checkboxes = wrapper.findAll('input[type="checkbox"]');

        // Urutan mengikuti props.permissions: role.manage, lalu user.manage.
        expect(checkboxes[0].element.checked).toBe(false);
        expect(checkboxes[1].element.checked).toBe(true);
    });

    it('menyimpan perubahan ke endpoint peran', async () => {
        const wrapper = mountCard();
        const form = useForm.mock.results[0].value;

        form.permissions = ['role.manage'];

        await wrapper.find('form').trigger('submit');

        expect(form.put).toHaveBeenCalledWith(
            '/rbac/roles/role-1',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('mengunci kartu superadmin', () => {
        const wrapper = mountCard(
            makeRole({ name: 'superadmin', is_superadmin: true, permissions: [...permissions] }),
        );

        expect(wrapper.text()).toContain('Tetap');
        wrapper
            .findAll('input[type="checkbox"]')
            .forEach((checkbox) => expect(checkbox.element.disabled).toBe(true));
        expect(wrapper.find('button[type="submit"]').element.disabled).toBe(true);
    });
});
