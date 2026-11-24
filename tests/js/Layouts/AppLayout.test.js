import { mount } from '@vue/test-utils';
import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { PanelLeft, PanelLeftClose, PanelRight } from 'lucide-vue-next';
import { emitRouter, router, setPage } from '@tests/helpers/inertia.js';
import { useSidebar } from '@/lib/useSidebar';
import { useNavigationLayout } from '@/lib/useNavigationLayout';
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
        useNavigationLayout().setLayout('sidebar');
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
        const groups = wrapper
            .findAll('nav li')
            .filter((item) => item.find('button[aria-expanded]').exists());

        expect(groups).toHaveLength(2);
        expect(groups[0].find('button[aria-expanded]').text()).toContain('Administrasi');
        expect(groups[0].findAll('ul a').map((link) => link.text())).toEqual(['Pengguna', 'RBAC']);
        expect(groups[1].find('button[aria-expanded]').text()).toContain('Sistem');
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
        const administrationGroup = wrapper
            .findAll('nav li')
            .find((item) => item.text().includes('Administrasi'));

        expect(usersLink.classes()).toContain('menu-active');
        expect(administrationGroup.find('button[aria-expanded]').attributes('aria-expanded')).toBe('true');

        setPage({ url: '/app' });
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
            '/app',
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

    it('menggilir ikon dan label sidebar penuh dan tersembunyi', async () => {
        loginAs();

        const wrapper = mountLayout();
        const tombol = () =>
            wrapper.find(
                'button[aria-label="Sembunyikan sidebar"], button[aria-label="Tampilkan sidebar"]',
            );
        const aside = () => wrapper.find('.drawer-side aside');

        expect(tombol().attributes('aria-label')).toBe('Sembunyikan sidebar');
        expect(tombol().findComponent(PanelLeft).exists()).toBe(true);
        expect(aside().classes()).toContain('w-64');
        expect(aside().classes()).not.toContain('lg:w-0');

        await tombol().trigger('click');

        expect(tombol().attributes('aria-label')).toBe('Tampilkan sidebar');
        expect(tombol().findComponent(PanelLeftClose).exists()).toBe(true);
        expect(aside().classes()).toContain('lg:w-0');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('hidden');

        await tombol().trigger('click');

        expect(tombol().attributes('aria-label')).toBe('Sembunyikan sidebar');
        expect(tombol().findComponent(PanelLeft).exists()).toBe(true);
        expect(aside().classes()).not.toContain('lg:w-0');
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

        expect(aside().classes()).toContain('lg:w-0');
        expect(aside().classes()).toContain('lg:overflow-hidden');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('hidden');

        expect(navbar().text()).toContain('Starterkit');
        expect(tombolTampilkan().exists()).toBe(true);

        await tombolTampilkan().trigger('click');

        expect(aside().classes()).not.toContain('lg:w-0');
        expect(aside().classes()).toContain('w-64');
        expect(navbar().text()).not.toContain('Starterkit');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('expanded');
    });

    it('beralih antara sidebar dan menu horizontal', async () => {
        loginAs(undefined, ['user.manage', 'role.manage']);

        const wrapper = mountLayout();

        expect(wrapper.find('.drawer').classes()).toContain('lg:drawer-open');
        expect(wrapper.find('.drawer-side').classes()).not.toContain('lg:hidden');
        expect(wrapper.find('button[aria-label="Buka menu pengguna"]').exists()).toBe(false);

        await wrapper.find('button[aria-label="Gunakan menu horizontal"]').trigger('click');

        expect(localStorage.getItem('navigation-layout')).toBe('topbar');
        expect(wrapper.find('.drawer').classes()).not.toContain('lg:drawer-open');
        expect(wrapper.find('.drawer-side').classes()).toContain('lg:hidden');
        expect(wrapper.find('nav[aria-label="Menu aplikasi"]').classes()).toContain('lg:flex');
        expect(wrapper.find('button[aria-label="Buka menu pengguna"]').exists()).toBe(true);
        expect(wrapper.find('a[aria-label="Pengaturan"]').exists()).toBe(false);
        expect(wrapper.find('button[aria-label="Sembunyikan sidebar"]').exists()).toBe(false);

        await wrapper.find('button[aria-label="Gunakan menu vertikal"]').trigger('click');

        expect(localStorage.getItem('navigation-layout')).toBe('sidebar');
        expect(wrapper.find('.drawer').classes()).toContain('lg:drawer-open');
        expect(wrapper.find('.drawer-side').classes()).not.toContain('lg:hidden');
        expect(wrapper.find('button[aria-label="Buka menu pengguna"]').exists()).toBe(false);
        expect(wrapper.find('a[aria-label="Pengaturan"]').exists()).toBe(true);
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

    it('menerjemahkan label layout saat locale Inggris aktif', async () => {
        setPage({
            props: {
                appName: 'Starterkit',
                auth: {
                    user: { id: 1, name: 'Siti Aminah', email: 'siti@example.test' },
                    roles: ['superadmin'],
                    permissions: [],
                },
                translations: {
                    Keluar: 'Log Out',
                    'Buka menu': 'Open menu',
                    'Tutup menu': 'Close menu',
                    'Navigasi cepat': 'Quick navigation',
                    'Menu aplikasi': 'Application menu',
                },
            },
        });

        const wrapper = mountLayout();

        expect(wrapper.find('a[aria-label="Log Out"]').exists()).toBe(true);
        expect(wrapper.find('label[aria-label="Open menu"]').exists()).toBe(true);
        expect(wrapper.find('label[aria-label="Close menu"]').exists()).toBe(true);
        expect(wrapper.find('nav[aria-label="Quick navigation"]').exists()).toBe(true);
        expect(wrapper.find('nav[aria-label="Application menu"]').exists()).toBe(true);
    });

    it('membuka command palette lewat Ctrl+K dan Meta+K', async () => {
        loginAs(undefined, ['user.manage']);

        const wrapper = mountLayout();
        const palette = () => wrapper.find('dialog.palette');

        expect(palette().element.open).toBe(false);

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }));
        await wrapper.vm.$nextTick();

        expect(palette().element.open).toBe(true);

        await palette().trigger('keydown', { key: 'Escape' });
        await wrapper.vm.$nextTick();

        expect(palette().element.open).toBe(false);

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'K', metaKey: true }));
        await wrapper.vm.$nextTick();

        expect(palette().element.open).toBe(true);
    });

    it('membuka command palette dari tombol topbar', async () => {
        loginAs();

        const wrapper = mountLayout();

        await wrapper.find('button[aria-label="Cari menu"]').trigger('click');

        expect(wrapper.find('dialog.palette').element.open).toBe(true);
    });

    it('bernavigasi ke menu yang dipilih dari command palette', async () => {
        loginAs(undefined, ['user.manage']);

        const wrapper = mountLayout();

        await wrapper.find('button[aria-label="Cari menu"]').trigger('click');

        const users = wrapper
            .findAll('dialog.palette [role="option"]')
            .find((option) => option.text().includes('Pengguna'));

        await users.trigger('click');

        expect(router.visit).toHaveBeenCalledWith('/users');
        expect(wrapper.find('dialog.palette').element.open).toBe(false);
    });

    it('menutup command palette saat URL berubah', async () => {
        loginAs();

        const wrapper = mountLayout();

        await wrapper.find('button[aria-label="Cari menu"]').trigger('click');
        expect(wrapper.find('dialog.palette').element.open).toBe(true);

        setPage({ url: '/users' });
        await wrapper.vm.$nextTick();

        expect(wrapper.find('dialog.palette').element.open).toBe(false);
    });

    it('tidak merebut Ctrl+K saat dialog lain sedang terbuka', async () => {
        loginAs();

        const wrapper = mountLayout();
        const other = document.createElement('dialog');

        document.body.appendChild(other);
        other.showModal();

        window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', ctrlKey: true }));
        await wrapper.vm.$nextTick();

        expect(wrapper.find('dialog.palette').element.open).toBe(false);

        other.close();
        other.remove();
    });
});
