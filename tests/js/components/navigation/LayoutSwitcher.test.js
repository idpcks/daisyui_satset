import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetPage } from '@tests/helpers/inertia.js';
import { useNavigationLayout } from '@/lib/useNavigationLayout';
import LayoutSwitcher from '@/components/navigation/LayoutSwitcher.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const mountSwitcher = () => mount(LayoutSwitcher);

const button = (wrapper, label) => wrapper.find(`button[aria-label="${label}"]`);

describe('LayoutSwitcher', () => {
    beforeEach(() => {
        resetPage();
        useNavigationLayout().setLayout('sidebar');
    });

    it('menampilkan pilihan menu vertikal dan horizontal', () => {
        const wrapper = mountSwitcher();

        expect(wrapper.find('[role="group"]').attributes('aria-label')).toBe('Tampilan menu');
        expect(wrapper.classes()).toContain('join');
        expect(wrapper.classes()).toContain('hidden');
        expect(wrapper.classes()).toContain('lg:inline-flex');
        expect(button(wrapper, 'Gunakan menu vertikal').attributes('aria-pressed')).toBe('true');
        expect(button(wrapper, 'Gunakan menu horizontal').attributes('aria-pressed')).toBe('false');
    });

    it('mengganti layout dan menyimpannya', async () => {
        const wrapper = mountSwitcher();

        await button(wrapper, 'Gunakan menu horizontal').trigger('click');

        expect(useNavigationLayout().layout.value).toBe('topbar');
        expect(localStorage.getItem('navigation-layout')).toBe('topbar');
        expect(button(wrapper, 'Gunakan menu horizontal').attributes('aria-pressed')).toBe('true');
        expect(button(wrapper, 'Gunakan menu vertikal').classes()).not.toContain('btn-active');

        await button(wrapper, 'Gunakan menu vertikal').trigger('click');

        expect(useNavigationLayout().layout.value).toBe('sidebar');
        expect(localStorage.getItem('navigation-layout')).toBe('sidebar');
    });
});
