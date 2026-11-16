import { Cog, Flag, History, LayoutDashboard, Settings, ShieldCheck, UserCog, Users } from 'lucide-vue-next';
import { routes } from '@/lib/routes';

/**
 * Struktur menu sidebar.
 *
 * `permission: null` berarti semua pengguna boleh melihat menunya; selain itu
 * nilainya dicocokkan dengan permission dari shared prop `auth`. Menu dengan
 * `superadminOnly` hanya tampil untuk peran superadmin. Grup dengan
 * `collapsible: true` tampil sebagai parent yang membuka submenu.
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
        title: 'Administrasi',
        icon: UserCog,
        collapsible: true,
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
    {
        title: 'Sistem',
        icon: Cog,
        collapsible: true,
        items: [
            {
                key: 'features',
                label: 'Feature Flags',
                href: routes.features,
                icon: Flag,
                permission: 'feature.manage',
            },
            {
                key: 'audit',
                label: 'Audit Log',
                href: routes.audit,
                icon: History,
                permission: 'audit.view',
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
