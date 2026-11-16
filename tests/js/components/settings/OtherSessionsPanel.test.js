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

const sessions = [
    {
        id: 1,
        ip_address: '127.0.0.1',
        user_agent: 'Mozilla/5.0 (Windows NT 10.0) Chrome/120.0.0.0 Safari/537.36',
        last_activity: '2026-11-16T08:00:00+00:00',
        is_current: true,
    },
    {
        id: 2,
        ip_address: '203.0.113.10',
        user_agent: 'Mozilla/5.0 (Linux; Android 14) Chrome/120.0.0.0 Mobile Safari/537.36',
        last_activity: '2026-11-16T07:30:00+00:00',
        is_current: false,
    },
];

const mountPanel = (panelSessions = sessions) =>
    mount(OtherSessionsPanel, {
        props: { sessions: panelSessions },
        global: { stubs: { Modal: ModalStub } },
    });

const findButton = (wrapper, label) =>
    wrapper.findAll('button').find((button) => button.text().trim() === label);

describe('komponen settings/OtherSessionsPanel', () => {
    beforeEach(() => {
        useForm.mockClear();
    });

    it('menampilkan sesi saat ini dan perangkat lain', () => {
        const wrapper = mountPanel();

        expect(wrapper.text()).toContain('Sesi & Perangkat');
        expect(wrapper.text()).toContain('Chrome · Windows');
        expect(wrapper.text()).toContain('Chrome · Android');
        expect(wrapper.text()).toContain('Sesi ini');
        expect(wrapper.text()).toContain('127.0.0.1');
        expect(wrapper.text()).toContain('203.0.113.10');
    });

    it('mengirim password untuk mengeluarkan sesi tertentu', async () => {
        const wrapper = mountPanel();
        const form = useForm.mock.results[0].value;

        await findButton(wrapper, 'Keluarkan').trigger('click');

        form.password = 'rahasia';
        await wrapper.find('#sessions-form').trigger('submit');

        expect(form.delete).toHaveBeenCalledWith(
            '/settings/security/sessions/2',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('mengirim password untuk mengeluarkan semua sesi lain', async () => {
        const wrapper = mountPanel();
        const form = useForm.mock.results[0].value;

        await findButton(wrapper, 'Keluar dari Semua Sesi Lain').trigger('click');

        form.password = 'rahasia';
        await wrapper.find('#sessions-form').trigger('submit');

        expect(form.delete).toHaveBeenCalledWith(
            '/settings/security/other-sessions',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('menonaktifkan aksi semua sesi lain jika hanya ada sesi saat ini', () => {
        const wrapper = mountPanel([sessions[0]]);

        expect(findButton(wrapper, 'Keluar dari Semua Sesi Lain').attributes('disabled')).toBeDefined();
    });
});
