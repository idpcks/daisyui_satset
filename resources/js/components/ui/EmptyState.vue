<script setup>
import { Inbox } from 'lucide-vue-next';

defineProps({
    title: { type: String, required: true },
    description: { type: String, default: null },
    icon: { type: [Object, Function], default: () => Inbox },
    bordered: { type: Boolean, default: true },
});
</script>

<template>
    <div
        class="flex flex-col items-center justify-center gap-2 px-6 py-10 text-center"
        :class="{ 'rounded-box border border-dashed border-base-300': bordered }"
    >
        <component :is="icon" class="size-8 text-base-content/40" aria-hidden="true" />

        <p class="font-medium">{{ title }}</p>

        <p v-if="description" class="max-w-sm text-sm text-base-content/60">
            {{ description }}
        </p>

        <div v-if="$slots.action" class="mt-2">
            <slot name="action" />
        </div>
    </div>
</template>
