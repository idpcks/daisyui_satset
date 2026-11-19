import { beforeEach, describe, expect, it } from 'vitest';
import { appUrl, getBasePath, setBasePath } from '@/lib/url';

describe('appUrl', () => {
    beforeEach(() => setBasePath(''));

    it('mengembalikan path apa adanya saat aplikasi di root domain', () => {
        expect(getBasePath()).toBe('');
        expect(appUrl('/users')).toBe('/users');
    });

    it('menambahkan prefix saat aplikasi dipasang di sub-path', () => {
        setBasePath('/admin');

        expect(appUrl('/users')).toBe('/admin/users');
        expect(appUrl('/app')).toBe('/admin/app');
    });

    it('menormalkan path tanpa garis miring di depan', () => {
        setBasePath('/admin');

        expect(appUrl('users')).toBe('/admin/users');
    });

    it('mengabaikan garis miring di akhir prefix', () => {
        setBasePath('/admin/');

        expect(appUrl('/auth/signin')).toBe('/admin/auth/signin');
    });

    it('kembali ke root domain saat base path bukan string', () => {
        setBasePath('/admin');
        setBasePath(null);

        expect(getBasePath()).toBe('');
        expect(appUrl('/logout')).toBe('/logout');
    });
});
