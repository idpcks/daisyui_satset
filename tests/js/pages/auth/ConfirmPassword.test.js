import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import ConfirmPassword from '@/pages/auth/ConfirmPassword.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const lastForm = () => useForm.mock.results.at(-1).value;

describe('halaman auth/ConfirmPassword', () => {
    beforeEach(() => useForm.mockClear());

    it('mengirim password lewat form.post ke endpoint Fortify', async () => {
        const wrapper = mount(ConfirmPassword);
        const form = lastForm();

        form.password = 'rahasia';

        await wrapper.find('form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith(
            '/user/confirm-password',
            expect.objectContaining({ onFinish: expect.any(Function) }),
        );
    });

    it('menampilkan pesan error saat password ditolak', async () => {
        const wrapper = mount(ConfirmPassword);
        const form = lastForm();

        form.errors.password = 'Password tidak cocok.';

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Password tidak cocok.');
        expect(wrapper.find('#confirm-password').classes()).toContain('input-error');
    });

    it('bisa menampilkan dan menyembunyikan password', async () => {
        const wrapper = mount(ConfirmPassword);

        expect(wrapper.find('#confirm-password').attributes('type')).toBe('password');

        const toggle = wrapper.find('button[aria-label="Tampilkan password"]');
        await toggle.trigger('click');

        expect(wrapper.find('#confirm-password').attributes('type')).toBe('text');
        expect(wrapper.find('button[aria-label="Sembunyikan password"]').exists()).toBe(true);
    });
});
