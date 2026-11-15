import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import { useAuth } from '@/lib/useAuth';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('useAuth', () => {
    it('mengembalikan null dan daftar kosong tanpa data auth', () => {
        const { user, roles, permissions, isSuperadmin, impersonation, can, hasRole } = useAuth();

        expect(user.value).toBeNull();
        expect(roles.value).toEqual([]);
        expect(permissions.value).toEqual([]);
        expect(isSuperadmin.value).toBe(false);
        expect(impersonation.value).toBeNull();
        expect(can('user.manage')).toBe(false);
        expect(hasRole('admin')).toBe(false);
    });

    it('membaca user, role, dan permission dari shared prop', () => {
        setPage({
            props: {
                auth: {
                    user: { name: 'Siti' },
                    roles: ['admin'],
                    permissions: ['user.manage'],
                    is_superadmin: true,
                    impersonation: { active: true, impersonator: { name: 'Zed' } },
                },
            },
        });

        const { user, roles, permissions, isSuperadmin, impersonation, can, hasRole } = useAuth();

        expect(user.value).toEqual({ name: 'Siti' });
        expect(roles.value).toEqual(['admin']);
        expect(permissions.value).toHaveLength(1);
        expect(isSuperadmin.value).toBe(true);
        expect(impersonation.value).toEqual({ active: true, impersonator: { name: 'Zed' } });
        expect(can('user.manage')).toBe(true);
        expect(can('user.delete')).toBe(false);
        expect(hasRole('admin')).toBe(true);
        expect(hasRole('operator')).toBe(false);
    });
});
