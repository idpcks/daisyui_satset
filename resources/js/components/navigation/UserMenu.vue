<script setup>
import { onBeforeUnmount, ref, useId, watch } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';
import { ChevronDown, LogOut, Settings } from 'lucide-vue-next';
import { useI18n } from '@/lib/i18n';
import { routes } from '@/lib/routes';
import { appUrl } from '@/lib/url';

defineProps({
    user: { type: Object, required: true },
    safeAvatar: { type: String, default: null },
    initial: { type: String, default: '?' },
});

const page = usePage();
const { t } = useI18n();

const trigger = ref(null);
const popover = ref(null);
const open = ref(false);
const baseId = useId();
const popoverId = `user-menu-${baseId.replace(/[^a-zA-Z0-9_-]/g, '')}`;
const anchorName = `--${popoverId}`;

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
    <div class="dropdown dropdown-end" @keydown="onKeydown">
        <button
            ref="trigger"
            type="button"
            class="btn btn-ghost btn-sm gap-1 px-2"
            :popovertarget="popoverId"
            :aria-controls="popoverId"
            :aria-expanded="open ? 'true' : 'false'"
            :aria-label="t('Buka menu pengguna')"
            :style="{ 'anchor-name': anchorName }"
        >
            <div class="avatar avatar-placeholder">
                <div class="size-8 rounded-full bg-neutral text-neutral-content">
                    <img v-if="safeAvatar" :src="safeAvatar" :alt="user.name" />
                    <span v-else class="text-xs">{{ initial }}</span>
                </div>
            </div>
            <ChevronDown
                class="size-4 shrink-0 transition-transform duration-150 motion-reduce:transition-none"
                :class="{ 'rotate-180': open }"
                aria-hidden="true"
            />
        </button>

        <ul
            :id="popoverId"
            ref="popover"
            class="dropdown menu w-60 rounded-box bg-base-100 p-2 shadow"
            popover
            :style="{ 'position-anchor': anchorName }"
            @toggle="onToggle"
        >
            <li class="menu-title">
                <div class="min-w-0">
                    <p class="truncate text-sm font-medium text-base-content">{{ user.name }}</p>
                    <p class="truncate text-xs text-base-content/60">{{ user.email }}</p>
                </div>
            </li>
            <li>
                <Link :href="appUrl(routes.settings)" @click="close()">
                    <Settings class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ t('Pengaturan') }}</span>
                </Link>
            </li>
            <li>
                <Link
                    :href="appUrl(routes.logout)"
                    method="post"
                    as="button"
                    class="w-full text-left"
                    @click="close()"
                >
                    <LogOut class="size-4 shrink-0" aria-hidden="true" />
                    <span>{{ t('Keluar') }}</span>
                </Link>
            </li>
        </ul>
    </div>
</template>
