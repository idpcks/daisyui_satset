import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { useToast } from '@/lib/useToast';

describe('useToast', () => {
    const { toasts, success, error, dismiss } = useToast();

    beforeEach(() => {
        vi.useFakeTimers();
        toasts.value = [];
    });

    afterEach(() => {
        vi.clearAllTimers();
        vi.useRealTimers();
        toasts.value = [];
    });

    it('menambah toast sukses dan error dengan id berurutan', () => {
        const idSukses = success('Data tersimpan');
        const idError = error('Gagal menyimpan');

        expect(idError).toBe(idSukses + 1);
        expect(toasts.value).toEqual([
            { id: idSukses, type: 'success', message: 'Data tersimpan' },
            { id: idError, type: 'error', message: 'Gagal menyimpan' },
        ]);
    });

    it('menutup toast otomatis setelah durasi bawaan', () => {
        success('Sementara');

        expect(toasts.value).toHaveLength(1);

        vi.advanceTimersByTime(4999);
        expect(toasts.value).toHaveLength(1);

        vi.advanceTimersByTime(1);
        expect(toasts.value).toHaveLength(0);
    });

    it('menghormati durasi khusus dan durasi 0', () => {
        success('Cepat', { duration: 100 });
        error('Tetap', { duration: 0 });

        vi.advanceTimersByTime(100);

        expect(toasts.value).toHaveLength(1);
        expect(toasts.value[0].message).toBe('Tetap');
    });

    it('dismiss manual membatalkan timer yang tersisa', () => {
        const id = success('Manual');

        dismiss(id);

        expect(toasts.value).toHaveLength(0);

        vi.advanceTimersByTime(5000);

        expect(toasts.value).toHaveLength(0);
    });
});
