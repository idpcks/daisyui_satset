import { describe, expect, it, vi } from 'vitest';
import { setPage } from '@tests/helpers/inertia.js';
import { useI18n } from '@/lib/i18n';

vi.mock('@inertiajs/vue3', () => import('@tests/helpers/inertia.js'));

describe('useI18n', () => {
    it('memakai locale id dan teks sumber saat terjemahan tidak tersedia', () => {
        setPage({ props: {} });

        const { t, locale, translations } = useI18n();

        expect(locale.value).toBe('id');
        expect(translations.value).toEqual({});
        expect(t('Simpan')).toBe('Simpan');
    });

    it('membaca terjemahan dan locale dari shared prop', () => {
        setPage({
            props: {
                locale: 'en',
                translations: { Simpan: 'Save' },
            },
        });

        const { t, locale } = useI18n();

        expect(locale.value).toBe('en');
        expect(t('Simpan')).toBe('Save');
    });

    it('mengganti parameter bergaya Laravel', () => {
        setPage({
            props: {
                locale: 'en',
                translations: { 'Fitur :label gagal.': 'Feature :label failed.' },
            },
        });

        const { t } = useI18n();

        expect(t('Fitur :label gagal.', { label: 'Audit' })).toBe('Feature Audit failed.');
    });

    it('tetap mengganti parameter saat kunci tidak diterjemahkan', () => {
        setPage({ props: {} });

        const { t } = useI18n();

        expect(t('Halo :name', { name: 'Siti' })).toBe('Halo Siti');
    });
});
