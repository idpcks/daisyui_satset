import { mount } from '@vue/test-utils';
import { nextTick } from 'vue';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { page } from '@tests/helpers/inertia.js';
import { useToast } from '@/lib/useToast';
import Toast from '@/components/ui/Toast.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('Toast', () => {
    const { toasts } = useToast();

    beforeEach(() => {
        vi.useFakeTimers();
        toasts.value = [];
        page.props.flash = {};
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
        toasts.value = [];
    });

    it('meneruskan flash sukses dan error menjadi toast', async () => {
        page.props.flash = { success: 'Data tersimpan', error: 'Data gagal disimpan' };

        const wrapper = mount(Toast);
        await nextTick();

        expect(toasts.value.map((toast) => toast.message)).toEqual(['Data tersimpan', 'Data gagal disimpan']);

        const items = wrapper.findAll('.alert');
        expect(items).toHaveLength(2);
        expect(items[0].attributes('role')).toBe('status');
        expect(items[1].attributes('role')).toBe('alert');

        wrapper.unmount();
    });

    it('tidak membuat toast saat flash kosong', async () => {
        const wrapper = mount(Toast);
        await nextTick();

        expect(toasts.value).toHaveLength(0);

        wrapper.unmount();
    });

    it('menutup toast lewat tombol tutup', async () => {
        page.props.flash = { success: 'Sekali saja' };

        const wrapper = mount(Toast);
        await nextTick();

        expect(wrapper.findAll('.alert')).toHaveLength(1);

        await wrapper.find('button[aria-label="Tutup notifikasi"]').trigger('click');
        await nextTick();

        expect(wrapper.findAll('.alert')).toHaveLength(0);

        wrapper.unmount();
    });

    it('menerjemahkan label tombol tutup', async () => {
        page.props.translations = { 'Tutup notifikasi': 'Close notification' };
        page.props.flash = { success: 'Sekali saja' };

        const wrapper = mount(Toast);
        await nextTick();

        expect(wrapper.find('button[aria-label="Close notification"]').exists()).toBe(true);

        wrapper.unmount();
    });
});
