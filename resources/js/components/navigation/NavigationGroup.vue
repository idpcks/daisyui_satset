<script setup>
import { computed, onBeforeUnmount, ref, useId, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';
import { appUrl } from '@/lib/url';

const props = defineProps({
    group: { type: Object, required: true },
    isActive: { type: Function, required: true },
});

const page = usePage();
const { t } = useI18n();

const trigger = ref(null);
const popover = ref(null);
const open = ref(false);
const baseId = useId();
const popoverId = `topnav-${baseId.replace(/[^a-zA-Z0-9_-]/g, '')}`;
const anchorName = `--${popoverId}`;

const groupIsActive = computed(() => props.group.items.some((item) => props.isActive(item.href)));

const onToggle = (event) => {
    open.value = event.newState
        ? event.newState === 'open'
        : Boolean(popover.value?.matches?.(':popover-open'));
};

const close = ({ restoreFocus = false } = {}) => {
    if (open.value && typeof popover.value?.hidePopover === 'function') {
        try {
            popover.value.hidePopover();
        } catch {}
    }

    open.value = false;

    if (restoreFocus) {
        trigger.value?.focus();
    }
};

// Navigasi Inertia tidak memuat ulang halaman, jadi popover harus ditutup
// secara eksplisit saat URL berubah.
watch(
    () => page.url,
    () => close(),
);

onBeforeUnmount(() => close());

const onKeydown = (event) => {
    if (event.key === 'Escape' && open.value) {
        event.stopPropagation();
        close({ restoreFocus: true });
    }
};
</script>

<template>
    <li @keydown="onKeydown">
        <button
            ref="trigger"
            type="button"
            class="font-medium"
            :class="{ 'menu-active': groupIsActive }"
            :popovertarget="popoverId"
            :aria-controls="popoverId"
            :aria-expanded="open ? 'true' : 'false'"
            :style="{ 'anchor-name': anchorName }"
        >
            <component :is="group.icon" class="size-4 shrink-0" aria-hidden="true" />
            <span class="truncate">{{ t(group.title) }}</span>
            <ChevronDown
                class="size-4 shrink-0 transition-transform duration-150 motion-reduce:transition-none"
                :class="{ 'rotate-180': open }"
                aria-hidden="true"
            />
        </button>

        <ul
            :id="popoverId"
            ref="popover"
            class="dropdown menu w-56 rounded-box bg-base-100 p-2 shadow"
            popover
            :style="{ 'position-anchor': anchorName }"
            @toggle="onToggle"
        >
            <li v-for="item in group.items" :key="item.href">
                <Link
                    :href="appUrl(item.href)"
                    :aria-current="isActive(item.href) ? 'page' : undefined"
                    :class="{ 'menu-active': isActive(item.href) }"
                    @click="close()"
                >
                    <component :is="item.icon" class="size-4 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ t(item.label) }}</span>
                </Link>
            </li>
        </ul>
    </li>
</template>
