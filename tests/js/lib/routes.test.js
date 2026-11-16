import { describe, expect, it } from 'vitest';
import { rolePath, routes, userImpersonatePath, userPath, userSessionPath } from '@/lib/routes';

describe('lib/routes', () => {
    it('memakai path absolut tanpa slash di akhir', () => {
        Object.entries(routes).forEach(([name, path]) => {
            expect(path.startsWith('/'), `${name} harus diawali slash`).toBe(true);

            if (path !== '/') {
                expect(path.endsWith('/'), `${name} tidak boleh diakhiri slash`).toBe(false);
            }
        });
    });

    it('tidak menyimpan path duplikat', () => {
        const paths = Object.values(routes);

        expect(new Set(paths).size).toBe(paths.length);
    });

    it('membentuk path detail pengguna dari path daftar', () => {
        expect(userPath('abc-123')).toBe(`${routes.users}/abc-123`);
    });

    it('membentuk path impersonation dari path daftar pengguna', () => {
        expect(userImpersonatePath('abc-123')).toBe(`${routes.users}/abc-123/impersonate`);
    });

    it('membentuk path peran dari path daftar peran RBAC', () => {
        expect(rolePath('role-1')).toBe(`${routes.rbacRoles}/role-1`);
    });

    it('membentuk path sesi perangkat dari path daftar sesi', () => {
        expect(userSessionPath(12)).toBe(`${routes.settingsSessions}/12`);
    });
});
