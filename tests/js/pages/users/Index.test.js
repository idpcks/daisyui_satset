import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { router, setPage } from '@tests/helpers/inertia.js';
import { useToast } from '@/lib/useToast';
import UsersIndex from '@/pages/users/Index.vue';
import UserForm from '@/components/users/UserForm.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const makeUser = (overrides = {}) => ({
    id: 1,
    name: 'Siti Aminah',
    username: 'siti',
    email: 'siti@example.test',
    role: 'admin',
    is_active: true,
    ...overrides,
});

const paginator = (data, overrides = {}) => ({
    data,
    links: [],
    last_page: 1,
    ...overrides,
});

const loginAs = (user = { id: 99, name: 'Admin' }, permissions = ['user.manage']) =>
    setPage({
        props: {
            auth: { user, roles: ['superadmin'], permissions },
        },
    });

const wrappers = [];

const mountPage = (props = {}) => {
    const wrapper = mount(UsersIndex, {
        props: {
            users: paginator([makeUser()]),
            filters: {},
            roles: ['superadmin', 'admin', 'operator'],
            ...props,
        },
    });

    wrappers.push(wrapper);

    return wrapper;
};

const dialogByTitle = (wrapper, title) =>
    wrapper.findAll('dialog').find((dialog) => dialog.find('h2').text() === title);

const deleteButtonFor = (wrapper, username) => {
    const row = wrapper.findAll('tbody tr').find((tr) => tr.text().includes(username));

    return row.find('button[aria-label="Hapus pengguna"]');
};

describe('halaman users/Index', () => {
    const { toasts } = useToast();

    beforeEach(() => {
        vi.useFakeTimers();
        toasts.value = [];
    });

    afterEach(() => {
        wrappers.forEach((wrapper) => wrapper.unmount());
        wrappers.length = 0;
        toasts.value = [];
        vi.clearAllTimers();
        vi.useRealTimers();
    });

    it('menampilkan daftar pengguna dengan label peran dan status', () => {
        loginAs();

        const wrapper = mountPage({
            users: paginator([
                makeUser(),
                makeUser({ id: 2, name: 'Budi', username: 'budi', role: 'operator', is_active: false }),
            ]),
        });

        expect(wrapper.text()).toContain('Siti Aminah');
        expect(wrapper.text()).toContain('siti@example.test');
        expect(wrapper.text()).toContain('Admin');
        expect(wrapper.text()).toContain('Aktif');
        expect(wrapper.text()).toContain('Operator');
        expect(wrapper.text()).toContain('Nonaktif');
    });

    it('memakai nilai cadangan untuk peran kosong', () => {
        loginAs();

        const wrapper = mountPage({ users: paginator([makeUser({ role: '' })]) });

        expect(wrapper.text()).toContain('-');
    });

    it('menampilkan pesan saat belum ada pengguna', () => {
        loginAs();

        const wrapper = mountPage({ users: paginator([]) });

        expect(wrapper.text()).toContain('Belum ada pengguna.');
    });

    it('menyembunyikan tombol hapus untuk akun sendiri', () => {
        loginAs({ id: 1, name: 'Siti Aminah' });

        const wrapper = mountPage({
            users: paginator([makeUser(), makeUser({ id: 2, name: 'Budi', username: 'budi' })]),
        });

        expect(deleteButtonFor(wrapper, 'siti').exists()).toBe(false);
        expect(deleteButtonFor(wrapper, 'budi').exists()).toBe(true);
    });

    it('menyembunyikan tombol tambah tanpa izin user.manage', () => {
        loginAs({ id: 99, name: 'Operator' }, []);

        const wrapper = mountPage();

        expect(wrapper.findAll('button').find((button) => button.text().includes('Tambah Pengguna'))).toBeUndefined();
    });

    it('mengirim filter setelah jeda 300 ms', async () => {
        loginAs();

        const wrapper = mountPage();

        await wrapper.find('#user-search').setValue('siti');

        await vi.advanceTimersByTimeAsync(299);
        expect(router.get).not.toHaveBeenCalled();

        await vi.advanceTimersByTimeAsync(1);
        expect(router.get).toHaveBeenCalledWith('/users', { search: 'siti' }, { preserveState: true, replace: true });

        await wrapper.find('#user-status').setValue('inactive');
        await vi.advanceTimersByTimeAsync(300);

        expect(router.get).toHaveBeenLastCalledWith(
            '/users',
            { search: 'siti', status: 'inactive' },
            { preserveState: true, replace: true },
        );

        await wrapper.find('#user-search').setValue('');
        await wrapper.find('#user-status').setValue('');
        await vi.advanceTimersByTimeAsync(300);

        expect(router.get).toHaveBeenLastCalledWith('/users', {}, { preserveState: true, replace: true });
    });

    it('memakai nilai filter awal dari server', async () => {
        loginAs();

        const wrapper = mountPage({ filters: { search: 'budi', status: 'active' } });

        expect(wrapper.find('#user-search').element.value).toBe('budi');
        expect(wrapper.find('#user-status').element.value).toBe('active');

        await wrapper.find('#user-search').setValue('budi santoso');
        await vi.advanceTimersByTimeAsync(300);

        expect(router.get).toHaveBeenCalledWith(
            '/users',
            { search: 'budi santoso', status: 'active' },
            { preserveState: true, replace: true },
        );
    });

    it('membuka form tambah dan ubah pengguna', async () => {
        loginAs();

        const wrapper = mountPage();

        await wrapper.findAll('button').find((button) => button.text().includes('Tambah Pengguna')).trigger('click');

        const tambah = dialogByTitle(wrapper, 'Tambah Pengguna');

        expect(tambah.element.open).toBe(true);
        expect(tambah.findComponent(UserForm).exists()).toBe(true);

        await wrapper.find('button[aria-label="Ubah pengguna"]').trigger('click');

        const ubah = dialogByTitle(wrapper, 'Ubah Pengguna');

        expect(ubah.element.open).toBe(true);
        expect(ubah.findComponent(UserForm).props('user')).toEqual(makeUser());
    });

    it('menghapus pengguna setelah konfirmasi', async () => {
        loginAs();

        const wrapper = mountPage({
            users: paginator([makeUser(), makeUser({ id: 2, name: 'Budi', username: 'budi' })]),
        });

        await deleteButtonFor(wrapper, 'budi').trigger('click');

        const konfirmasi = dialogByTitle(wrapper, 'Hapus Pengguna');

        expect(konfirmasi.element.open).toBe(true);
        expect(konfirmasi.text()).toContain('Budi');
        expect(konfirmasi.text()).toContain('budi');

        const hapus = konfirmasi.findAll('button').find((button) => button.text() === 'Hapus');

        await hapus.trigger('click');

        expect(router.delete).toHaveBeenCalledWith('/users/2', expect.objectContaining({ preserveScroll: true }));

        const options = router.delete.mock.calls[0][1];

        options.onSuccess();

        await wrapper.vm.$nextTick();

        expect(dialogByTitle(wrapper, 'Hapus Pengguna').element.open).toBe(false);
    });

    it('menampilkan toast saat penghapusan gagal', async () => {
        loginAs();

        const wrapper = mountPage({
            users: paginator([makeUser(), makeUser({ id: 2, name: 'Budi', username: 'budi' })]),
        });

        await deleteButtonFor(wrapper, 'budi').trigger('click');

        const hapus = dialogByTitle(wrapper, 'Hapus Pengguna')
            .findAll('button')
            .find((button) => button.text() === 'Hapus');

        await hapus.trigger('click');

        const options = router.delete.mock.calls[0][1];

        options.onError();
        options.onFinish();

        expect(toasts.value.at(-1)).toMatchObject({
            type: 'error',
            message: 'Pengguna gagal dihapus. Silakan coba lagi.',
        });
    });

    it('menutup konfirmasi hapus lewat tombol batal', async () => {
        loginAs();

        const wrapper = mountPage({
            users: paginator([makeUser(), makeUser({ id: 2, name: 'Budi', username: 'budi' })]),
        });

        await deleteButtonFor(wrapper, 'budi').trigger('click');

        const konfirmasi = dialogByTitle(wrapper, 'Hapus Pengguna');
        const batal = konfirmasi.findAll('button').find((button) => button.text() === 'Batal');

        await batal.trigger('click');

        expect(dialogByTitle(wrapper, 'Hapus Pengguna').element.open).toBe(false);
    });

    it('menampilkan tautan paginasi saat lebih dari satu halaman', () => {
        loginAs();

        const wrapper = mountPage({
            users: paginator([makeUser()], {
                last_page: 2,
                links: [
                    { label: '&laquo; Previous', url: null, active: false },
                    { label: '1', url: '/users?page=1', active: true },
                    { label: '2', url: '/users?page=2', active: false },
                ],
            }),
        });

        const join = wrapper.find('.join');

        expect(join.exists()).toBe(true);
        expect(join.findAll('a')).toHaveLength(2);
        expect(join.find('.btn-active').text()).toBe('1');
        expect(join.findAll('span.btn-disabled')).toHaveLength(1);
    });
});
