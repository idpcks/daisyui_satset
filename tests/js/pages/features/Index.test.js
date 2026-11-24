import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetRouter, router } from '@tests/helpers/inertia.js';
import FeaturesIndex from '@/pages/features/Index.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const flags = [
    {
        name: 'passkeys-login',
        label: 'Login Passkey',
        description: 'Login dan pengelolaan passkey.',
        active: true,
    },
    {
        name: 'magic-link-login',
        label: 'Login Tautan Email',
        description: 'Login lewat tautan yang dikirim ke email.',
        active: false,
    },
];

const ModalStub = {
    name: 'Modal',
    props: ['modelValue', 'title'],
    template: '<div><slot /><slot name="footer" /></div>',
};

const mountPage = () =>
    mount(FeaturesIndex, {
        props: { flags },
        global: { stubs: { Modal: ModalStub } },
    });

describe('halaman features/Index', () => {
    beforeEach(() => {
        resetRouter();
    });

    it('menampilkan nama, status, dan deskripsi tiap flag', () => {
        const wrapper = mountPage();

        expect(wrapper.text()).toContain('Login Passkey');
        expect(wrapper.text()).toContain('Login Tautan Email');
        expect(wrapper.text()).toContain('passkeys-login');
        expect(wrapper.findAll('.badge').map((badge) => badge.text())).toEqual([
            'Aktif',
            'Nonaktif',
        ]);
    });

    it('mengaktifkan flag yang nonaktif tanpa konfirmasi', async () => {
        const wrapper = mountPage();

        await wrapper.get('button[aria-label="Aktifkan Login Tautan Email"]').trigger('click');

        expect(router.patch).toHaveBeenCalledWith(
            '/features/magic-link-login',
            { active: true },
            expect.any(Object),
        );
    });

    it('meminta konfirmasi sebelum menonaktifkan flag', async () => {
        const wrapper = mountPage();

        await wrapper.get('button[aria-label="Nonaktifkan Login Passkey"]').trigger('click');

        expect(router.patch).not.toHaveBeenCalled();
        expect(wrapper.text()).toContain('Yakin ingin menonaktifkan');

        await wrapper.get('[data-testid="confirm-disable"]').trigger('click');

        expect(router.patch).toHaveBeenCalledWith(
            '/features/passkeys-login',
            { active: false },
            expect.any(Object),
        );
    });
});
