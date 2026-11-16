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
        useSidebar().setState('expanded');
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

    it('mengelompokkan menu ke dalam parent dan submenu', () => {
        loginAs(undefined, ['user.manage', 'role.manage', 'feature.manage']);

        const wrapper = mountLayout();
        const groups = wrapper.findAll('nav details');

        expect(groups).toHaveLength(2);
        expect(groups[0].find('summary').text()).toContain('Administrasi');
        expect(groups[0].findAll('ul a').map((link) => link.text())).toEqual(['Pengguna', 'RBAC']);
        expect(groups[1].find('summary').text()).toContain('Sistem');
        expect(groups[1].findAll('ul a').map((link) => link.text())).toEqual(['Feature Flags']);
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
        const administrationGroup = wrapper.findAll('nav details')[0];

        expect(usersLink.classes()).toContain('menu-active');
        expect(administrationGroup.element.open).toBe(true);

        setPage({ url: '/dashboard' });
        await wrapper.vm.$nextTick();

        const dashboardLink = wrapper.findAll('a').find((link) => link.text().includes('Dashboard'));

        expect(dashboardLink.classes()).toContain('menu-active');
    });

    it('menampilkan dock mobile dari menu yang boleh diakses', () => {
        loginAs(undefined, ['user.manage', 'role.manage', 'feature.manage']);

        const wrapper = mountLayout();
        const dock = wrapper.find('nav[aria-label="Navigasi cepat"]');

        expect(dock.exists()).toBe(true);
        expect(dock.classes()).toContain('dock');
        expect(dock.classes()).toContain('lg:hidden');
        expect(dock.findAll('a').map((link) => link.text())).toEqual(['Dashboard', 'Pengguna', 'RBAC', 'Fitur']);
        expect(dock.findAll('a').map((link) => link.attributes('href'))).toEqual([
            '/dashboard',
            '/users',
            '/rbac',
            '/features',
        ]);

        // Landmark navigasi harus punya nama berbeda dari dock.
        expect(wrapper.find('nav[aria-label="Menu aplikasi"]').exists()).toBe(true);
        expect(wrapper.find('.drawer-content').classes()).toContain(
            'pb-[calc(3.5rem_+_env(safe-area-inset-bottom))]',
        );
    });

    it('menyaring dock sesuai permission dan menandai tujuan aktif', () => {
        loginAs(undefined, ['user.manage']);
        setPage({ url: '/users?page=2' });

        const wrapper = mountLayout();
        const dock = wrapper.find('nav[aria-label="Navigasi cepat"]');

        expect(dock.findAll('a').map((link) => link.text())).toEqual(['Dashboard', 'Pengguna']);

        const active = dock.find('a[aria-current="page"]');

        expect(active.text()).toContain('Pengguna');
        expect(active.classes()).toContain('text-primary');
    });

    it('menutup drawer mobile saat URL berubah', async () => {
        loginAs();

        const wrapper = mountLayout();
        const toggle = wrapper.find('#sidebar');

        await toggle.setValue(true);

        expect(toggle.element.checked).toBe(true);

        setPage({ url: '/users' });
        await wrapper.vm.$nextTick();

        expect(toggle.element.checked).toBe(false);
    });

    it('memperkecil dan melebarkan sidebar', async () => {
        loginAs();

        const wrapper = mountLayout();

        const tombol = () => wrapper.find('button[aria-label="Kecilkan sidebar"], button[aria-label="Lebarkan sidebar"]');

        expect(tombol().attributes('aria-label')).toBe('Kecilkan sidebar');

        await tombol().trigger('click');

        expect(tombol().attributes('aria-label')).toBe('Lebarkan sidebar');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('collapsed');

        await tombol().trigger('click');

        expect(tombol().attributes('aria-label')).toBe('Kecilkan sidebar');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('expanded');
    });

    it('menyembunyikan sidebar sepenuhnya dan menampilkannya kembali dari header', async () => {
        loginAs();

        const wrapper = mountLayout();
        const aside = () => wrapper.find('.drawer-side aside');
        const navbar = () => wrapper.find('.navbar');
        const tombolTampilkan = () => wrapper.find('button[aria-label="Tampilkan sidebar"]');

        expect(aside().classes()).toContain('w-64');
        expect(navbar().text()).not.toContain('Starterkit');

        await wrapper.find('button[aria-label="Sembunyikan sidebar"]').trigger('click');

        // Tidak ada rail ikon dan tidak ada lebar tersisa di layout desktop.
        expect(aside().classes()).toContain('lg:w-0');
        expect(aside().classes()).toContain('lg:overflow-hidden');
        expect(aside().classes()).not.toContain('lg:w-16');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('hidden');

        // Brand dan toggle tetap tersedia di header.
        expect(navbar().text()).toContain('Starterkit');
        expect(tombolTampilkan().exists()).toBe(true);

        await tombolTampilkan().trigger('click');

        expect(aside().classes()).not.toContain('lg:w-0');
        expect(aside().classes()).toContain('w-64');
        expect(navbar().text()).not.toContain('Starterkit');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('expanded');
    });

    it('mengembalikan sidebar ke rail ikon setelah hidden dari kondisi collapsed', async () => {
        loginAs();

        const wrapper = mountLayout();

        await wrapper.find('button[aria-label="Kecilkan sidebar"]').trigger('click');
        await wrapper.find('button[aria-label="Sembunyikan sidebar"]').trigger('click');

        expect(wrapper.find('.drawer-side aside').classes()).toContain('lg:w-0');

        await wrapper.find('button[aria-label="Tampilkan sidebar"]').trigger('click');

        expect(wrapper.find('.drawer-side aside').classes()).toContain('lg:w-16');
        expect(wrapper.find('.drawer-side aside').classes()).not.toContain('lg:w-0');
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
