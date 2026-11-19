<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import PageSkeleton from '@/components/ui/PageSkeleton.vue';
import Footer from '@/components/ui/Footer.vue';
import ImpersonationBanner from '@/components/ui/ImpersonationBanner.vue';
import Toast from '@/components/ui/Toast.vue';
import BrandMark from '@/components/ui/BrandMark.vue';
import { LogOut, Menu, PanelLeft, PanelLeftClose, PanelRight } from 'lucide-vue-next';
import { useTheme } from '@/lib/useTheme';
import { useAuth } from '@/lib/useAuth';
import { useNavigation } from '@/lib/useNavigation';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';
import { useSidebar } from '@/lib/useSidebar';
import { useI18n } from '@/lib/i18n';

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

// Drawer mobile dikendalikan checkbox. Tutup otomatis setiap URL berubah
// supaya menu tidak tetap terbuka menutupi halaman dan dock setelah navigasi.
const sidebarToggle = ref(null);

watch(
    () => page.url,
    () => {
        if (sidebarToggle.value) {
            sidebarToggle.value.checked = false;
        }
    },
);
const { theme, setTheme } = useTheme();

const switchLocale = (locale) => {
    router.post(appUrl('locale'), { locale }, { preserveScroll: true });
};
const { user } = useAuth();
const { state, toggle } = useSidebar();
const { t } = useI18n();

// `collapsed` tetap berupa boolean agar binding existing tidak perlu diubah;
// hidden hanya memengaruhi layout desktop.
const collapsed = computed(() => state.value === 'collapsed');
const isHidden = computed(() => state.value === 'hidden');
const isHiddenDesktop = computed(() => isHidden.value && isDesktop.value);

// Brand header dan brand sidebar tidak boleh tampil bersamaan saat sidebar
// melewati batas hidden. Selama transisi, brand sidebar diredupkan; hook
// `after-enter`/`after-leave` pada <Transition> di template memulihkannya
// setelah animasi brand header selesai.
const isBrandHandoff = ref(false);

watch(state, (next, previous) => {
    isBrandHandoff.value = previous === 'hidden' || next === 'hidden';
});

// Label menjelaskan aksi klik berikutnya, mengikuti siklus tombol.
const toggleLabel = computed(() => {
    if (state.value === 'expanded') {
        return 'Kecilkan sidebar';
    }

    return state.value === 'collapsed' ? 'Sembunyikan sidebar' : 'Tampilkan sidebar';
});

// Menu sidebar dan aksi topbar berasal dari lib/navigation.js; di sini hanya
// memakai hasil yang sudah disaring permission.
const { groups: visibleGroups, dockItems, topbarActions, isActive } = useNavigation();

// Rail ikon hanya dipakai saat sidebar desktop benar-benar diciutkan. Di
// mobile drawer tetap menampilkan hierarki parent/submenu secara utuh.
const isCollapsed = computed(() => collapsed.value && isDesktop.value);

// Lebar aside: rail 4rem saat collapsed, dan 0 saat hidden agar kolom grid
// drawer ikut menutup sehingga main content memakai seluruh lebar. Ukuran
// mobile (w-64) tidak berubah supaya drawer tetap berfungsi.
const sidebarWidth = computed(() => {
    if (isHidden.value) {
        return 'w-64 lg:w-0 lg:overflow-hidden lg:border-r-0';
    }

    return collapsed.value ? 'w-64 lg:w-16' : 'w-64';
});

const flatItems = computed(() => visibleGroups.value.flatMap((group) => group.items));
const groupIsActive = (group) => group.items.some((item) => isActive(item.href));

const appName = computed(() => page.props.appName ?? 'Aplikasi');
const themes = ['light', 'dark', 'cupcake', 'corporate'];

const initial = computed(() => user.value?.name?.charAt(0).toUpperCase() ?? '?');

// Hanya izinkan URL http(s) untuk avatar agar skema berbahaya seperti
// javascript: tidak pernah dirender sebagai src gambar.
const safeAvatar = computed(() => {
    const url = user.value?.avatar;

    if (typeof url !== 'string') {
        return null;
    }

    try {
        const parsed = new URL(url, window.location.origin);

        return parsed.protocol === 'http:' || parsed.protocol === 'https:' ? url : null;
    } catch {
        return null;
    }
});
</script>

<template>
    <div class="drawer lg:drawer-open">
        <input id="sidebar" ref="sidebarToggle" type="checkbox" class="drawer-toggle" />

        <!-- Padding bawah di mobile memberi ruang untuk dock yang fixed agar
             footer dan baris terakhir konten tidak tertutup. -->
        <div
            class="drawer-content flex min-h-screen min-w-0 flex-col bg-base-200 pb-[calc(3.5rem_+_env(safe-area-inset-bottom))] lg:pb-0"
        >
            <!-- Peringatan global saat superadmin memakai akun pengguna lain. -->
            <ImpersonationBanner />

            <!-- Navbar -->
            <div class="navbar sticky top-0 z-10 gap-2 border-b border-base-300 bg-base-100 px-4">
                <!-- Mobile: buka drawer -->
                <label for="sidebar" class="btn btn-square btn-ghost btn-sm lg:hidden" aria-label="Buka menu">
                    <Menu class="size-5" />
                </label>

                <!-- Desktop: satu tombol menggilir penuh, rail ikon, dan tersembunyi.
                     Ikon mencerminkan kondisi saat ini: panel kiri (penuh), panel
                     kanan (rail), panel kiri tertutup (tersembunyi). -->
                <button
                    type="button"
                    class="btn btn-square btn-ghost btn-sm max-lg:hidden"
                    :aria-label="toggleLabel"
                    :title="toggleLabel"
                    @click="toggle"
                >
                    <PanelLeft v-if="state === 'expanded'" class="size-5" />
                    <PanelRight v-else-if="state === 'collapsed'" class="size-5" />
                    <PanelLeftClose v-else class="size-5" />
                </button>

                <!-- Sidebar disembunyikan: brand pindah ke header agar tetap terlihat.
                     Durasi samakan dengan transisi lebar sidebar (200ms) supaya
                     kemunculannya terasa satu gerakan. -->
                <Transition
                    enter-active-class="transition delay-150 duration-150 ease-out motion-reduce:transition-none"
                    enter-from-class="opacity-0 -translate-x-2"
                    enter-to-class="opacity-100 translate-x-0"
                    leave-active-class="transition duration-150 ease-in motion-reduce:transition-none"
                    leave-from-class="opacity-100 translate-x-0"
                    leave-to-class="opacity-0 -translate-x-2"
                    @after-enter="isBrandHandoff = false"
                    @after-leave="isBrandHandoff = false"
                >
                    <BrandMark v-if="isHidden" :name="appName" class="max-lg:hidden" />
                </Transition>

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
                    class="select select-bordered select-sm w-24"
                    :value="page.props.locale"
                    @change="switchLocale($event.target.value)"
                    aria-label="Bahasa"
                >
                    <option v-for="(label, code) in page.props.availableLocales" :key="code" :value="code">
                        {{ label }}
                    </option>
                </select>

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
                class="flex min-h-full min-w-0 flex-col border-r border-base-300 bg-base-100 transition-[width] duration-200"
                :class="sidebarWidth"
                :inert="isHiddenDesktop || null"
            >
                <!-- Brand -->
                <div
                    class="flex h-16 items-center border-b border-base-300 px-5"
                    :class="{ 'lg:justify-center lg:px-0': collapsed }"
                >
                    <BrandMark
                        :name="appName"
                        :compact="collapsed"
                        class="transition-opacity duration-150 motion-reduce:transition-none"
                        :class="isBrandHandoff ? 'opacity-0' : 'opacity-100'"
                    />
                </div>

                <!-- Menu sidebar: Dashboard langsung, grup lain menjadi parent/submenu. -->
                <nav
                    class="flex-1 overflow-y-auto overflow-x-hidden py-4"
                    :class="collapsed ? 'px-3 lg:px-2' : 'px-3'"
                    aria-label="Menu aplikasi"
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
                                    {{ t(group.title) }}
                                </li>
                                <li v-for="item in group.items" :key="item.href">
                                    <Link
                                        :href="appUrl(item.href)"
                                        :class="{ 'menu-active': isActive(item.href) }"
                                    >
                                        <component :is="item.icon" class="size-5 shrink-0" />
                                        <span class="truncate">{{ t(item.label) }}</span>
                                    </Link>
                                </li>
                            </template>

                            <li v-else>
                                <details :open="groupIsActive(group)">
                                    <summary class="font-medium" :class="{ 'text-primary': groupIsActive(group) }">
                                        <component :is="group.icon" class="size-5 shrink-0" />
                                        <span class="truncate">{{ t(group.title) }}</span>
                                    </summary>

                                    <ul>
                                        <li v-for="item in group.items" :key="item.href">
                                            <Link
                                                :href="appUrl(item.href)"
                                                :class="{ 'menu-active': isActive(item.href) }"
                                            >
                                                <component :is="item.icon" class="size-4 shrink-0" />
                                                <span class="truncate">{{ t(item.label) }}</span>
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
                                <img v-if="safeAvatar" :src="safeAvatar" :alt="user.name" />
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

        <!-- Dock mobile: pintasan dari navigation.js, disembunyikan di desktop
             karena sidebar sudah menjadi navigasi utama di sana. -->
        <nav aria-label="Navigasi cepat" class="dock dock-sm lg:hidden">
            <Link
                v-for="item in dockItems"
                :key="item.key"
                :href="appUrl(item.href)"
                class="text-base-content/70"
                :class="{ 'text-primary': isActive(item.href) }"
                :aria-current="isActive(item.href) ? 'page' : undefined"
            >
                <component :is="item.icon" class="size-[1.2em]" aria-hidden="true" />
                <span class="dock-label">{{ t(item.shortLabel ?? item.label) }}</span>
            </Link>
        </nav>

        <!-- Notifikasi -->
        <Toast />
    </div>
</template>