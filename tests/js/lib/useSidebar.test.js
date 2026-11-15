import { describe, expect, it, vi } from 'vitest';
import { useSidebar } from '@/lib/useSidebar';

describe('useSidebar', () => {
    it('membalik state dan menyimpannya ke localStorage', () => {
        const { collapsed, toggle } = useSidebar();
        const awal = collapsed.value;

        toggle();

        expect(collapsed.value).toBe(!awal);
        expect(localStorage.getItem('sidebar-collapsed')).toBe(collapsed.value ? '1' : '0');

        toggle();

        expect(collapsed.value).toBe(awal);
        expect(localStorage.getItem('sidebar-collapsed')).toBe(awal ? '1' : '0');
    });

    it('menganggap sidebar terbuka saat localStorage tidak bisa dibaca', async () => {
        vi.resetModules();
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('diblokir');
        });

        const modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().collapsed.value).toBe(false);
    });
});
