import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Footer from '@/components/ui/Footer.vue';

describe('Footer', () => {
    it('menampilkan hak cipta dengan tahun berjalan', () => {
        const wrapper = mount(Footer);

        expect(wrapper.text()).toContain(`Copyright © ${new Date().getFullYear()}`);
        expect(wrapper.text()).toContain('All rights reserved.');
    });
});
