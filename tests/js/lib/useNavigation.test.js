import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import { useNavigation } from '@/lib/useNavigation';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

const loginAs = (permissions = [], url = '/app', isSuperadmin = false) =>
    setPage({
        url,
        props: {
            appName: 'Starterkit',
            auth: { user: { id: 1, name: 'Siti' }, roles: ['admin'], permissions, is_superadmin: isSuperadmin },
        },
    });

const labels = (groups) => groups.flatMap((group) => group.items.map((item) => item.label));

describe('lib/useNavigation', () => {
    it('menampilkan menu yang tidak butuh izin dan yang diizinkan', () => {
        loginAs(['user.manage']);

        expect(labels(useNavigation().groups.value)).toEqual(['Dashboard', 'Pengguna']);
    });

    it('menampilkan menu RBAC hanya bila punya role.manage', () => {
        loginAs(['user.manage']);
        expect(labels(useNavigation().groups.value)).not.toContain('RBAC');

        loginAs(['user.manage', 'role.manage']);
        expect(labels(useNavigation().groups.value)).toContain('RBAC');
    });

    it('menyembunyikan menu dan grup yang tidak diizinkan', () => {
        loginAs([]);

        const groups = useNavigation().groups.value;

        expect(groups.map((group) => group.title)).toEqual(['Utama']);
        expect(labels(groups)).toEqual(['Dashboard']);
    });

    it('menampilkan menu Audit Log hanya bila punya audit.view', () => {
        loginAs(['user.manage', 'role.manage']);
        expect(labels(useNavigation().groups.value)).not.toContain('Audit Log');

        loginAs(['user.manage', 'role.manage', 'audit.view']);
        expect(labels(useNavigation().groups.value)).toContain('Audit Log');
    });

    it('selalu menyediakan aksi topbar Pengaturan', () => {
        loginAs([]);

        const { topbarActions } = useNavigation();

        expect(topbarActions.map((action) => action.label)).toEqual(['Pengaturan']);
        expect(topbarActions[0].href).toBe('/settings');
    });

    it('menandai menu aktif, termasuk saat ada query string', () => {
        loginAs([], '/settings?tab=keamanan');

        const { isActive } = useNavigation();

        expect(isActive('/settings')).toBe(true);
        expect(isActive('/users')).toBe(false);
    });

    it('mencocokkan sub-path detail sebagai menu aktif', () => {
        loginAs([], '/users/abc-123');

        const { isActive } = useNavigation();

        expect(isActive('/users')).toBe(true);
        expect(isActive('/app')).toBe(false);
    });
});
