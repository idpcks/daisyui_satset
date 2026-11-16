<script setup>
import { computed, ref, onMounted, onUnmounted } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import PageSkeleton from '@/components/ui/PageSkeleton.vue';
import Footer from '@/components/ui/Footer.vue';
import ImpersonationBanner from '@/components/ui/ImpersonationBanner.vue';
import Toast from '@/components/ui/Toast.vue';
import { LayoutDashboard, LogOut, Menu, PanelLeftClose, PanelLeftOpen } from 'lucide-vue-next';
import { useTheme } from '@/lib/useTheme';
import { useAuth } from '@/lib/useAuth';
import { useNavigation } from '@/lib/useNavigation';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useSidebar } from '@/lib/useSidebar';

const navigating = ref(false);
const isDesktop = ref(false);
let timer;
let desktopMedia = null;
const offs = [];

const syncDesktopViewport = (event) => {
    isDesktop.value = event.matches;
};

onMounted(() => {
    if (typeof window.matchMedia === 'function') {
        desktopMedia = window.matchMedia('(min-width: 1024px)');
        isDesktop.value = desktopMedia.matches;
        desktopMedia.addEventListener?.('change', syncDesktopViewport);
    }

    offs.push(
        router.on('start', (event) => {
            const visit = event.detail.visit;

            // Lewati: form submit, filter/pencarian (preserveState), dan partial reload,
            // supaya input yang sedang diketik tidak hilang.
            if (visit.method !== 'get' || visit.preserveState || visit.only?.length) return;

            clearTimeout(timer);
            timer = setTimeout(() => (navigating.value = true), 120);
        }),
        router.on('finish', () => {
            clearTimeout(timer);
            navigating.value = false;
        }),
    );
});

onUnmounted(() => {
    clearTimeout(timer);
    desktopMedia?.removeEventListener?.('change', syncDesktopViewport);
    offs.forEach((off) => off());
});


const page = usePage();
const { theme, setTheme } = useTheme();
const { user } = useAuth();
const { collapsed, toggle } = useSidebar();

// Menu sidebar dan aksi topbar berasal dari lib/navigation.js; di sini hanya
// memakai hasil yang sudah disaring permission.
const { groups: visibleGroups, topbarActions, isActive } = useNavigation();

// Rail ikon hanya dipakai saat sidebar desktop benar-benar diciutkan. Di
// mobile drawer tetap menampilkan hierarki parent/submenu secara utuh.
const isCollapsed = computed(() => collapsed.value && isDesktop.value);
const flatItems = computed(() => visibleGroups.value.flatMap((group) => group.items));
const groupIsActive = (group) => group.items.some((item) => isActive(item.href));

const appName = computed(() => page.props.appName ?? 'Aplikasi');
const themes = ['light', 'dark', 'cupcake', 'corporate'];

const initial = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '?');
</script>

<template>
    <div class="drawer lg:drawer-open">
        <input id="sidebar" type="checkbox" class="drawer-toggle" />

        <div class="drawer-content flex min-h-screen min-w-0 flex-col bg-base-200">
            <!-- Peringatan global saat superadmin memakai akun pengguna lain. -->
            <ImpersonationBanner />

            <!-- Navbar -->
            <div class="navbar sticky top-0 z-10 gap-2 border-b border-base-300 bg-base-100 px-4">
                <!-- Mobile: buka drawer -->
                <label for="sidebar" class="btn btn-square btn-ghost btn-sm lg:hidden" aria-label="Buka menu">
                    <Menu class="size-5" />
                </label>

                <!-- Desktop: kecilkan / lebarkan sidebar -->
                <button
                    type="button"
                    class="btn btn-square btn-ghost btn-sm max-lg:hidden"
                    :aria-label="collapsed ? 'Lebarkan sidebar' : 'Kecilkan sidebar'"
                    :title="collapsed ? 'Lebarkan sidebar' : 'Kecilkan sidebar'"
                    @click="toggle"
                >
                    <PanelLeftOpen v-if="collapsed" class="size-5" />
                    <PanelLeftClose v-else class="size-5" />
                </button>

                <div class="flex-1"></div>

                <!-- Aksi topbar (ikon saja) dari lib/navigation.js. -->
                <Link
                    v-for="action in topbarActions"
                    :key="action.key"
                    :href="appUrl(action.href)"
                    class="btn btn-square btn-ghost btn-sm"
                    :class="{ 'btn-active': isActive(action.href) }"
                    :aria-label="action.label"
                    :title="action.label"
                >
                    <component :is="action.icon" class="size-5" />
                </Link>

                <select
                    class="select select-bordered select-sm w-32"
                    :value="theme"
                    @change="setTheme($event.target.value)"
                    aria-label="Tema"
                >
                    <option v-for="t in themes" :key="t" :value="t">{{ t }}</option>
                </select>
            </div>

            <main class="flex-1 p-6">
                <PageSkeleton v-if="navigating" />
                <slot v-else />
            </main>

            <!-- Footer -->
            <Footer />
        </div>

        <!-- Sidebar -->
        <div class="drawer-side z-20">
            <label for="sidebar" aria-label="Tutup menu" class="drawer-overlay"></label>

            <aside
                class="flex min-h-full flex-col border-r border-base-300 bg-base-100 transition-[width] duration-200"
                :class="collapsed ? 'w-64 lg:w-16' : 'w-64'"
            >
                <!-- Brand -->
                <div
                    class="flex h-16 items-center gap-3 border-b border-base-300 px-5"
                    :class="{ 'lg:justify-center lg:px-0': collapsed }"
                >
                    <div class="grid size-9 shrink-0 place-items-center rounded-lg bg-primary text-primary-content">
                        <LayoutDashboard class="size-5" />
                    </div>
                    <span class="truncate text-lg font-bold" :class="{ 'lg:hidden': collapsed }">{{ appName }}</span>
                </div>

                <!-- Menu sidebar: Dashboard langsung, grup lain menjadi parent/submenu. -->
                <nav
                    class="flex-1 overflow-y-auto overflow-x-hidden py-4"
                    :class="collapsed ? 'px-3 lg:px-2' : 'px-3'"
                    aria-label="Navigasi utama"
                >
                    <!-- Sidebar desktop diciutkan: rail ikon datar dengan tooltip. -->
                    <ul v-if="isCollapsed" class="menu w-full gap-1 p-0">
                        <li v-for="item in flatItems" :key="item.href">
                            <Link
                                :href="appUrl(item.href)"
                                class="justify-center px-0"
                                :class="{ 'menu-active': isActive(item.href) }"
                                :title="item.label"
                                :aria-label="item.label"
                            >
                                <component :is="item.icon" class="size-5 shrink-0" />
                            </Link>
                        </li>
                    </ul>

                    <!-- Sidebar terbuka: parent bisa dibuka/tutup, submenu tetap terfilter permission. -->
                    <ul v-else class="menu w-full gap-1 p-0">
                        <template v-for="group in visibleGroups" :key="group.title">
                            <template v-if="!group.collapsible">
                                <li class="menu-title mt-1 px-3 text-xs font-semibold uppercase tracking-wider text-base-content/50">
                                    {{ group.title }}
                                </li>
                                <li v-for="item in group.items" :key="item.href">
                                    <Link
                                        :href="appUrl(item.href)"
                                        :class="{ 'menu-active': isActive(item.href) }"
                                    >
                                        <component :is="item.icon" class="size-5 shrink-0" />
                                        <span class="truncate">{{ item.label }}</span>
                                    </Link>
                                </li>
                            </template>

                            <li v-else>
                                <details :open="groupIsActive(group)">
                                    <summary class="font-medium" :class="{ 'text-primary': groupIsActive(group) }">
                                        <component :is="group.icon" class="size-5 shrink-0" />
                                        <span class="truncate">{{ group.title }}</span>
                                    </summary>

                                    <ul>
                                        <li v-for="item in group.items" :key="item.href">
                                            <Link
                                                :href="appUrl(item.href)"
                                                :class="{ 'menu-active': isActive(item.href) }"
                                            >
                                                <component :is="item.icon" class="size-4 shrink-0" />
                                                <span class="truncate">{{ item.label }}</span>
                                            </Link>
                                        </li>
                                    </ul>
                                </details>
                            </li>
                        </template>
                    </ul>
                </nav>

                <!-- Pengguna -->
                <div v-if="user" class="border-t border-base-300 p-3">
                    <div
                        class="flex items-center gap-3 rounded-lg p-2"
                        :class="{ 'lg:flex-col lg:gap-2 lg:px-0': collapsed }"
                    >
                        <div class="avatar avatar-placeholder shrink-0">
                            <div class="size-9 rounded-full bg-neutral text-neutral-content">
                                <img v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                <span v-else class="text-sm">{{ initial }}</span>
                            </div>
                        </div>

                        <div class="min-w-0 flex-1" :class="{ 'lg:hidden': collapsed }">
                            <p class="truncate text-sm font-medium">{{ user.name }}</p>
                            <p class="truncate text-xs text-base-content/60">{{ user.email }}</p>
                        </div>

                        <Link
                            :href="appUrl(routes.logout)"
                            method="post"
                            as="button"
                            class="btn btn-ghost btn-sm btn-square"
                            aria-label="Keluar"
                            title="Keluar"
                        >
                            <LogOut class="size-5" />
                        </Link>
                    </div>
                </div>
            </aside>
        </div>

        <!-- Notifikasi -->
        <Toast />
    </div>
</template>