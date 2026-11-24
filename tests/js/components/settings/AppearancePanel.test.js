import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetPage } from '@tests/helpers/inertia.js';
import { useNavigationLayout } from '@/lib/useNavigationLayout';
import AppearancePanel from '@/components/settings/AppearancePanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const mountPanel = () => mount(AppearancePanel);

describe('AppearancePanel', () => {
    beforeEach(() => {
        resetPage();
        useNavigationLayout().setLayout('sidebar');
    });

    it('menampilkan pilihan menu vertikal dan horizontal', () => {
        const wrapper = mountPanel();

        expect(wrapper.find('fieldset').exists()).toBe(true);
        expect(wrapper.find('legend').text()).toBe('Tampilan menu');
        expect(wrapper.findAll('input[type="radio"]')).toHaveLength(2);
        expect(wrapper.find('input[value="sidebar"]').element.checked).toBe(true);
        expect(wrapper.find('input[value="topbar"]').element.checked).toBe(false);
        expect(wrapper.text()).toContain('Menu vertikal');
        expect(wrapper.text()).toContain('Menu horizontal');
        expect(wrapper.text()).toContain('Pada layar kecil, navigasi tetap memakai drawer.');
    });

    it('menerapkan dan menyimpan pilihan menu horizontal', async () => {
        const wrapper = mountPanel();

        await wrapper.find('input[value="topbar"]').setValue(true);

        expect(useNavigationLayout().layout.value).toBe('topbar');
        expect(localStorage.getItem('navigation-layout')).toBe('topbar');
        expect(wrapper.find('input[value="topbar"]').element.checked).toBe(true);
        expect(wrapper.find('input[value="sidebar"]').element.checked).toBe(false);
    });
});
