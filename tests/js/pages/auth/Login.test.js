import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import Login from '@/pages/auth/Login.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const lastForm = () => useForm.mock.results.at(-1).value;

describe('halaman auth/Login', () => {
    it('mengirim kredensial lewat form.post dan mengosongkan password', async () => {
        const wrapper = mount(Login);
        const form = lastForm();

        form.email = 'siti@example.test';
        form.password = 'rahasia';
        form.remember = true;

        await wrapper.find('form').trigger('submit');

        expect(form.post).toHaveBeenCalledWith('/login', expect.objectContaining({ onFinish: expect.any(Function) }));

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
