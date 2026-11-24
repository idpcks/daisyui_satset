<script setup>
import { computed, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { ChevronDown, LogOut } from 'lucide-vue-next';
import BrandMark from '@/components/ui/BrandMark.vue';
import { useI18n } from '@/lib/i18n';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

const props = defineProps({
    appName: { type: String, required: true },
    groups: { type: Array, default: () => [] },
    isActive: { type: Function, required: true },
    user: { type: Object, default: null },
    safeAvatar: { type: String, default: null },
    initial: { type: String, default: '?' },
    isHidden: { type: Boolean, default: false },
    isHiddenDesktop: { type: Boolean, default: false },
    isBrandHandoff: { type: Boolean, default: false },
    hiddenOnDesktop: { type: Boolean, default: false },
});

const { t } = useI18n();

// Lebar aside: 0 saat hidden agar kolom grid drawer ikut menutup sehingga main
// content memakai seluruh lebar. Ukuran mobile (w-64) tidak berubah.
const sidebarWidth = computed(() => {
    if (props.isHidden) {
        return 'w-64 lg:w-0 lg:overflow-hidden lg:border-r-0';
    }

    return 'w-64';
});

const groupIsActive = (group) => group.items.some((item) => props.isActive(item.href));

// Disclosure state disimpan per judul grup. Sebelum pengguna menyentuhnya,
// grup yang memiliki item aktif tetap terbuka agar konteks navigasi tidak hilang.
const openGroups = ref({});

const isGroupOpen = (group) => {
    if (Object.prototype.hasOwnProperty.call(openGroups.value, group.title)) {
        return openGroups.value[group.title];
    }

    return groupIsActive(group);
};

const toggleGroup = (title, currentlyOpen) => {
    openGroups.value[title] = !currentlyOpen;
};
</script>

<template>
    <div class="drawer-side z-20" :class="{ 'lg:hidden': hiddenOnDesktop }">
        <label for="sidebar" :aria-label="t('Tutup menu')" class="drawer-overlay"></label>

        <aside
            class="flex min-h-full min-w-0 flex-col border-r border-base-300 bg-base-100 transition-[width] duration-200"
            :class="sidebarWidth"
            :inert="isHiddenDesktop || null"
        >
            <!-- Brand -->
            <div class="flex h-16 items-center border-b border-base-300 px-5">
                <BrandMark
                    :name="appName"
                    class="transition-opacity duration-150 motion-reduce:transition-none"
                    :class="isBrandHandoff ? 'opacity-0' : 'opacity-100'"
                />
            </div>

            <!-- Menu sidebar: Dashboard langsung, grup lain menjadi parent/submenu. -->
            <nav
                class="flex-1 overflow-y-auto overflow-x-hidden py-4 px-3"
                :aria-label="t('Menu aplikasi')"
            >
                <ul class="menu w-full gap-1 p-0">
                    <template v-for="(group, index) in groups" :key="group.title">
                        <template v-if="!group.collapsible">
                            <li
                                class="menu-title mt-1 px-3 text-xs font-semibold uppercase tracking-wider text-base-content/50"
                            >
                                {{ t(group.title) }}
                            </li>
                            <li v-for="item in group.items" :key="item.href">
                                <Link
                                    :href="appUrl(item.href)"
                                    :aria-current="isActive(item.href) ? 'page' : undefined"
                                    :class="{ 'menu-active': isActive(item.href) }"
                                >
                                    <component
                                        :is="item.icon"
                                        class="size-5 shrink-0"
                                        aria-hidden="true"
                                    />
                                    <span class="truncate">{{ t(item.label) }}</span>
                                </Link>
                            </li>
                        </template>

                        <li v-else>
                            <button
                                type="button"
                                class="font-medium grid w-full grid-flow-col items-center gap-2 rounded-field px-3 py-1.5 text-start hover:bg-base-content/10"
                                :class="{ 'text-primary': groupIsActive(group) }"
                                :aria-expanded="isGroupOpen(group) ? 'true' : 'false'"
                                :aria-controls="`sidebar-group-${index}`"
                                @click="toggleGroup(group.title, isGroupOpen(group))"
                            >
                                <component
                                    :is="group.icon"
                                    class="size-5 shrink-0"
                                    aria-hidden="true"
                                />
                                <span class="truncate">{{ t(group.title) }}</span>
                                <ChevronDown
                                    class="size-4 shrink-0 transition-transform duration-150 motion-reduce:transition-none"
                                    :class="{ 'rotate-180': isGroupOpen(group) }"
                                    aria-hidden="true"
                                />
                            </button>

                            <ul v-show="isGroupOpen(group)" :id="`sidebar-group-${index}`">
                                <li v-for="item in group.items" :key="item.href">
                                    <Link
                                        :href="appUrl(item.href)"
                                        :aria-current="isActive(item.href) ? 'page' : undefined"
                                        :class="{ 'menu-active': isActive(item.href) }"
                                    >
                                        <component
                                            :is="item.icon"
                                            class="size-4 shrink-0"
                                            aria-hidden="true"
                                        />
                                        <span class="truncate">{{ t(item.label) }}</span>
                                    </Link>
                                </li>
                            </ul>
                        </li>
                    </template>
                </ul>
            </nav>

            <!-- Pengguna -->
            <div v-if="user" class="border-t border-base-300 p-3">
                <div class="flex items-center gap-3 rounded-lg p-2">
                    <div class="avatar avatar-placeholder shrink-0">
                        <div class="size-9 rounded-full bg-neutral text-neutral-content">
                            <img v-if="safeAvatar" :src="safeAvatar" :alt="user.name" />
                            <span v-else class="text-sm">{{ initial }}</span>
                        </div>
                    </div>

                    <div class="min-w-0 flex-1">
                        <p class="truncate text-sm font-medium">{{ user.name }}</p>
                        <p class="truncate text-xs text-base-content/60">{{ user.email }}</p>
                    </div>

                    <Link
                        :href="appUrl(routes.logout)"
                        method="post"
                        as="button"
                        class="btn btn-ghost btn-sm btn-square"
                        :aria-label="t('Keluar')"
                        :title="t('Keluar')"
                    >
                        <LogOut class="size-5" aria-hidden="true" />
                    </Link>
                </div>
            </div>
        </aside>
    </div>
</template>
