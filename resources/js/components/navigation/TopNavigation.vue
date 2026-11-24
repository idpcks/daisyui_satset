<script setup>
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { MoreHorizontal } from 'lucide-vue-next';
import NavigationGroup from '@/components/navigation/NavigationGroup.vue';
import { useI18n } from '@/lib/i18n';
import { appUrl } from '@/lib/url';

const props = defineProps({
    groups: { type: Array, default: () => [] },
    isActive: { type: Function, required: true },
});

const { t } = useI18n();

const nav = ref(null);
const compact = ref(false);
let observer = null;
let frame = null;

// Dashboard dan item non-collapsible lain tetap menjadi link langsung. Grup
// yang punya submenu dirender sebagai dropdown.
const directItems = computed(() =>
    props.groups.filter((group) => !group.collapsible).flatMap((group) => group.items),
);

const collapsibleGroups = computed(() => props.groups.filter((group) => group.collapsible));

const moreGroup = computed(() => ({
    title: 'Lainnya',
    icon: MoreHorizontal,
    collapsible: true,
    items: collapsibleGroups.value.flatMap((group) => group.items),
}));

// Jika menu tidak muat, semua grup dilipat ke satu dropdown "Lainnya".
// Dashboard tetap terlihat agar tujuan utama selalu tersedia.
const measure = () => {
    compact.value = false;

    nextTick(() => {
        const element = nav.value;

        if (element) {
            compact.value = element.scrollWidth > element.clientWidth + 1;
        }
    });
};

const scheduleMeasure = () => {
    if (typeof requestAnimationFrame === 'function') {
        cancelAnimationFrame(frame);
        frame = requestAnimationFrame(measure);

        return;
    }

    measure();
};

onMounted(() => {
    if (typeof ResizeObserver !== 'undefined' && nav.value?.parentElement) {
        observer = new ResizeObserver(scheduleMeasure);
        observer.observe(nav.value.parentElement);
    }

    window.addEventListener('resize', scheduleMeasure);
    scheduleMeasure();
});

onUnmounted(() => {
    observer?.disconnect();
    window.removeEventListener('resize', scheduleMeasure);

    if (typeof cancelAnimationFrame === 'function') {
        cancelAnimationFrame(frame);
    }
});
</script>

<template>
    <nav ref="nav" class="hidden min-w-0 lg:flex" :aria-label="t('Menu aplikasi')">
        <ul class="menu menu-horizontal gap-1 px-1">
            <li v-for="item in directItems" :key="item.href">
                <Link
                    :href="appUrl(item.href)"
                    :aria-current="isActive(item.href) ? 'page' : undefined"
                    :class="{ 'menu-active': isActive(item.href) }"
                >
                    <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ t(item.label) }}</span>
                </Link>
            </li>

            <template v-if="!compact">
                <NavigationGroup
                    v-for="group in collapsibleGroups"
                    :key="group.title"
                    :group="group"
                    :is-active="isActive"
                />
            </template>

            <NavigationGroup
                v-else-if="moreGroup.items.length > 0"
                :group="moreGroup"
                :is-active="isActive"
            />
        </ul>
    </nav>
</template>
