import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import GuestLayout from '@/Layouts/GuestLayout.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('GuestLayout', () => {
    it('menampilkan nama aplikasi dan isi halaman', () => {
        setPage({ props: { appName: 'Starterkit' } });

        const wrapper = mount(GuestLayout, { slots: { default: '<p>Form login</p>' } });

        expect(wrapper.find('h1').text()).toBe('Starterkit');
        expect(wrapper.text()).toContain('Form login');
    });

    it('memakai nama cadangan saat appName tidak tersedia', () => {
        setPage({ props: { appName: undefined } });

        const wrapper = mount(GuestLayout);

        expect(wrapper.find('h1').text()).toBe('Aplikasi');
    });
});
