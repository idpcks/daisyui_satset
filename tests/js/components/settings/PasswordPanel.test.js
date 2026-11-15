import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import PasswordPanel from '@/components/settings/PasswordPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('komponen settings/PasswordPanel', () => {
    beforeEach(() => {
        useForm.mockClear();
    });

    it('menampilkan tiga kolom password yang kosong', () => {
        const wrapper = mount(PasswordPanel);
        const inputs = wrapper.findAll('input');

        expect(inputs).toHaveLength(3);
        inputs.forEach((input) => expect(input.element.value).toBe(''));
        expect(inputs[0].attributes('autocomplete')).toBe('current-password');
        expect(inputs[1].attributes('autocomplete')).toBe('new-password');
    });

    it('mengirim perubahan password lewat PUT', async () => {
        const wrapper = mount(PasswordPanel);
        const form = useForm.mock.results[0].value;

        form.current_password = 'password';
        form.password = 'password-baru';
        form.password_confirmation = 'password-baru';

        await wrapper.find('form').trigger('submit');

        expect(form.put).toHaveBeenCalledWith(
            '/settings/password',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('mengosongkan form setelah password berhasil diubah', async () => {
        const wrapper = mount(PasswordPanel);
        const form = useForm.mock.results[0].value;

        await wrapper.find('form').trigger('submit');
        form.put.mock.calls[0][1].onSuccess();

        expect(form.reset).toHaveBeenCalled();
    });
});
