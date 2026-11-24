import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useNavigationLayout } from '@/lib/useNavigationLayout';

describe('useNavigationLayout', () => {
    beforeEach(() => {
        localStorage.clear();
        useNavigationLayout().setLayout('sidebar');
    });

    afterEach(() => {
        vi.restoreAllMocks();
    });

    it('memakai sidebar sebagai default saat belum ada preferensi', async () => {
        localStorage.clear();
        vi.resetModules();

        const modul = await import('@/lib/useNavigationLayout');

        expect(modul.useNavigationLayout().layout.value).toBe('sidebar');
    });

    it('menyimpan pilihan topbar dan memulihkannya saat modul dimuat ulang', async () => {
        const { layout, setLayout } = useNavigationLayout();

        setLayout('topbar');

        expect(layout.value).toBe('topbar');
        expect(localStorage.getItem('navigation-layout')).toBe('topbar');

        vi.resetModules();

        const modul = await import('@/lib/useNavigationLayout');

        expect(modul.useNavigationLayout().layout.value).toBe('topbar');
    });

    it('menggilir sidebar dan topbar', () => {
        const { layout, toggleLayout } = useNavigationLayout();

        expect(layout.value).toBe('sidebar');

        toggleLayout();

        expect(layout.value).toBe('topbar');
        expect(localStorage.getItem('navigation-layout')).toBe('topbar');

        toggleLayout();

        expect(layout.value).toBe('sidebar');
        expect(localStorage.getItem('navigation-layout')).toBe('sidebar');
    });

    it('mengabaikan nilai layout yang tidak dikenal', () => {
        const { layout, setLayout } = useNavigationLayout();

        setLayout('grid');

        expect(layout.value).toBe('sidebar');
        expect(localStorage.getItem('navigation-layout')).toBeNull();
    });

    it('kembali ke sidebar saat nilai tersimpan tidak dikenal', async () => {
        localStorage.setItem('navigation-layout', 'grid');
        vi.resetModules();

        const modul = await import('@/lib/useNavigationLayout');

        expect(modul.useNavigationLayout().layout.value).toBe('sidebar');
    });

    it('menganggap sidebar aktif saat localStorage tidak bisa dibaca', async () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => {
            throw new Error('diblokir');
        });
        vi.resetModules();

        const modul = await import('@/lib/useNavigationLayout');

        expect(modul.useNavigationLayout().layout.value).toBe('sidebar');
    });

    it('tetap mengubah state saat localStorage tidak bisa ditulis', () => {
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => {
            throw new Error('diblokir');
        });

        const { layout, setLayout } = useNavigationLayout();

        expect(() => setLayout('topbar')).not.toThrow();
        expect(layout.value).toBe('topbar');
    });
});
