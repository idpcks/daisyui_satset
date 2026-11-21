import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import Footer from '@/components/ui/Footer.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const currentYear = new Date().getFullYear();

describe('Footer', () => {
    it('menampilkan hak cipta dengan tahun berjalan', () => {
        const wrapper = mount(Footer);

        expect(wrapper.text()).toBe(`Copyright © ${currentYear}. All rights reserved.`);

        wrapper.unmount();
    });

    it('memakai terjemahan dan mengisi parameter tahun', () => {
        setPage({
            props: {
                translations: { 'Copyright © :year. All rights reserved.': 'Hak cipta © :year.' },
            },
        });

        const wrapper = mount(Footer);

        expect(wrapper.text()).toBe(`Hak cipta © ${currentYear}.`);

        wrapper.unmount();
    });
});
