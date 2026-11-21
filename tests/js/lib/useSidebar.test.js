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

    it('menggilir expanded dan hidden, lalu kembali ke expanded', () => {
        const { state, toggle } = useSidebar();

        expect(state.value).toBe('expanded');

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

    it('memetakan nilai lama (collapsed, 1, 0) ke expanded', async () => {
        for (const legacy of ['collapsed', '1', '0']) {
            localStorage.setItem('sidebar-collapsed', legacy);
            vi.resetModules();

            const modul = await import('@/lib/useSidebar');

            expect(modul.useSidebar().state.value).toBe('expanded');
        }
    });

    it('menyimpan state expanded dan memulihkannya saat modul dimuat ulang', async () => {
        useSidebar().setState('expanded');

        vi.resetModules();

        const modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().state.value).toBe('expanded');
    });

    it('menganggap sidebar expanded saat localStorage tidak bisa dibaca', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('diblokir');
        });
        vi.resetModules();

        const modul = await import('@/lib/useSidebar');

        expect(modul.useSidebar().state.value).toBe('expanded');
    });
});
