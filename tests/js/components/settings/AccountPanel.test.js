import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import AccountPanel from '@/components/settings/AccountPanel.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const account = {
    name: 'Siti Aminah',
    username: 'siti',
    email: 'siti@example.test',
};

const mountPanel = (props = {}) => mount(AccountPanel, { props: { account, ...props } });

describe('komponen settings/AccountPanel', () => {
    beforeEach(() => {
        useForm.mockClear();
    });

    it('mengisi form dengan data akun saat ini', () => {
        const wrapper = mountPanel();
        const inputs = wrapper.findAll('input');

        expect(inputs[0].element.value).toBe('Siti Aminah');
        expect(inputs[1].element.value).toBe('siti');
        expect(inputs[2].element.value).toBe('siti@example.test');
    });

    it('menyediakan field password untuk konfirmasi perubahan akun', () => {
        const wrapper = mountPanel();
        const password = wrapper.find('input[type="password"]');

        expect(password.exists()).toBe(true);
        expect(password.element.value).toBe('');
        expect(password.attributes('autocomplete')).toBe('current-password');
    });

    it('mengirim perubahan data akun lewat PUT', async () => {
        const wrapper = mountPanel();
        const form = useForm.mock.results[0].value;

        form.name = 'Siti Aminah Baru';
        await wrapper.find('form').trigger('submit');

        expect(form.put).toHaveBeenCalledWith(
            '/settings/account',
            expect.objectContaining({ preserveScroll: true }),
        );
    });
});
