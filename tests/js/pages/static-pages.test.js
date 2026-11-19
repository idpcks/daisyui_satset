import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import Dashboard from '@/pages/Dashboard.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('halaman statis', () => {
    it('menampilkan kartu statistik dashboard tanpa kartu sambutan', () => {
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

        expect(wrapper.text()).not.toContain('Selamat datang');
        expect(wrapper.find('h2').exists()).toBe(false);
        expect(wrapper.findAll('.card')).toHaveLength(3);
    });
});
