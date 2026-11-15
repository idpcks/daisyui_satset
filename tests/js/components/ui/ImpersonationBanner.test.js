import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { router, setPage } from '@tests/helpers/inertia.js';
import ImpersonationBanner from '@/components/ui/ImpersonationBanner.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const impersonation = {
    active: true,
    started_at: '2026-11-15T10:00:00+07:00',
    impersonator: { id: 'actor-1', name: 'Zed Superadmin', username: 'zed' },
};

const loginAs = (impersonating = impersonation) =>
    setPage({
        props: {
            auth: {
                user: { id: 'target-1', name: 'Operator Satu', username: 'operator1' },
                roles: ['operator'],
                permissions: [],
                impersonation: impersonating,
            },
        },
    });

const wrappers = [];

const mountBanner = () => {
    const wrapper = mount(ImpersonationBanner);

    wrappers.push(wrapper);

    return wrapper;
};

describe('ImpersonationBanner', () => {
    afterEach(() => {
        wrappers.forEach((wrapper) => wrapper.unmount());
        wrappers.length = 0;
    });

    it('menampilkan akun yang sedang dipakai dan akun asal', () => {
        loginAs();

        const wrapper = mountBanner();

        expect(wrapper.text()).toContain('Mode impersonation aktif');
        expect(wrapper.text()).toContain('Operator Satu');
        expect(wrapper.text()).toContain('operator1');
        expect(wrapper.text()).toContain('Zed Superadmin');
        expect(wrapper.text()).toContain('Kembali ke akun saya');
    });

    it('tidak tampil di luar mode impersonation', () => {
        loginAs(null);

        const wrapper = mountBanner();

        expect(wrapper.text()).toBe('');
    });

    it('mengirim permintaan kembali ke akun asal', async () => {
        loginAs();

        const wrapper = mountBanner();

        await wrapper.find('button').trigger('click');

        expect(router.delete).toHaveBeenCalledWith(
            '/impersonation',
            expect.objectContaining({ data: undefined }),
        );
    });
});
