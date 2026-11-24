<script setup>
import { computed, ref, onMounted, onUnmounted, watch } from 'vue';
import { Link, usePage, router } from '@inertiajs/vue3';
import PageSkeleton from '@/components/ui/PageSkeleton.vue';
import Footer from '@/components/ui/Footer.vue';
import ImpersonationBanner from '@/components/ui/ImpersonationBanner.vue';
import Toast from '@/components/ui/Toast.vue';
import BrandMark from '@/components/ui/BrandMark.vue';
import CommandPalette from '@/components/ui/CommandPalette.vue';
import AppSidebar from '@/components/navigation/AppSidebar.vue';
import TopNavigation from '@/components/navigation/TopNavigation.vue';
import TopbarUtilities from '@/components/navigation/TopbarUtilities.vue';
import LayoutFab from '@/components/navigation/LayoutFab.vue';
import { Menu, PanelLeft, PanelLeftClose } from 'lucide-vue-next';
import { useAuth } from '@/lib/useAuth';
import { useNavigation } from '@/lib/useNavigation';
import { useNavigationLayout } from '@/lib/useNavigationLayout';
import { useSidebar } from '@/lib/useSidebar';
import { useI18n } from '@/lib/i18n';
import { appUrl } from '@/lib/url';

const navigating = ref(false);
const isDesktop = ref(false);
let timer;
let desktopMedia = null;
const offs = [];

const searchOpen = ref(false);

const openSearch = () => {
    searchOpen.value = true;
};

// Label hint hanya kosmetik: Mac menampilkan ⌘K, platform lain Ctrl K.
// Shortcut-nya sendiri menerima kedua modifier.
const searchShortcutLabel = computed(() => {
    const platform =
        typeof navigator === 'undefined' ? '' : navigator.platform || navigator.userAgent || '';

    return /mac|iphone|ipad/i.test(platform) ? '⌘K' : 'Ctrl K';
});

// Dialog native lain (mis. form pengguna) menandai dirinya terbuka lewat
// properti `open`; cek properti, bukan atribut, agar tidak bergantung pada
// refleksi atribut di DOM.
const hasOpenDialog = () =>
    Array.from(document.querySelectorAll('dialog')).some((dialog) => dialog.open);

// Jangan mencuri Ctrl/Cmd+K dari dialog lain yang sedang terbuka (mis. form
// pengguna) dan jangan biarkan browser membuka pencarian halaman.
const handleSearchShortcut = (event) => {
    if (!(event.ctrlKey || event.metaKey) || event.key?.toLowerCase() !== 'k' || event.repeat) {
        return;
    }

    if (searchOpen.value) {
        event.preventDefault();

        return;
    }

    if (hasOpenDialog()) {
        return;
    }

    event.preventDefault();
    openSearch();
};

const handleSearchSelect = (entry) => {
    searchOpen.value = false;
    router.visit(appUrl(entry.href));
};

const syncDesktopViewport = (event) => {
    isDesktop.value = event.matches;
};

onMounted(() => {
    if (typeof window.matchMedia === 'function') {
        desktopMedia = window.matchMedia('(min-width: 1024px)');
        isDesktop.value = desktopMedia.matches;
        desktopMedia.addEventListener?.('change', syncDesktopViewport);
    }

    window.addEventListener('keydown', handleSearchShortcut);
    offs.push(() => window.removeEventListener('keydown', handleSearchShortcut));

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

        // Palette tidak boleh menggantung di atas halaman baru, termasuk saat
        // navigasi berasal dari palette itu sendiri.
        searchOpen.value = false;
    },
);

const { user } = useAuth();
const { state, toggle } = useSidebar();
const { layout } = useNavigationLayout();
const { t } = useI18n();

// `collapsed` tetap berupa boolean agar binding existing tidak perlu diubah;
// hidden hanya memengaruhi layout desktop.
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
    return state.value === 'expanded' ? t('Sembunyikan sidebar') : t('Tampilkan sidebar');
});

// Menu sidebar dan aksi topbar berasal dari lib/navigation.js; di sini hanya
// memakai hasil yang sudah disaring permission.
const { groups: visibleGroups, dockItems, topbarActions, menuEntries, isActive } = useNavigation();

const appName = computed(() => page.props.appName ?? 'Aplikasi');

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
    <div class="drawer" :class="{ 'lg:drawer-open': layout === 'sidebar' }">
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
                <div class="navbar-start min-w-0 flex-1 gap-2">
                    <!-- Mobile: buka drawer -->
                    <label for="sidebar" class="btn btn-square btn-ghost btn-sm lg:hidden" :aria-label="t('Buka menu')">
                        <Menu class="size-5" aria-hidden="true" />
                    </label>

                    <!-- Desktop: satu tombol menggilir penuh dan tersembunyi.
                         Ikon mencerminkan kondisi saat ini. -->
                    <button
                        v-if="layout === 'sidebar'"
                        type="button"
                        class="btn btn-square btn-ghost btn-sm max-lg:hidden"
                        :aria-label="toggleLabel"
                        :title="toggleLabel"
                        @click="toggle"
                    >
                        <PanelLeft v-if="state === 'expanded'" class="size-5" aria-hidden="true" />
                        <PanelLeftClose v-else class="size-5" aria-hidden="true" />
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
                        <BrandMark
                            v-if="layout === 'topbar' || isHidden"
                            :name="appName"
                            class="max-lg:hidden"
                        />
                    </Transition>

                    <TopNavigation
                        v-if="layout === 'topbar'"
                        :groups="visibleGroups"
                        :is-active="isActive"
                    />
                </div>

                <div class="navbar-end w-auto flex-none gap-1">
                    <TopbarUtilities
                        :search-shortcut-label="searchShortcutLabel"
                        :topbar-actions="topbarActions"
                        :is-active="isActive"
                        :show-settings="layout === 'sidebar'"
                        :show-user-menu="layout === 'topbar'"
                        :compact="layout === 'topbar'"
                        :user="user"
                        :safe-avatar="safeAvatar"
                        :initial="initial"
                        @search="openSearch"
                    />
                </div>
            </div>

            <main class="flex-1 p-6">
                <PageSkeleton v-if="navigating" />
                <slot v-else />
            </main>

            <!-- Footer -->
            <Footer />
        </div>

        <AppSidebar
            :app-name="appName"
            :groups="visibleGroups"
            :is-active="isActive"
            :user="user"
            :safe-avatar="safeAvatar"
            :initial="initial"
            :is-hidden="isHidden"
            :is-hidden-desktop="layout === 'sidebar' && isHiddenDesktop"
            :is-brand-handoff="isBrandHandoff"
            :hidden-on-desktop="layout === 'topbar'"
        />

        <!-- Dock mobile: pintasan dari navigation.js, disembunyikan di desktop
             karena sidebar sudah menjadi navigasi utama di sana. -->
        <nav :aria-label="t('Navigasi cepat')" class="dock dock-sm lg:hidden">
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

        <!-- Command palette pencarian menu + aksi topbar. -->
        <CommandPalette v-model="searchOpen" :entries="menuEntries" @select="handleSearchSelect" />

        <!-- Dial cepat untuk mengganti tampilan menu, tersedia di semua halaman. -->
        <LayoutFab />

        <!-- Notifikasi -->
        <Toast />
    </div>
</template>
