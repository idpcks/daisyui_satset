import { describe, expect, it } from 'vitest';
import {
    buildMenuEntries,
    clampIndex,
    groupEntries,
    searchEntries,
    stepIndex,
} from '@/lib/menuSearch';

const groups = [
    {
        title: 'Utama',
        items: [{ key: 'dashboard', label: 'Dashboard', href: '/app', icon: 'layout' }],
    },
    {
        title: 'Administrasi',
        collapsible: true,
        items: [
            { key: 'users', label: 'Pengguna', href: '/users', icon: 'users' },
            { key: 'rbac', label: 'RBAC', href: '/rbac', icon: 'shield' },
        ],
    },
    {
        title: 'Sistem',
        collapsible: true,
        items: [{ key: 'audit', label: 'Audit Log', href: '/audit', icon: 'history' }],
    },
];

const topbarActions = [{ key: 'settings', label: 'Pengaturan', href: '/settings', icon: 'cog' }];

const translations = {
    Dashboard: 'Dashboard',
    Utama: 'Main',
    Administrasi: 'Administration',
    Sistem: 'System',
    Pengguna: 'Users',
    RBAC: 'RBAC',
    'Audit Log': 'Audit Log',
    Pengaturan: 'Settings',
    Aksi: 'Actions',
};

const translateEnglish = (key) => translations[key] ?? key;
const translateIdentity = (key) => key;

const entries = (t = translateIdentity) => buildMenuEntries({ groups, topbarActions, t });

const keys = (results) => results.map((entry) => entry.key);

describe('lib/menuSearch', () => {
    describe('buildMenuEntries', () => {
        it('menggabungkan menu sidebar dan aksi topbar secara berurutan', () => {
            const built = entries();

            expect(keys(built)).toEqual(['dashboard', 'users', 'rbac', 'audit', 'settings']);
            expect(built.map((entry) => entry.kind)).toEqual([
                'menu',
                'menu',
                'menu',
                'menu',
                'action',
            ]);
        });

        it('menyimpan label mentah dan label terjemahan untuk pencarian', () => {
            const built = entries(translateEnglish);
            const users = built.find((entry) => entry.key === 'users');

            expect(users.label).toBe('Users');
            expect(users.rawLabel).toBe('Pengguna');
            expect(users.groupLabel).toBe('Administration');
            expect(users.groupKey).toBe('Administrasi');
        });

        it('memberi grup sendiri untuk aksi topbar', () => {
            const settings = entries(translateEnglish).find((entry) => entry.key === 'settings');

            expect(settings.groupKey).toBe('Aksi');
            expect(settings.groupLabel).toBe('Actions');
        });
    });

    describe('searchEntries', () => {
        it('mengembalikan daftar kosong saat query kosong', () => {
            expect(searchEntries(entries(), '')).toEqual([]);
            expect(searchEntries(entries(), '   ')).toEqual([]);
        });

        it('mencocokkan label Indonesia dan tidak peka huruf besar/kecil', () => {
            expect(keys(searchEntries(entries(), 'PENGGUNA'))).toEqual(['users']);
            expect(keys(searchEntries(entries(), '  pengguna  '))).toEqual(['users']);
        });

        it('mencocokkan label terjemahan dan label mentah sekaligus', () => {
            const built = entries(translateEnglish);

            expect(keys(searchEntries(built, 'users'))).toEqual(['users']);
            expect(keys(searchEntries(built, 'pengguna'))).toEqual(['users']);
        });

        it('mencocokkan nama grup sehingga satu grup muncul utuh', () => {
            expect(keys(searchEntries(entries(), 'administrasi'))).toEqual(['users', 'rbac']);
        });

        it('mencocokkan key entri', () => {
            expect(keys(searchEntries(entries(), 'rbac'))).toEqual(['rbac']);
        });

        it('menerapkan semua kata pada query multi-kata (AND)', () => {
            expect(keys(searchEntries(entries(), 'audit log'))).toEqual(['audit']);
            expect(keys(searchEntries(entries(), 'audit pengguna'))).toEqual([]);
        });

        it('mengurutkan kecocokan paling relevan lebih dulu', () => {
            const ranked = [
                {
                    key: 'long',
                    label: 'Manajemen Pengguna',
                    rawLabel: 'Manajemen Pengguna',
                    groupLabel: 'Administrasi',
                    groupKey: 'Administrasi',
                },
                {
                    key: 'exact',
                    label: 'Pengguna',
                    rawLabel: 'Pengguna',
                    groupLabel: 'Administrasi',
                    groupKey: 'Administrasi',
                },
            ];

            expect(keys(searchEntries(ranked, 'pengguna'))).toEqual(['exact', 'long']);
        });

        it('mempertahankan urutan asli saat peringkat sama', () => {
            const sameRank = [
                {
                    key: 'first',
                    label: 'Pengaturan Akun',
                    rawLabel: 'Pengaturan Akun',
                    groupLabel: 'X',
                    groupKey: 'X',
                },
                {
                    key: 'second',
                    label: 'Pengaturan Sistem',
                    rawLabel: 'Pengaturan Sistem',
                    groupLabel: 'X',
                    groupKey: 'X',
                },
            ];

            expect(keys(searchEntries(sameRank, 'pengaturan'))).toEqual(['first', 'second']);
        });

        it('mengembalikan daftar kosong bila tidak ada yang cocok', () => {
            expect(searchEntries(entries(), 'zzz')).toEqual([]);
        });
    });

    describe('groupEntries', () => {
        it('mengelompokkan hasil dan menggabungkan grup yang sama', () => {
            const built = entries();
            const sectioned = groupEntries([built[0], built[1], built[4], built[2]]);

            expect(sectioned.map((section) => section.key)).toEqual([
                'Utama',
                'Administrasi',
                'Aksi',
            ]);
            expect(sectioned[1].items.map((entry) => entry.key)).toEqual(['users', 'rbac']);
        });

        it('memakai label grup terjemahan sebagai judul seksi', () => {
            const sectioned = groupEntries(entries(translateEnglish));

            expect(sectioned.map((section) => section.label)).toEqual([
                'Main',
                'Administration',
                'System',
                'Actions',
            ]);
        });
    });

    describe('stepIndex', () => {
        it('membungkus panah bawah dari hasil terakhir ke pertama', () => {
            expect(stepIndex(2, 1, 3)).toBe(0);
            expect(stepIndex(0, 1, 3)).toBe(1);
        });

        it('membungkus panah atas dari hasil pertama ke terakhir', () => {
            expect(stepIndex(0, -1, 3)).toBe(2);
            expect(stepIndex(2, -1, 3)).toBe(1);
        });

        it('memulai dari ujung yang benar saat belum ada pilihan', () => {
            expect(stepIndex(-1, 1, 3)).toBe(0);
            expect(stepIndex(-1, -1, 3)).toBe(2);
        });

        it('mengembalikan -1 saat tidak ada hasil', () => {
            expect(stepIndex(0, 1, 0)).toBe(-1);
            expect(stepIndex(-1, 1, 0)).toBe(-1);
        });
    });

    describe('clampIndex', () => {
        it('menahan indeks di dalam batas hasil', () => {
            expect(clampIndex(5, 3)).toBe(2);
            expect(clampIndex(1, 3)).toBe(1);
        });

        it('memulai dari 0 saat indeks tidak valid', () => {
            expect(clampIndex(-1, 3)).toBe(0);
            expect(clampIndex(Number.NaN, 3)).toBe(0);
        });

        it('mengembalikan -1 saat tidak ada hasil', () => {
            expect(clampIndex(0, 0)).toBe(-1);
        });
    });
});
