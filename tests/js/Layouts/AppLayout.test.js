import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { emitRouter, setPage } from '@tests/helpers/inertia.js';
import { useSidebar } from '@/lib/useSidebar';
import AppLayout from '@/Layouts/AppLayout.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const loginAs = (user = { id: 1, name: 'Siti Aminah', email: 'siti@example.test' }, permissions = []) =>
    setPage({
        props: {
            appName: 'Starterkit',
            auth: { user, roles: ['superadmin'], permissions },
        },
    });

const wrappers = [];

const mountLayout = () => {
    const wrapper = mount(AppLayout, {
        slots: { default: '<p>Isi halaman</p>' },
    });

    wrappers.push(wrapper);

    return wrapper;
};

describe('AppLayout', () => {
    beforeEach(() => {
        vi.useFakeTimers();
        document.documentElement.dataset.theme = 'light';
        useSidebar().collapsed.value = false;
    });

    afterEach(() => {
        wrappers.forEach((wrapper) => wrapper.unmount());
        wrappers.length = 0;
        vi.clearAllTimers();
        vi.useRealTimers();
    });

    it('menampilkan nama aplikasi, menu, dan data pengguna', () => {
        loginAs(undefined, ['user.manage', 'role.manage']);

        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('Starterkit');
        expect(wrapper.text()).toContain('Siti Aminah');
        expect(wrapper.text()).toContain('siti@example.test');
        expect(wrapper.text()).toContain('S');
        expect(wrapper.find('a[aria-label="Keluar"]').exists()).toBe(true);
        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.text()).toContain('Pengguna');
        expect(wrapper.text()).toContain('RBAC');
        // Pengaturan tidak lagi berupa menu bertulisan di sidebar.
        expect(wrapper.find('nav a[href="/settings"]').exists()).toBe(false);
        expect(wrapper.find('a[aria-label="Pengaturan"]').exists()).toBe(true);
    });

    it('menyaring menu sesuai izin pengguna', () => {
        loginAs(undefined, []);

        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('Utama');
        expect(wrapper.text()).toContain('Dashboard');
        expect(wrapper.find('a[href="/users"]').exists()).toBe(false);
        // Semua item grup Sistem butuh izin, jadi grupnya hilang untuk operator.
        expect(wrapper.text()).not.toContain('Sistem');
        // Ikon Pengaturan tetap tersedia karena setiap pengguna boleh membuka akunnya.
        expect(wrapper.find('a[aria-label="Pengaturan"]').exists()).toBe(true);
    });

    it('menyorot menu yang sedang aktif', async () => {
        loginAs(undefined, ['user.manage']);
        setPage({ url: '/settings' });

        const wrapper = mountLayout();
        const settingsLink = () => wrapper.find('a[aria-label="Pengaturan"]');

        expect(settingsLink().classes()).toContain('btn-active');

        setPage({ url: '/users?page=2' });
        await wrapper.vm.$nextTick();

        expect(settingsLink().classes()).not.toContain('btn-active');

        const usersLink = wrapper.findAll('a').find((link) => link.text().includes('Pengguna'));

        expect(usersLink.classes()).toContain('menu-active');

        setPage({ url: '/dashboard' });
        await wrapper.vm.$nextTick();

        const dashboardLink = wrapper.findAll('a').find((link) => link.text().includes('Dashboard'));

        expect(dashboardLink.classes()).toContain('menu-active');
    });

    it('memperkecil dan melebarkan sidebar', async () => {
        loginAs();

        const wrapper = mountLayout();

        const tombol = () => wrapper.find('button[aria-label="Kecilkan sidebar"], button[aria-label="Lebarkan sidebar"]');

        expect(tombol().attributes('aria-label')).toBe('Kecilkan sidebar');

        await tombol().trigger('click');

        expect(tombol().attributes('aria-label')).toBe('Lebarkan sidebar');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('1');

        await tombol().trigger('click');

        expect(tombol().attributes('aria-label')).toBe('Kecilkan sidebar');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('0');
    });

    it('mengganti tema lewat dropdown', async () => {
        loginAs();

        const wrapper = mountLayout();

        await wrapper.find('select[aria-label="Tema"]').setValue('cupcake');

        expect(document.documentElement.dataset.theme).toBe('cupcake');
        expect(localStorage.getItem('theme')).toBe('cupcake');
        expect(wrapper.find('select[aria-label="Tema"]').element.value).toBe('cupcake');
    });

    it('menampilkan avatar pengguna bila tersedia', () => {
        loginAs({ id: 2, name: 'Budi', email: 'budi@example.test', avatar: 'https://example.test/budi.png' });

        const wrapper = mountLayout();
        const avatar = wrapper.find('img');

        expect(avatar.attributes('src')).toBe('https://example.test/budi.png');
        expect(avatar.attributes('alt')).toBe('Budi');
    });

    it('menyembunyikan bagian pengguna saat belum login', () => {
        setPage({ props: { appName: 'Starterkit' } });

        const wrapper = mountLayout();

        expect(wrapper.find('a[aria-label="Keluar"]').exists()).toBe(false);
        expect(wrapper.text()).not.toContain('Siti');
    });

    it('menampilkan skeleton saat navigasi dimulai', async () => {
        loginAs();

        const wrapper = mountLayout();

        expect(wrapper.text()).toContain('Isi halaman');

        emitRouter('start', { detail: { visit: { method: 'get' } } });

        await vi.advanceTimersByTimeAsync(119);

        expect(wrapper.find('[aria-busy="true"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Isi halaman');

        await vi.advanceTimersByTimeAsync(1);

        expect(wrapper.find('[aria-busy="true"]').exists()).toBe(true);
        expect(wrapper.text()).not.toContain('Isi halaman');

        emitRouter('finish');

        await vi.advanceTimersByTimeAsync(0);

        expect(wrapper.find('[aria-busy="true"]').exists()).toBe(false);
        expect(wrapper.text()).toContain('Isi halaman');
    });

    it('tidak menampilkan skeleton untuk submit form, filter, dan partial reload', async () => {
        loginAs();

        const wrapper = mountLayout();

        emitRouter('start', { detail: { visit: { method: 'post' } } });
        emitRouter('start', { detail: { visit: { method: 'get', preserveState: true } } });
        emitRouter('start', { detail: { visit: { method: 'get', only: ['users'] } } });

        await vi.advanceTimersByTimeAsync(500);

        expect(wrapper.find('[aria-busy="true"]').exists()).toBe(false);
    });
});
