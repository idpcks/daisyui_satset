import { mount } from '@vue/test-utils';
import { describe, expect, it, vi } from 'vitest';
import { useForm } from '@tests/helpers/inertia.js';
import UserForm from '@/components/users/UserForm.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const roles = ['superadmin', 'admin', 'operator'];

const mountForm = (props = {}) => mount(UserForm, { props: { user: null, roles, ...props } });

const lastForm = () => useForm.mock.results.at(-1).value;

const labelKonfirmasi = (wrapper) =>
    wrapper.findAll('label').find((label) => label.text().includes('Konfirmasi password'));

describe('UserForm', () => {
    it('memakai peran pertama saat menambah pengguna', () => {
        const wrapper = mountForm();

        expect(lastForm().role).toBe('superadmin');
        expect(lastForm().is_active).toBe(true);

        const opsi = wrapper.findAll('option').map((option) => option.text());

        expect(opsi).toEqual(['Super Admin', 'Admin', 'Operator']);
    });

    it('mengisi form dari pengguna yang diubah dan menerjemahkan peran', () => {
        mountForm({
            user: {
                id: 4,
                name: 'Siti',
                username: 'siti',
                email: 'siti@example.test',
                role: 'admin',
                is_active: false,
            },
        });
        const form = lastForm();

        expect(form.name).toBe('Siti');
        expect(form.role).toBe('admin');
        expect(form.is_active).toBe(false);
    });

    it('tidak menampilkan input password', () => {
        const wrapper = mountForm();

        expect(wrapper.text()).not.toContain('Password');
        expect(labelKonfirmasi(wrapper)).toBeUndefined();
    });

    it('mengirim POST saat menambah dan PUT saat mengubah', async () => {
        const tambah = mountForm();

        await tambah.find('form').trigger('submit');

        expect(lastForm().post).toHaveBeenCalledWith(
            '/users',
            expect.objectContaining({ preserveScroll: true }),
        );

        lastForm().post.mock.calls[0][1].onSuccess();
        expect(tambah.emitted('success')).toHaveLength(1);

        const ubah = mountForm({
            user: {
                id: 9,
                name: 'Budi',
                username: 'budi',
                email: 'budi@example.test',
                role: 'operator',
            },
        });

        await ubah.find('form').trigger('submit');

        expect(lastForm().put).toHaveBeenCalledWith(
            '/users/9',
            expect.objectContaining({ preserveScroll: true }),
        );
    });

    it('meneruskan batal', async () => {
        const wrapper = mountForm();

        const tombolBatal = wrapper.findAll('button').find((button) => button.text() === 'Batal');
        await tombolBatal.trigger('click');

        expect(wrapper.emitted('cancel')).toHaveLength(1);
    });
});
