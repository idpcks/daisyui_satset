import { flushPromises, mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { router, setPage, useForm } from '@tests/helpers/inertia.js';
import { requestJson } from '@/lib/http';
import { getPasskeyCredential, isPasskeySupported } from '@/lib/webauthn';
import Login from '@/pages/auth/Login.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

vi.mock('@/lib/http', async (importOriginal) => {
    const actual = await importOriginal();

    return { ...actual, requestJson: vi.fn() };
});

vi.mock('@/lib/webauthn', async (importOriginal) => {
    const actual = await importOriginal();

    return {
        ...actual,
        isPasskeySupported: vi.fn(() => false),
        getPasskeyCredential: vi.fn(),
    };
});

const lastForm = () => useForm.mock.results.at(-1).value;

describe('halaman auth/Login', () => {
    it('mengirim kredensial lewat form.post dan mengosongkan password', async () => {
        const wrapper = mount(Login);
        const form = lastForm();

        form.email = 'siti@example.test';
        form.password = 'rahasia';
        form.remember = true;

        await wrapper.find('form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith(
            '/auth/signin',
            expect.objectContaining({ onFinish: expect.any(Function) }),
        );

        form.post.mock.calls[0][1].onFinish();

        expect(form.reset).toHaveBeenCalledWith('password');
        expect(form.password).toBe('');
        expect(form.email).toBe('siti@example.test');
        expect(form.remember).toBe(true);
    });

    it('menampilkan pesan error dan menandai input', async () => {
        const wrapper = mount(Login);
        const form = lastForm();

        form.errors.email = 'Email atau password salah';
        form.errors.password = 'Password wajib diisi';

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Email atau password salah');
        expect(wrapper.text()).toContain('Password wajib diisi');
        expect(wrapper.find('input[type="email"]').classes()).toContain('input-error');
        expect(wrapper.find('input[type="password"]').classes()).toContain('input-error');
    });

    it('menonaktifkan tombol selama proses login', async () => {
        const wrapper = mount(Login);
        const form = lastForm();

        form.processing = true;

        await wrapper.vm.$nextTick();

        const tombol = wrapper.find('button[type="submit"]');

        expect(tombol.attributes('disabled')).toBeDefined();
        expect(tombol.find('.loading').exists()).toBe(true);
    });
});

describe('halaman auth/Login — passkey', () => {
    const findPasskeyButton = (wrapper) =>
        wrapper.findAll('button').find((button) => button.text().includes('Masuk dengan passkey'));

    beforeEach(() => {
        requestJson.mockReset();
        getPasskeyCredential.mockReset();
        isPasskeySupported.mockReturnValue(false);
        setPage({ props: { features: { passkeys: true } } });
    });

    it('menyembunyikan tombol saat fitur passkey tidak aktif', () => {
        isPasskeySupported.mockReturnValue(true);
        setPage({ props: { features: { passkeys: false } } });

        const wrapper = mount(Login);

        expect(findPasskeyButton(wrapper)).toBeUndefined();
    });

    it('menyembunyikan tombol saat browser tidak mendukung WebAuthn', () => {
        const wrapper = mount(Login);

        expect(findPasskeyButton(wrapper)).toBeUndefined();
    });

    it('menampilkan tombol saat fitur aktif dan browser mendukung', () => {
        isPasskeySupported.mockReturnValue(true);

        const wrapper = mount(Login);

        expect(findPasskeyButton(wrapper)).toBeTruthy();
    });

    it('masuk dengan passkey lalu mengunjungi URL redirect dari server', async () => {
        isPasskeySupported.mockReturnValue(true);
        requestJson
            .mockResolvedValueOnce({ options: { challenge: 'AQID' } })
            .mockResolvedValueOnce({ redirect: 'https://app.test/app' });
        getPasskeyCredential.mockResolvedValue({ id: 'credential-id', type: 'public-key' });

        const wrapper = mount(Login);
        const form = lastForm();
        form.remember = true;

        await findPasskeyButton(wrapper).trigger('click');
        await flushPromises();

        expect(requestJson).toHaveBeenNthCalledWith(1, '/passkeys/login/options');
        expect(getPasskeyCredential).toHaveBeenCalledWith({ challenge: 'AQID' });
        expect(requestJson).toHaveBeenNthCalledWith(2, '/passkeys/login', {
            method: 'POST',
            body: { credential: { id: 'credential-id', type: 'public-key' }, remember: true },
        });
        expect(router.visit).toHaveBeenCalledWith('https://app.test/app');
    });

    it('menampilkan pesan ramah saat pengguna membatalkan passkey', async () => {
        isPasskeySupported.mockReturnValue(true);
        requestJson.mockResolvedValueOnce({ options: { challenge: 'AQID' } });
        getPasskeyCredential.mockRejectedValue(
            Object.assign(new Error('The operation either timed out or was not allowed.'), {
                name: 'NotAllowedError',
            }),
        );

        const wrapper = mount(Login);

        await findPasskeyButton(wrapper).trigger('click');
        await flushPromises();

        expect(wrapper.text()).toContain('Masuk dengan passkey dibatalkan.');
        expect(router.visit).not.toHaveBeenCalled();
    });
});
