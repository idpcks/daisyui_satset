import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetPage, setPage } from '@tests/helpers/inertia.js';
import TopNavigation from '@/components/navigation/TopNavigation.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const groups = [
    {
        title: 'Utama',
        items: [{ key: 'dashboard', label: 'Dashboard', href: '/app', icon: 'span' }],
    },
    {
        title: 'Administrasi',
        icon: 'span',
        collapsible: true,
        items: [
            { key: 'users', label: 'Pengguna', href: '/users', icon: 'span' },
            { key: 'rbac', label: 'RBAC', href: '/rbac', icon: 'span' },
        ],
    },
];

const mountNavigation = (isActive = (href) => href === '/app') =>
    mount(TopNavigation, {
        props: { groups, isActive },
    });

describe('TopNavigation', () => {
    beforeEach(() => resetPage());

    it('merender Dashboard sebagai link dan grup sebagai dropdown', () => {
        const wrapper = mountNavigation();
        const nav = wrapper.find('nav');

        expect(nav.attributes('aria-label')).toBe('Menu aplikasi');
        expect(nav.classes()).toContain('hidden');
        expect(nav.classes()).toContain('lg:flex');
        expect(nav.find('ul').classes()).toContain('menu');
        expect(nav.find('ul').classes()).toContain('menu-horizontal');
        expect(nav.find('a').text()).toContain('Dashboard');
        expect(nav.find('a').attributes('aria-current')).toBe('page');
        expect(nav.find('a').classes()).toContain('menu-active');

        const trigger = nav.find('button');
        const popover = nav.find('ul[popover]');

        expect(trigger.text()).toContain('Administrasi');
        expect(trigger.attributes('aria-expanded')).toBe('false');
        expect(trigger.attributes('aria-controls')).toBe(popover.attributes('id'));
        expect(popover.attributes('id')).toBeTruthy();
        expect(popover.findAll('a').map((link) => link.text())).toEqual(['Pengguna', 'RBAC']);
        expect(nav.find('[role="menu"]').exists()).toBe(false);
        expect(nav.find('[role="menubar"]').exists()).toBe(false);
    });

    it('menandai grup dan item aktif', () => {
        const wrapper = mountNavigation((href) => href === '/users');
        const trigger = wrapper.find('button');

        expect(trigger.classes()).toContain('menu-active');
        expect(wrapper.findAll('a').find((link) => link.text().includes('Pengguna')).classes()).toContain(
            'menu-active',
        );
        expect(
            wrapper.findAll('a').find((link) => link.text().includes('Pengguna')).attributes('aria-current'),
        ).toBe('page');
    });

    it('menyinkronkan aria-expanded dari event toggle popover', async () => {
        const wrapper = mountNavigation();
        const trigger = wrapper.find('button');
        const popover = wrapper.find('ul[popover]');
        const event = new Event('toggle');

        event.newState = 'open';
        popover.element.dispatchEvent(event);
        await wrapper.vm.$nextTick();

        expect(trigger.attributes('aria-expanded')).toBe('true');

        const closeEvent = new Event('toggle');

        closeEvent.newState = 'closed';
        popover.element.dispatchEvent(closeEvent);
        await wrapper.vm.$nextTick();

        expect(trigger.attributes('aria-expanded')).toBe('false');
    });

    it('menutup popover saat URL berubah', async () => {
        const wrapper = mountNavigation();
        const trigger = wrapper.find('button');
        const popover = wrapper.find('ul[popover]');
        const event = new Event('toggle');

        event.newState = 'open';
        popover.element.dispatchEvent(event);
        await wrapper.vm.$nextTick();

        expect(trigger.attributes('aria-expanded')).toBe('true');

        setPage({ url: '/users' });
        await wrapper.vm.$nextTick();

        expect(trigger.attributes('aria-expanded')).toBe('false');
    });
});
