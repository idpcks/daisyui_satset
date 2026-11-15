import { mount } from '@vue/test-utils';
import { afterEach, describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import AuditIndex from '@/pages/audit/Index.vue';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const paginator = (data) => ({
    data,
    links: [],
    last_page: 1,
});

const makeLog = (overrides = {}) => ({
    id: 'log-1',
    event: 'impersonation_started',
    description: 'Zed Superadmin masuk sebagai Operator Satu.',
    impersonator_name: 'Zed Superadmin',
    impersonator_username: 'zed',
    impersonated_name: 'Operator Satu',
    impersonated_username: 'operator1',
    ip_address: '127.0.0.1',
    duration_seconds: null,
    created_at: '2026-11-15T10:00:00+07:00',
    ...overrides,
});

const wrappers = [];

const mountPage = (logs = paginator([makeLog()])) => {
    setPage({
        props: {
            auth: {
                user: { id: 'actor-1', name: 'Zed Superadmin' },
                roles: ['superadmin'],
                permissions: [],
                is_superadmin: true,
            },
        },
    });

    const wrapper = mount(AuditIndex, { props: { logs } });

    wrappers.push(wrapper);

    return wrapper;
};

describe('halaman audit/Index', () => {
    afterEach(() => {
        wrappers.forEach((wrapper) => wrapper.unmount());
        wrappers.length = 0;
    });

    it('menampilkan riwayat impersonation', () => {
        const wrapper = mountPage(
            paginator([
                makeLog(),
                makeLog({
                    id: 'log-2',
                    event: 'impersonation_stopped',
                    description: 'Zed Superadmin kembali dari akun Operator Satu.',
                    duration_seconds: 125,
                }),
            ]),
        );

        expect(wrapper.text()).toContain('Mulai impersonation');
        expect(wrapper.text()).toContain('Selesai impersonation');
        expect(wrapper.text()).toContain('Zed Superadmin');
        expect(wrapper.text()).toContain('operator1');
        expect(wrapper.text()).toContain('2 mnt 5 dtk');
        expect(wrapper.text()).toContain('127.0.0.1');
    });

    it('menampilkan empty state saat belum ada log', () => {
        const wrapper = mountPage(paginator([]));

        expect(wrapper.text()).toContain('Belum ada aktivitas impersonation.');
    });
});
