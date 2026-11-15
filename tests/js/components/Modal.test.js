import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import Modal from '@/components/ui/Modal.vue';

const mountModal = (props = {}) =>
    mount(Modal, {
        props: { modelValue: false, ...props },
        slots: { default: '<p>Isi modal</p>', footer: '<button type="button">Simpan</button>' },
    });

describe('Modal', () => {
    it('menyinkronkan status buka dialog dengan modelValue', async () => {
        const wrapper = mountModal();

        expect(wrapper.find('dialog').element.open).toBe(false);

        await wrapper.setProps({ modelValue: true });
        expect(wrapper.find('dialog').element.open).toBe(true);

        await wrapper.setProps({ modelValue: false });
        expect(wrapper.find('dialog').element.open).toBe(false);

        wrapper.unmount();
    });

    it('menutup lewat tombol tutup', async () => {
        const wrapper = mountModal({ modelValue: true });

        await wrapper.find('button[aria-label="Tutup dialog"]').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);

        wrapper.unmount();
    });

    it('menutup saat backdrop diklik, tetapi tidak saat isi modal diklik', async () => {
        const wrapper = mountModal({ modelValue: true });

        await wrapper.find('.modal-box').trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();

        await wrapper.find('dialog').trigger('click');
        expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);

        wrapper.unmount();
    });

    it('menutup lewat Escape dan event close bawaan dialog', async () => {
        const wrapper = mountModal({ modelValue: true });

        await wrapper.find('dialog').trigger('keydown', { key: 'Escape' });
        expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);

        wrapper.find('dialog').element.close();

        expect(wrapper.emitted('update:modelValue')).toHaveLength(2);

        wrapper.unmount();
    });

    it('menahan penutupan saat closeable dimatikan', async () => {
        const wrapper = mountModal({ modelValue: true, closeable: false });

        expect(wrapper.find('button[aria-label="Tutup dialog"]').exists()).toBe(false);
        expect(wrapper.find('dialog').attributes('closedby')).toBe('none');

        const cancel = new Event('cancel', { cancelable: true });
        wrapper.find('dialog').element.dispatchEvent(cancel);
        expect(cancel.defaultPrevented).toBe(true);

        await wrapper.find('dialog').trigger('keydown', { key: 'Escape' });
        await wrapper.find('dialog').trigger('click');

        expect(wrapper.emitted('update:modelValue')).toBeUndefined();

        wrapper.unmount();
    });

    it('menampilkan judul, isi, dan footer', () => {
        const wrapper = mountModal({ modelValue: true, title: 'Tambah Pengguna', size: 'lg' });

        expect(wrapper.find('h2').text()).toBe('Tambah Pengguna');
        expect(wrapper.text()).toContain('Isi modal');
        expect(wrapper.find('.modal-action').text()).toContain('Simpan');
        expect(wrapper.find('.modal-box').classes()).toContain('modal-box-wide');

        wrapper.unmount();
    });
});
