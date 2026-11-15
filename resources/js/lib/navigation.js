import { LayoutDashboard, Settings, ShieldCheck, Users } from 'lucide-vue-next';
import { routes } from '@/lib/routes';

/**
 * Struktur menu sidebar.
 *
 * `permission: null` berarti semua pengguna boleh melihat menunya; selain itu
 * nilainya dicocokkan dengan permission dari shared prop `auth`.
 */
export const sidebarGroups = [
    {
        title: 'Utama',
        items: [
            {
                key: 'dashboard',
                label: 'Dashboard',
                href: routes.dashboard,
                icon: LayoutDashboard,
                permission: null,
            },
        ],
    },
    {
        title: 'Sistem',
        items: [
            {
                key: 'users',
                label: 'Pengguna',
                href: routes.users,
                icon: Users,
                permission: 'user.manage',
            },
            {
                key: 'rbac',
                label: 'RBAC',
                href: routes.rbac,
                icon: ShieldCheck,
                permission: 'role.manage',
            },
        ],
    },
];

/**
 * Aksi di navbar kanan atas. Tampil sebagai ikon saja, jadi `label` dipakai
 * untuk aria-label dan tooltip.
 */
export const topbarActions = [
    {
        key: 'settings',
        label: 'Pengaturan',
        href: routes.settings,
        icon: Settings,
    },
];
