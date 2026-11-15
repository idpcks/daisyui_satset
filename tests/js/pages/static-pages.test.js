import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import Dashboard from '@/pages/Dashboard.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('halaman statis', () => {
    it('menampilkan sambutan pengguna pada dashboard', () => {
        setPage({
            props: {
                appName: 'Starterkit',
                auth: {
                    user: { id: 1, name: 'Siti Aminah' },
                    roles: ['admin'],
                    permissions: ['user.manage'],
                },
            },
        });

        const wrapper = mount(Dashboard);

        expect(wrapper.find('h2').text()).toBe('Selamat datang, Siti Aminah');
        expect(wrapper.text()).toContain('Starterkit');
        expect(wrapper.text()).toContain('admin');
        expect(wrapper.findAll('.card')).toHaveLength(4);
    });
});
