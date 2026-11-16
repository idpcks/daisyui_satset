import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import { sidebarGroups, topbarActions } from '@/lib/navigation';
import { useAuth } from '@/lib/useAuth';
import { appUrl } from '@/lib/url';

/**
 * Navigasi siap pakai: menu sidebar yang sudah disaring permission, aksi
 * topbar, dan penanda menu aktif.
 */
export function useNavigation() {
    const page = usePage();
    const { can, isSuperadmin } = useAuth();

    const groups = computed(() =>
        sidebarGroups
            .map((group) => ({
                ...group,
                items: group.items.filter(
                    (item) =>
                        (!item.superadminOnly || isSuperadmin.value) &&
                        (!item.permission || can(item.permission)),
                ),
            }))
            .filter((group) => group.items.length > 0),
    );

    // Dock mobile adalah proyeksi dari grup yang sudah tersaring permission,
    // bukan daftar menu terpisah, supaya label/ikon/urutan tetap satu sumber.
    const dockItems = computed(() =>
        groups.value.flatMap((group) => group.items).filter((item) => item.dock === true),
    );

    // page.url memuat base path (mis. /admin/users), jadi pembandingnya juga
    // harus lewat appUrl agar tetap cocok saat dipasang di sub-path.
    const isActive = (href) => {
        const target = appUrl(href);

        return page.url === target || page.url.startsWith(`${target}/`) || page.url.startsWith(`${target}?`);
    };

    return { groups, dockItems, topbarActions, isActive };
}
