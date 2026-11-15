import { mount } from '@vue/test-utils';
import { describe, expect, it } from 'vitest';
import { SearchX } from 'lucide-vue-next';
import EmptyState from '@/components/ui/EmptyState.vue';

describe('komponen ui/EmptyState', () => {
    it('menampilkan judul, deskripsi, ikon default, dan border', () => {
        const wrapper = mount(EmptyState, {
            props: { title: 'Belum ada data.', description: 'Tambahkan data pertama.' },
        });

        expect(wrapper.text()).toContain('Belum ada data.');
        expect(wrapper.text()).toContain('Tambahkan data pertama.');
        expect(wrapper.find('svg').exists()).toBe(true);
        expect(wrapper.classes()).toContain('border-dashed');
    });

    it('menyembunyikan deskripsi bila tidak diisi', () => {
        const wrapper = mount(EmptyState, { props: { title: 'Kosong.' } });

        expect(wrapper.findAll('p')).toHaveLength(1);
    });

    it('merender tombol aksi lewat slot action', () => {
        const wrapper = mount(EmptyState, {
            props: { title: 'Kosong.' },
            slots: { action: '<button type="button">Tambahkan</button>' },
        });

        expect(wrapper.find('button').text()).toBe('Tambahkan');
    });

    it('bisa dipakai tanpa border, mis. di dalam tabel', () => {
        const wrapper = mount(EmptyState, { props: { title: 'Kosong.', bordered: false } });

        expect(wrapper.classes()).not.toContain('border-dashed');
    });

    it('menerima ikon kustom', () => {
        const wrapper = mount(EmptyState, { props: { title: 'Kosong.', icon: SearchX } });

        expect(wrapper.find('svg').exists()).toBe(true);
    });
});
