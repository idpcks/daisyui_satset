import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import CommandPalette from '@/components/ui/CommandPalette.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const Icon = { name: 'Icon', render: () => null };

const entries = [
    {
        key: 'dashboard',
        kind: 'menu',
        href: '/app',
        icon: Icon,
        label: 'Dashboard',
        rawLabel: 'Dashboard',
        groupKey: 'Utama',
        groupLabel: 'Utama',
    },
    {
        key: 'users',
        kind: 'menu',
        href: '/users',
        icon: Icon,
        label: 'Pengguna',
        rawLabel: 'Pengguna',
        groupKey: 'Administrasi',
        groupLabel: 'Administrasi',
    },
    {
        key: 'rbac',
        kind: 'menu',
        href: '/rbac',
        icon: Icon,
        label: 'RBAC',
        rawLabel: 'RBAC',
        groupKey: 'Administrasi',
        groupLabel: 'Administrasi',
    },
    {
        key: 'settings',
        kind: 'action',
        href: '/settings',
        icon: Icon,
        label: 'Pengaturan',
        rawLabel: 'Pengaturan',
        groupKey: 'Aksi',
        groupLabel: 'Aksi',
    },
];

const mountPalette = (props = {}) =>
    mount(CommandPalette, {
        props: { modelValue: true, entries, ...props },
    });

const optionLabels = (wrapper) =>
    wrapper.findAll('[role="option"]').map((option) => option.text());

const input = (wrapper) => wrapper.find('input[role="combobox"]');

const selectedLabel = (wrapper) =>
    wrapper.find('[role="option"][aria-selected="true"]').exists()
        ? wrapper.find('[role="option"][aria-selected="true"]').text()
        : null;

describe('CommandPalette', () => {
    it('membuka dialog sesuai modelValue', async () => {
        const wrapper = mountPalette({ modelValue: false });

        expect(wrapper.find('dialog').element.open).toBe(false);

        await wrapper.setProps({ modelValue: true });
        expect(wrapper.find('dialog').element.open).toBe(true);

        await wrapper.setProps({ modelValue: false });
        expect(wrapper.find('dialog').element.open).toBe(false);

        wrapper.unmount();
    });

    it('menampilkan seluruh entri terkelompok saat query kosong', () => {
        const wrapper = mountPalette();

        expect(optionLabels(wrapper)).toEqual(['Dashboard', 'Pengguna', 'RBAC', 'Pengaturan']);
        expect(wrapper.text()).toContain('Utama');
        expect(wrapper.text()).toContain('Administrasi');
        expect(wrapper.text()).toContain('Aksi');

        wrapper.unmount();
    });

    it('menyorot hasil pertama secara default', () => {
        const wrapper = mountPalette();

        expect(selectedLabel(wrapper)).toBe('Dashboard');

        wrapper.unmount();
    });

    it('menyaring hasil saat pengguna mengetik dan menyembunyikan grup kosong', async () => {
        const wrapper = mountPalette();

        await input(wrapper).setValue('penggun');

        expect(optionLabels(wrapper)).toEqual(['Pengguna']);
        expect(wrapper.text()).toContain('Administrasi');
        expect(wrapper.text()).not.toContain('Utama');
        expect(wrapper.text()).not.toContain('Aksi');

        wrapper.unmount();
    });

    it('menampilkan status kosong beserta query yang dicari', async () => {
        const wrapper = mountPalette();

        await input(wrapper).setValue('zzz');

        expect(wrapper.findAll('[role="option"]')).toHaveLength(0);
        expect(wrapper.find('[role="status"]').text()).toBe('Tidak ada hasil untuk "zzz"');

        wrapper.unmount();
    });

    it('memindah sorotan dengan panah atas dan bawah', async () => {
        const wrapper = mountPalette();
        const dialog = wrapper.find('dialog');

        await dialog.trigger('keydown', { key: 'ArrowDown' });
        expect(selectedLabel(wrapper)).toBe('Pengguna');

        await dialog.trigger('keydown', { key: 'ArrowDown' });
        await dialog.trigger('keydown', { key: 'ArrowDown' });
        expect(selectedLabel(wrapper)).toBe('Pengaturan');

        // Dari hasil terakhir, panah bawah kembali ke atas.
        await dialog.trigger('keydown', { key: 'ArrowDown' });
        expect(selectedLabel(wrapper)).toBe('Dashboard');

        await dialog.trigger('keydown', { key: 'ArrowUp' });
        expect(selectedLabel(wrapper)).toBe('Pengaturan');

        wrapper.unmount();
    });

    it('melompat ke awal dan akhir hasil dengan Home dan End', async () => {
        const wrapper = mountPalette();
        const dialog = wrapper.find('dialog');

        await dialog.trigger('keydown', { key: 'End' });
        expect(selectedLabel(wrapper)).toBe('Pengaturan');

        await dialog.trigger('keydown', { key: 'Home' });
        expect(selectedLabel(wrapper)).toBe('Dashboard');

        wrapper.unmount();
    });

    it('menghubungkan aria-activedescendant ke opsi aktif', async () => {
        const wrapper = mountPalette();
        const dialog = wrapper.find('dialog');

        const firstId = input(wrapper).attributes('aria-activedescendant');
        expect(firstId).toBe(wrapper.findAll('[role="option"]')[0].attributes('id'));

        await dialog.trigger('keydown', { key: 'ArrowDown' });

        expect(input(wrapper).attributes('aria-activedescendant')).toBe(
            wrapper.findAll('[role="option"]')[1].attributes('id'),
        );

        wrapper.unmount();
    });

    it('memilih entri dengan Enter lalu menutup dialog', async () => {
        const wrapper = mountPalette();
        const dialog = wrapper.find('dialog');

        await dialog.trigger('keydown', { key: 'ArrowDown' });
        await dialog.trigger('keydown', { key: 'Enter' });

        expect(wrapper.emitted('select')).toHaveLength(1);
        expect(wrapper.emitted('select')[0][0].key).toBe('users');
        expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);

        wrapper.unmount();
    });

    it('memilih entri lewat klik dan hover memindah sorotan', async () => {
        const wrapper = mountPalette();
        const options = wrapper.findAll('[role="option"]');

        await options[2].trigger('mousemove');
        expect(selectedLabel(wrapper)).toBe('RBAC');

        await options[2].trigger('click');

        expect(wrapper.emitted('select')[0][0].key).toBe('rbac');

        wrapper.unmount();
    });

    it('menutup lewat Escape', async () => {
        const wrapper = mountPalette();

        await wrapper.find('dialog').trigger('keydown', { key: 'Escape' });

        expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);

        wrapper.unmount();
    });

    it('menutup saat backdrop diklik, tetapi tidak saat isi diklik', async () => {
        const wrapper = mountPalette();

        await wrapper.find('.modal-box').trigger('click');
        expect(wrapper.emitted('update:modelValue')).toBeUndefined();

        await wrapper.find('dialog').trigger('click');
        expect(wrapper.emitted('update:modelValue')).toEqual([[false]]);

        wrapper.unmount();
    });

    it('mereset query saat dibuka kembali', async () => {
        const wrapper = mountPalette();

        await input(wrapper).setValue('peng');
        await wrapper.setProps({ modelValue: false });
        await wrapper.setProps({ modelValue: true });

        expect(input(wrapper).element.value).toBe('');
        expect(optionLabels(wrapper)).toEqual(['Dashboard', 'Pengguna', 'RBAC', 'Pengaturan']);

        wrapper.unmount();
    });

    it('memakai terjemahan dari shared props', async () => {
        setPage({
            props: {
                translations: {
                    'Cari menu': 'Search menu',
                    'Cari menu atau aksi…': 'Search menu or action…',
                    'Hasil pencarian': 'Search results',
                    Navigasi: 'Navigate',
                    Pilih: 'Select',
                },
            },
        });

        const wrapper = mountPalette();

        expect(input(wrapper).attributes('aria-label')).toBe('Search menu');
        expect(input(wrapper).attributes('placeholder')).toBe('Search menu or action…');
        expect(wrapper.text()).toContain('Navigate');
        expect(wrapper.text()).toContain('Select');

        wrapper.unmount();
    });
});
