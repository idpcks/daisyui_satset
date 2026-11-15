import { describe, expect, it } from 'vitest';
import { routes, userPath } from '@/lib/routes';

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
});
