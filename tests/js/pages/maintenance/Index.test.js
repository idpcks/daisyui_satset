import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetRouter, router } from '@tests/helpers/inertia.js';
import MaintenanceIndex from '@/pages/maintenance/Index.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const ModalStub = {
    name: 'Modal',
    props: ['modelValue', 'title'],
    template: '<div><slot /><slot name="footer" /></div>',
};

const mountPage = (props = {}) =>
    mount(MaintenanceIndex, {
        props: { active: false, retry: null, ...props },
        global: { stubs: { Modal: ModalStub } },
    });

describe('halaman maintenance/Index', () => {
    beforeEach(() => {
        resetRouter();
    });

    it('menampilkan status aplikasi normal saat maintenance nonaktif', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Aplikasi normal');
        expect(wrapper.text()).toContain('Aktifkan Maintenance');
        expect(wrapper.text()).not.toContain('php artisan up');
    });

    it('mengaktifkan maintenance dengan retry opsional', async () => {
        const wrapper = mountPage();

        await wrapper.get('button.btn-error').trigger('click');

        expect(wrapper.text()).toContain('Yakin ingin mengaktifkan maintenance mode?');

        await wrapper.get('#retry').setValue('300');
        await wrapper.get('[data-testid="confirm-enable"]').trigger('click');

        expect(router.post).toHaveBeenCalledWith(
            '/maintenance',
            { retry: 300 },
            expect.any(Object),
        );
    });

    it('mengirim retry null bila kolom dibiarkan kosong', async () => {
        const wrapper = mountPage();

        await wrapper.get('[data-testid="confirm-enable"]').trigger('click');

        expect(router.post).toHaveBeenCalledWith(
            '/maintenance',
            { retry: null },
            expect.any(Object),
        );
    });

    it('menampilkan status aktif dan instruksi pemulihan lewat CLI', () => {
        const wrapper = mountPage({ active: true, retry: 600 });

        expect(wrapper.text()).toContain('Maintenance aktif');
        expect(wrapper.text()).toContain('600 detik');
        expect(wrapper.text()).toContain('php artisan up');
        expect(wrapper.text()).toContain('Nonaktifkan Maintenance');
    });

    it('meminta konfirmasi sebelum menonaktifkan maintenance', async () => {
        const wrapper = mountPage({ active: true });

        await wrapper.get('button.btn-success').trigger('click');

        expect(router.delete).not.toHaveBeenCalled();

        await wrapper.get('[data-testid="confirm-disable"]').trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/maintenance', expect.any(Object));
    });
});
