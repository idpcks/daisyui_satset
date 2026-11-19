import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useSidebar } from '@/lib/useSidebar';

describe('useSidebar', () => {
    beforeEach(() => {
        localStorage.clear();
        useSidebar().setState('expanded');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('menggilir expanded, collapsed, hidden, lalu kembali ke expanded', () => {
        const { state, toggle } = useSidebar();

        expect(state.value).toBe('expanded');

        toggle();

        expect(state.value).toBe('collapsed');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('collapsed');

        toggle();

        expect(state.value).toBe('hidden');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('hidden');

        toggle();

        expect(state.value).toBe('expanded');
        expect(localStorage.getItem('sidebar-collapsed')).toBe('expanded');
    });

    it('menyimpan state hidden dan memulihkannya saat modul dimuat ulang', async () => {
        useSidebar().setState('hidden');

        expect(localStorage.getItem('sidebar-collapsed')).toBe('hidden');

        vi.resetModules();

        const modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().state.value).toBe('hidden');
    });

    it('tetap membaca nilai lama 1 dan 0 sebagai collapsed dan expanded', async () => {
        localStorage.setItem('sidebar-collapsed', '1');
        vi.resetModules();

        let modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().state.value).toBe('collapsed');

        localStorage.setItem('sidebar-collapsed', '0');
        vi.resetModules();

        modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().state.value).toBe('expanded');
    });

    it('menampilkan sidebar penuh saat toggle dari kondisi hidden', () => {
        const { state, setState, toggle } = useSidebar();

        setState('collapsed');
        setState('hidden');
        toggle();

        expect(state.value).toBe('expanded');
    });

    it('menganggap sidebar terbuka saat localStorage tidak bisa dibaca', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('diblokir');
        });
        vi.resetModules();

        const modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().state.value).toBe('expanded');
    });
});
