import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import TwoFactorChallenge from '@/pages/auth/TwoFactorChallenge.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const lastForm = () => useForm.mock.results.at(-1).value;

describe('halaman auth/TwoFactorChallenge', () => {
    beforeEach(() => useForm.mockClear());

    it('mengirim kode autentikator lewat form.post', async () => {
        const wrapper = mount(TwoFactorChallenge);
        const form = lastForm();

        form.code = '123456';

        await wrapper.find('form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith(
            '/two-factor-challenge',
            expect.objectContaining({ onFinish: expect.any(Function) }),
        );
    });

    it('beralih ke kode pemulihan dan mengirimkannya', async () => {
        const wrapper = mount(TwoFactorChallenge);
        const form = lastForm();

        expect(wrapper.find('#two-factor-recovery').exists()).toBe(false);

        const toggle = wrapper
            .findAll('button')
            .find((button) => button.text().includes('kode pemulihan'));

        await toggle.trigger('click');

        expect(wrapper.find('#two-factor-code').exists()).toBe(false);
        expect(wrapper.find('#two-factor-recovery').exists()).toBe(true);

        form.recovery_code = 'abcd-efgh';

        await wrapper.find('form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith(
            '/two-factor-challenge',
            expect.objectContaining({ onFinish: expect.any(Function) }),
        );
    });

    it('menampilkan pesan error saat kode ditolak', async () => {
        const wrapper = mount(TwoFactorChallenge);
        const form = lastForm();

        form.errors.code = 'Kode verifikasi tidak valid';

        await wrapper.vm.$nextTick();

        expect(wrapper.text()).toContain('Kode verifikasi tidak valid');
        expect(wrapper.find('#two-factor-code').classes()).toContain('input-error');
    });
});
