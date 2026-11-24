import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { resetPage } from '@tests/helpers/inertia.js';
import UserMenu from '@/components/navigation/UserMenu.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const user = { id: 1, name: 'Siti Aminah', email: 'siti@example.test' };

const mountMenu = () =>
    mount(UserMenu, {
        props: { user, safeAvatar: null, initial: 'S' },
    });

describe('UserMenu', () => {
    beforeEach(() => resetPage());

    it('menampilkan identitas dan aksi akun', () => {
        const wrapper = mountMenu();
        const trigger = wrapper.find('button[aria-label="Buka menu pengguna"]');
        const popover = wrapper.find('ul[popover]');

        expect(trigger.attributes('aria-expanded')).toBe('false');
        expect(trigger.attributes('aria-controls')).toBe(popover.attributes('id'));
        expect(wrapper.text()).toContain('Siti Aminah');
        expect(wrapper.text()).toContain('siti@example.test');
        expect(wrapper.find('a[href="/settings"]').text()).toContain('Pengaturan');
        expect(wrapper.find('a[href="/logout"]').text()).toContain('Keluar');
        expect(wrapper.find('[role="menu"]').exists()).toBe(false);
    });

    it('menyinkronkan aria-expanded dari event toggle popover', async () => {
        const wrapper = mountMenu();
        const trigger = wrapper.find('button[aria-label="Buka menu pengguna"]');
        const popover = wrapper.find('ul[popover]');
        const event = new Event('toggle');

        event.newState = 'open';
        popover.element.dispatchEvent(event);
        await wrapper.vm.$nextTick();

        expect(trigger.attributes('aria-expanded')).toBe('true');
    });
});
