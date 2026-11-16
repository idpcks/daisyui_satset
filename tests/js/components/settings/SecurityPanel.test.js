import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import SecurityPanel from '@/components/settings/SecurityPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

// Stub modal supaya isi form tetap ter-render tanpa bergantung pada <dialog>.
const ModalStub = {
    name: 'Modal',
    props: ['modelValue', 'title'],
    template: '<div><slot /><slot name="footer" /></div>',
};

const mountPage = (props = {}) =>
    mount(SecurityPanel, {
        props: {
            twoFactorEnabled: false,
            twoFactorPending: false,
            qrCodeSvg: null,
            secretKey: null,
            recoveryCodes: [],
            ...props,
        },
        global: { stubs: { Modal: ModalStub } },
    });

const findButton = (wrapper, label) => wrapper.findAll('button').find((button) => button.text().includes(label));

const writeText = vi.fn(() => Promise.resolve());

describe('komponen settings/SecurityPanel', () => {
    beforeEach(() => {
        useForm.mockClear();
        writeText.mockClear();

        Object.defineProperty(navigator, 'clipboard', {
            value: { writeText },
            configurable: true,
        });
    });

    it('menawarkan aktivasi 2FA selama masih nonaktif', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Nonaktif');
        expect(findButton(wrapper, 'Aktifkan 2FA')).toBeTruthy();
    });

    it('mengirim password saat mengaktifkan 2FA', async () => {
        const wrapper = mountPage();
        const enableForm = useForm.mock.results[0].value;

        await findButton(wrapper, 'Aktifkan 2FA').trigger('click');

        enableForm.password = 'rahasia';
        await wrapper.find('#enable-2fa-form').trigger('submit');

        expect(enableForm.post).toHaveBeenCalledWith(
            '/settings/security/two-factor',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('menampilkan QR code dan memverifikasi kode saat menunggu konfirmasi', async () => {
        const wrapper = mountPage({
            twoFactorPending: true,
            qrCodeSvg: '<svg><rect /></svg>',
            secretKey: 'ABCDEFGHIJKLMNOP',
        });

        expect(wrapper.text()).toContain('Menunggu konfirmasi');
        expect(wrapper.html()).toContain('<svg>');
        expect(wrapper.text()).toContain('ABCDEFGHIJKLMNOP');

        const confirmForm = useForm.mock.results.at(-1).value;
        confirmForm.code = '123456';

        await wrapper.find('#confirm-2fa-form').trigger('submit');

        expect(confirmForm.post).toHaveBeenCalledWith(
            '/settings/security/two-factor/confirm',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('menyalin kunci manual lewat clipboard', async () => {
        const wrapper = mountPage({
            twoFactorPending: true,
            qrCodeSvg: '<svg><rect /></svg>',
            secretKey: 'ABCDEFGHIJKLMNOP',
        });

        await findButton(wrapper, 'Salin').trigger('click');
        await flushPromises();

        expect(writeText).toHaveBeenCalledWith('ABCDEFGHIJKLMNOP');
        expect(wrapper.text()).toContain('Tersalin');
    });

    it('menyembunyikan kode pemulihan sampai diminta', async () => {
        const codes = ['aaaa-bbbb', 'cccc-dddd'];
        const wrapper = mountPage({ twoFactorEnabled: true, recoveryCodes: codes });

        expect(wrapper.text()).toContain('Kode pemulihan disembunyikan.');
        expect(wrapper.text()).not.toContain(codes[0]);

        await findButton(wrapper, 'Tampilkan').trigger('click');

        expect(wrapper.text()).toContain(codes[0]);
        expect(wrapper.text()).toContain(codes[1]);
    });

    it('menyalin satu kode pemulihan lewat clipboard', async () => {
        const wrapper = mountPage({ twoFactorEnabled: true, recoveryCodes: ['aaaa-bbbb', 'cccc-dddd'] });

        await findButton(wrapper, 'Tampilkan').trigger('click');
        await wrapper.find('button[aria-label="Salin kode pemulihan cccc-dddd"]').trigger('click');
        await flushPromises();

        expect(writeText).toHaveBeenCalledWith('cccc-dddd');
    });

    it('meminta password sebelum membuat ulang kode pemulihan', async () => {
        const wrapper = mountPage({ twoFactorEnabled: true, recoveryCodes: ['aaaa-bbbb'] });
        const regenerateForm = useForm.mock.results[2].value;

        await findButton(wrapper, 'Buat Ulang').trigger('click');

        expect(wrapper.find('#regenerate-recovery-form').exists()).toBe(true);

        regenerateForm.password = 'rahasia';
        await wrapper.find('#regenerate-recovery-form').trigger('submit');

        expect(regenerateForm.post).toHaveBeenCalledWith(
            '/settings/security/recovery-codes',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
