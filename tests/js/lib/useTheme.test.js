import { beforeEach, describe, expect, it, vi } from 'vitest';
import { useTheme } from '@/lib/useTheme';

describe('useTheme', () => {
    beforeEach(() => {
        document.documentElement.dataset.theme = 'light';
    });

    it('memakai tema dari elemen html sebagai nilai awal', () => {
        expect(useTheme().theme.value).toBe('light');
    });

    it('menyimpan tema baru ke dataset dan localStorage', () => {
        const { theme, setTheme } = useTheme();

        setTheme('dark');

        expect(theme.value).toBe('dark');
        expect(document.documentElement.dataset.theme).toBe('dark');
        expect(localStorage.getItem('theme')).toBe('dark');
    });

    it('tetap berjalan saat localStorage menolak penulisan', () => {
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('diblokir');
        });

        const { setTheme } = useTheme();

        expect(() => setTheme('cupcake')).not.toThrow();
        expect(document.documentElement.dataset.theme).toBe('cupcake');
    });
});
