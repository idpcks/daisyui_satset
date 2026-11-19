import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import SystemPanel from '@/components/settings/SystemPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const settings = {
    app_name: 'Starterkit',
    locale: 'id',
    timezone: 'Asia/Jakarta',
    maintenance_enabled: false,
    maintenance_message: '',
};

const mountPanel = (props = {}) =>
    mount(SystemPanel, {
        props: {
            settings,
            locales: [
                { value: 'id', label: 'Indonesia' },
                { value: 'en', label: 'English' },
            ],
            timezones: ['Asia/Jakarta', 'Asia/Makassar'],
            ...props,
        },
    });

describe('komponen settings/SystemPanel', () => {
    beforeEach(() => {
        useForm.mockClear();
    });

    it('mengisi form dengan konfigurasi saat ini', () => {
        mountPanel();

        const form = useForm.mock.results[0].value;

        expect(form.app_name).toBe('Starterkit');
        expect(form.locale).toBe('id');
        expect(form.timezone).toBe('Asia/Jakarta');
        expect(form.maintenance_enabled).toBe(false);
    });

    it('menampilkan pilihan bahasa dan zona waktu dari props', () => {
        const wrapper = mountPanel();
        const selects = wrapper.findAll('select');

        expect(selects[0].findAll('option').map((option) => option.text())).toEqual(['Indonesia', 'English']);
        expect(selects[1].findAll('option').map((option) => option.text())).toEqual([
            'Asia/Jakarta',
            'Asia/Makassar',
        ]);
    });

    it('menonaktifkan pesan maintenance saat mode maintenance mati', () => {
        const wrapper = mountPanel();

        expect(wrapper.find('textarea').attributes('disabled')).toBeDefined();
    });

    it('mengaktifkan pesan maintenance saat toggle dinyalakan', async () => {
        const wrapper = mountPanel();

        await wrapper.find('input[type=checkbox]').setValue(true);

        expect(wrapper.find('textarea').attributes('disabled')).toBeUndefined();
    });

    it('mengirim perubahan lewat PUT ke /settings/system', async () => {
        const wrapper = mountPanel();
        const form = useForm.mock.results[0].value;

        form.app_name = 'Panel Baru';
        await wrapper.find('form').trigger('submit');

        expect(form.put).toHaveBeenCalledWith('/settings/system', expect.objectContaining({ preserveScroll: true }));
    });

    it('menampilkan pesan error validasi dari server', async () => {
        const wrapper = mountPanel();
        const form = useForm.mock.results[0].value;

        form.errors.app_name = 'Nama aplikasi wajib diisi.';
        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Nama aplikasi wajib diisi.');
    });
});
