import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetPage, router, setPage } from '@tests/helpers/inertia.js';
import Index from '@/pages/settings/Index.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const ModalStub = {
    name: 'Modal',
    props: ['modelValue', 'title'],
    template: '<div><slot /><slot name="footer" /></div>',
};

const mountPage = (props = {}) =>
    mount(Index, {
        props: {
            tab: 'akun',
            account: { name: 'Siti Aminah', username: 'siti', email: 'siti@example.test' },
            twoFactorEnabled: false,
            twoFactorPending: false,
            qrCodeSvg: null,
            secretKey: null,
            recoveryCodes: [],
            loginHistory: [],
            ...props,
        },
        global: { stubs: { Modal: ModalStub } },
    });

const findTab = (wrapper, label) => wrapper.findAll('[role="tab"]').find((tab) => tab.text().includes(label));

describe('halaman settings/Index', () => {
    beforeEach(() => {
        resetPage();
        router.get.mockClear();
    });

    it('menampilkan tab Akun, Keamanan, dan Riwayat Login', () => {
        const wrapper = mountPage();

        expect(findTab(wrapper, 'Akun')).toBeTruthy();
        expect(findTab(wrapper, 'Keamanan')).toBeTruthy();
        expect(findTab(wrapper, 'Riwayat Login')).toBeTruthy();
    });

    it('menampilkan tab Akun secara default', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Data Akun');
        expect(wrapper.text()).toContain('Ubah Password');
        expect(wrapper.text()).not.toContain('Autentikasi Dua Faktor (2FA)');
        expect(findTab(wrapper, 'Akun').classes()).toContain('tab-active');
    });

    it('menampilkan panel Keamanan saat tab Keamanan dipilih', async () => {
        const wrapper = mountPage();

        await findTab(wrapper, 'Keamanan').trigger('click');

        expect(wrapper.text()).toContain('Autentikasi Dua Faktor (2FA)');
        expect(wrapper.text()).not.toContain('Data Akun');
        expect(wrapper.text()).not.toContain('Ubah Password');
        expect(findTab(wrapper, 'Keamanan').classes()).toContain('tab-active');
    });

    it('menyimpan tab aktif di query string', async () => {
        const wrapper = mountPage();

        await findTab(wrapper, 'Keamanan').trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            '/settings',
            { tab: 'keamanan' },
            expect.objectContaining({ preserveScroll: true, preserveState: true, replace: true }),
        );
    });

    it('membuka tab Keamanan langsung dari prop tab', () => {
        const wrapper = mountPage({ tab: 'keamanan' });

        expect(wrapper.text()).toContain('Autentikasi Dua Faktor (2FA)');
        expect(wrapper.text()).toContain('Sesi & Perangkat');
        expect(findTab(wrapper, 'Keamanan').classes()).toContain('tab-active');
    });

    it('menampilkan panel passkey saat flag aktif', () => {
        setPage({ props: { features: { passkeys: true } } });

        const wrapper = mountPage({ tab: 'keamanan' });

        expect(wrapper.text()).toContain('Tambah Passkey');
    });

    it('menyembunyikan panel passkey saat flag nonaktif', () => {
        setPage({ props: { features: { passkeys: false } } });

        const wrapper = mountPage({ tab: 'keamanan' });

        expect(wrapper.text()).not.toContain('Tambah Passkey');
        expect(wrapper.text()).toContain('Autentikasi Dua Faktor (2FA)');
    });

    it('menampilkan panel Riwayat Login saat tab dipilih', async () => {
        const wrapper = mountPage();

        await findTab(wrapper, 'Riwayat Login').trigger('click');

        expect(wrapper.text()).toContain('Belum ada riwayat login.');
        expect(wrapper.text()).not.toContain('Data Akun');
        expect(wrapper.text()).not.toContain('Autentikasi Dua Faktor (2FA)');
        expect(findTab(wrapper, 'Riwayat Login').classes()).toContain('tab-active');
    });

    it('menyimpan tab Riwayat Login di query string', async () => {
        const wrapper = mountPage();

        await findTab(wrapper, 'Riwayat Login').trigger('click');

        expect(router.get).toHaveBeenCalledWith(
            '/settings',
            { tab: 'riwayat' },
            expect.objectContaining({ preserveScroll: true, preserveState: true, replace: true }),
        );
    });
});
