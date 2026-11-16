import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router } from '@tests/helpers/inertia.js';
import { RequestError, requestJson } from '@/lib/http';
import { createPasskeyCredential } from '@/lib/webauthn';
import PasskeysPanel from '@/components/settings/PasskeysPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

vi.mock('@/lib/http', async (importOriginal) => {
    const actual = await importOriginal();

    return { ...actual, requestJson: vi.fn() };
});

vi.mock('@/lib/webauthn', async (importOriginal) => {
    const actual = await importOriginal();

    return {
        ...actual,
        isPasskeySupported: vi.fn(() => true),
        createPasskeyCredential: vi.fn(),
    };
});

const ModalStub = {
    name: 'Modal',
    props: ['modelValue', 'title'],
    template: '<div><slot /><slot name="footer" /></div>',
};

const passkeys = [
    {
        id: 'pk-1',
        name: 'Laptop Kantor',
        authenticator: 'Windows Hello',
        created_at: '2026-11-16T08:00:00+00:00',
        last_used_at: null,
    },
    {
        id: 'pk-2',
        name: 'iPhone',
        authenticator: null,
        created_at: '2026-11-15T08:00:00+00:00',
        last_used_at: '2026-11-16T07:30:00+00:00',
    },
];

const mountPanel = (panelPasskeys = passkeys) =>
    mount(PasskeysPanel, {
        props: { passkeys: panelPasskeys },
        global: { stubs: { Modal: ModalStub } },
    });

const findButton = (wrapper, label) =>
    wrapper.findAll('button').find((button) => button.text().trim() === label);

describe('komponen settings/PasskeysPanel', () => {
    beforeEach(() => {
        requestJson.mockReset();
        createPasskeyCredential.mockReset();
        router.reload.mockClear();
    });

    it('menampilkan daftar passkey milik pengguna', () => {
        const wrapper = mountPanel();

        expect(wrapper.text()).toContain('Passkey');
        expect(wrapper.text()).toContain('Laptop Kantor');
        expect(wrapper.text()).toContain('Windows Hello');
        expect(wrapper.text()).toContain('Belum pernah');
        expect(wrapper.text()).toContain('iPhone');
    });

    it('menampilkan empty state ketika belum ada passkey', () => {
        const wrapper = mountPanel([]);

        expect(wrapper.text()).toContain('Belum ada passkey terdaftar.');
    });

    it('mendaftarkan passkey baru setelah konfirmasi password', async () => {
        requestJson
            .mockResolvedValueOnce(null)
            .mockResolvedValueOnce({ options: { challenge: 'AQID', user: { id: 'BAU' } } })
            .mockResolvedValueOnce({ status: 'passkey-registered', id: 'pk-3' });
        createPasskeyCredential.mockResolvedValue({ id: 'credential-id', type: 'public-key' });

        const wrapper = mountPanel();

        await findButton(wrapper, 'Tambah Passkey').trigger('click');
        await wrapper.find('#passkey-name').setValue('MacBook');
        await wrapper.find('#passkey-register-password').setValue('rahasia');
        await wrapper.find('#passkey-register-form').trigger('submit');
        await flushPromises();

        expect(requestJson).toHaveBeenNthCalledWith(1, '/user/confirm-password', {
            method: 'POST',
            body: { password: 'rahasia' },
        });
        expect(requestJson).toHaveBeenNthCalledWith(2, '/user/passkeys/options');
        expect(createPasskeyCredential).toHaveBeenCalledWith({ challenge: 'AQID', user: { id: 'BAU' } });
        expect(requestJson).toHaveBeenNthCalledWith(3, '/user/passkeys', {
            method: 'POST',
            body: { name: 'MacBook', credential: { id: 'credential-id', type: 'public-key' } },
        });
        expect(router.reload).toHaveBeenCalledWith({ only: ['passkeys'] });
    });

    it('menghapus passkey setelah konfirmasi password', async () => {
        requestJson.mockResolvedValue(null);

        const wrapper = mountPanel();

        await findButton(wrapper, 'Hapus').trigger('click');
        await wrapper.find('#passkey-delete-password').setValue('rahasia');
        await wrapper.find('#passkey-delete-form').trigger('submit');
        await flushPromises();

        expect(requestJson).toHaveBeenNthCalledWith(1, '/user/confirm-password', {
            method: 'POST',
            body: { password: 'rahasia' },
        });
        expect(requestJson).toHaveBeenNthCalledWith(2, '/user/passkeys/pk-1', { method: 'DELETE' });
        expect(router.reload).toHaveBeenCalledWith({ only: ['passkeys'] });
    });

    it('menampilkan error validasi saat password salah', async () => {
        requestJson.mockRejectedValueOnce(
            new RequestError('Data yang diberikan tidak valid.', {
                status: 422,
                errors: { password: ['Password yang diberikan tidak cocok.'] },
            }),
        );

        const wrapper = mountPanel();

        await findButton(wrapper, 'Hapus').trigger('click');
        await wrapper.find('#passkey-delete-password').setValue('salah');
        await wrapper.find('#passkey-delete-form').trigger('submit');
        await flushPromises();

        expect(wrapper.text()).toContain('Password yang diberikan tidak cocok.');
        expect(router.reload).not.toHaveBeenCalled();
    });

    it('tidak menampilkan error saat pendaftaran dibatalkan pengguna', async () => {
        requestJson
            .mockResolvedValueOnce(null)
            .mockResolvedValueOnce({ options: { challenge: 'AQID', user: { id: 'BAU' } } });
        createPasskeyCredential.mockRejectedValue(
            Object.assign(new Error('The operation either timed out or was not allowed.'), {
                name: 'NotAllowedError',
            }),
        );

        const wrapper = mountPanel();

        await findButton(wrapper, 'Tambah Passkey').trigger('click');
        await wrapper.find('#passkey-name').setValue('MacBook');
        await wrapper.find('#passkey-register-password').setValue('rahasia');
        await wrapper.find('#passkey-register-form').trigger('submit');
        await flushPromises();

        expect(requestJson).toHaveBeenCalledTimes(2);
        expect(router.reload).not.toHaveBeenCalled();
    });
});
