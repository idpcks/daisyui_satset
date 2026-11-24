import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetPage } from '@tests/helpers/inertia.js';
import { useNavigationLayout } from '@/lib/useNavigationLayout';
import LayoutFab from '@/components/navigation/LayoutFab.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('LayoutFab', () => {
    beforeEach(() => {
        resetPage();
        useNavigationLayout().setLayout('sidebar');
    });

    it('memakai FAB DaisyUI flower dengan tooltip', () => {
        const wrapper = mount(LayoutFab);

        expect(wrapper.find('.fab').classes()).toContain('fab-flower');
        expect(wrapper.find('.fab').classes()).toContain('bottom-20');
        expect(wrapper.find('.fab').classes()).toContain('lg:bottom-4');
        expect(wrapper.find('[role="button"]').attributes('aria-label')).toBe('Tampilan menu');
        expect(wrapper.findAll('.tooltip.tooltip-left')).toHaveLength(2);
        expect(wrapper.find('.tooltip[data-tip="Menu vertikal"]').exists()).toBe(true);
        expect(wrapper.find('.tooltip[data-tip="Menu horizontal"]').exists()).toBe(true);
    });

    it('mengganti layout saat tombol speed dial diklik', async () => {
        const wrapper = mount(LayoutFab);

        await wrapper.find('button[aria-label="Gunakan menu horizontal"]').trigger('click');

        expect(useNavigationLayout().layout.value).toBe('topbar');
        expect(localStorage.getItem('navigation-layout')).toBe('topbar');
        expect(wrapper.find('button[aria-label="Gunakan menu horizontal"]').classes()).toContain(
            'btn-primary',
        );

        await wrapper.find('button[aria-label="Gunakan menu vertikal"]').trigger('click');

        expect(useNavigationLayout().layout.value).toBe('sidebar');
        expect(localStorage.getItem('navigation-layout')).toBe('sidebar');
    });
});
