import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import OtherSessionsPanel from '@/components/settings/OtherSessionsPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

// Stub modal supaya isi form tetap ter-render tanpa bergantung pada <dialog>.
const ModalStub = {
    name: 'Modal',
    props: ['modelValue', 'title'],
    template: '<div><slot /><slot name="footer" /></div>',
};

const mountPanel = () =>
    mount(OtherSessionsPanel, {
        global: { stubs: { Modal: ModalStub } },
    });

const findButton = (wrapper, label) =>
    wrapper.findAll('button').find((button) => button.text().trim() === label);

describe('komponen settings/OtherSessionsPanel', () => {
    beforeEach(() => {
        useForm.mockClear();
    });

    it('menjelaskan bahwa sesi saat ini tetap aktif', () => {
        const wrapper = mountPanel();

        expect(wrapper.text()).toContain('Sesi & Perangkat');
        expect(wrapper.text()).toContain('Sesi yang sedang Anda pakai tetap aktif.');
    });

    it('mengirim password untuk mengeluarkan sesi lain', async () => {
        const wrapper = mountPanel();
        const form = useForm.mock.results[0].value;

        await findButton(wrapper, 'Keluar dari Semua Sesi Lain').trigger('click');

        form.password = 'rahasia';
        await wrapper.find('#other-sessions-form').trigger('submit');

        expect(form.delete).toHaveBeenCalledWith(
            '/settings/security/other-sessions',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
